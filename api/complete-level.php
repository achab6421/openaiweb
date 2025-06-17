<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}
require_once "../config/database.php";
$db = (new Database())->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$levelId = isset($data['levelId']) ? intval($data['levelId']) : 0;
$chapterId = isset($data['chapterId']) ? intval($data['chapterId']) : 0;
$userId = $_SESSION['user_id'];

if (!$levelId || !$chapterId) {
    echo json_encode(['success' => false, 'message' => '參數錯誤']);
    exit;
}

try {
    // 確保 experience 列存在
    try {
        $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
        $checkColumnStmt = $db->query($checkColumnQuery);
        $experienceColumnExists = ($checkColumnStmt->rowCount() > 0);
        
        if (!$experienceColumnExists) {
            $addColumnQuery = "ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0";
            $db->exec($addColumnQuery);
            error_log("Added missing 'experience' column to players table");
        }
    } catch (PDOException $e) {
        error_log("Error checking/adding experience column: " . $e->getMessage());
    }
    
    // 開始交易
    $db->beginTransaction();
    
    // 1. 獲取關卡信息
    $levelQuery = "SELECT * FROM levels WHERE level_id = ?";
    $levelStmt = $db->prepare($levelQuery);
    $levelStmt->execute([$levelId]);
    $levelData = $levelStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$levelData) {
        throw new Exception("關卡不存在");
    }
    
    // 2. 獲取怪物信息以取得經驗值獎勵
    $monsterQuery = "SELECT * FROM monsters WHERE monster_id = ?";
    $monsterStmt = $db->prepare($monsterQuery);
    $monsterStmt->execute([$levelData['monster_id']]);
    $monsterData = $monsterStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$monsterData) {
        throw new Exception("怪物信息不存在");
    }
    
    // 經驗值獎勵
    $expReward = $monsterData['exp_reward'];
    
    // 3. 更新玩家關卡記錄
    updatePlayerLevelRecord($db, $userId, $levelId);
    
    // 4. 解鎖下一個關卡
    $unlockedLevels = unlockNextLevels($db, $levelId, $userId);
    
    // 5. 檢查章節是否已完成
    $completedChapter = checkChapterCompletion($db, $chapterId ?: $levelData['chapter_id'], $userId);
    
    // 6. 增加經驗值並檢查是否升級
    $levelUpInfo = addExperienceAndCheckLevelUp($db, $userId, $expReward);
    
    // 提交交易
    $db->commit();
    
    // 返回結果
    echo json_encode([
        'success' => true,
        'message' => '關卡完成記錄成功',
        'unlockedLevels' => $unlockedLevels,
        'completedChapter' => $completedChapter,
        'expReward' => $expReward,
        'levelUp' => $levelUpInfo['levelUp'],
        'newLevel' => $levelUpInfo['newLevel'],
        'currentExp' => $levelUpInfo['currentExp'],
        'expToNextLevel' => $levelUpInfo['expToNextLevel']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // 回滾交易
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => '關卡完成記錄失敗: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // 回滾交易
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
exit;

/**
 * 更新玩家關卡記錄
 * @param PDO $db 資料庫連接
 * @param int $userId 玩家ID
 * @param int $levelId 關卡ID
 */
function updatePlayerLevelRecord($db, $userId, $levelId) {
    // 檢查記錄是否存在
    $checkQuery = "SELECT * FROM player_level_records 
                  WHERE player_id = ? AND level_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$userId, $levelId]);
    
    if ($checkStmt->rowCount() > 0) {
        // 存在記錄，更新成功次數
        $record = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $updateQuery = "UPDATE player_level_records 
                      SET success_count = success_count + 1,
                          attempt_count = attempt_count + 1,
                          updated_at = CURRENT_TIMESTAMP 
                      WHERE record_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$record['record_id']]);
    } else {
        // 不存在記錄，創建新記錄 (成功完成關卡)
        $insertQuery = "INSERT INTO player_level_records 
                      (player_id, level_id, attempt_count, success_count) 
                      VALUES (?, ?, 1, 1)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$userId, $levelId]);
    }
    
    // 更新玩家的已完成關卡(JSON)
    $playerQuery = "SELECT completed_levels FROM players WHERE player_id = ?";
    $playerStmt = $db->prepare($playerQuery);
    $playerStmt->execute([$userId]);
    $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
    
    $completedLevels = json_decode($player['completed_levels'] ?? '[]', true);
    if (!in_array($levelId, $completedLevels)) {
        $completedLevels[] = $levelId;
        
        $updatePlayerQuery = "UPDATE players 
                             SET completed_levels = ? 
                             WHERE player_id = ?";
        $updatePlayerStmt = $db->prepare($updatePlayerQuery);
        $updatePlayerStmt->execute([json_encode($completedLevels), $userId]);
    }
}

/**
 * 解鎖下一個關卡
 * @param PDO $db 資料庫連接
 * @param int $currentLevelId 當前完成的關卡ID
 * @param int $userId 玩家ID
 * @return array 解鎖的關卡列表
 */
function unlockNextLevels($db, $currentLevelId, $userId) {
    // 查找以當前關卡為前置條件的關卡
    $nextLevelQuery = "SELECT level_id, chapter_id 
                      FROM levels 
                      WHERE prerequisite_level_id = ?";
    $nextLevelStmt = $db->prepare($nextLevelQuery);
    $nextLevelStmt->execute([$currentLevelId]);
    
    $unlockedLevels = [];
    
    while ($nextLevel = $nextLevelStmt->fetch(PDO::FETCH_ASSOC)) {
        $unlockedLevelId = $nextLevel['level_id'];
        $chapterId = $nextLevel['chapter_id'];
        
        // 檢查此關卡的玩家記錄是否存在
        $checkRecordQuery = "SELECT * FROM player_level_records 
                           WHERE player_id = ? AND level_id = ?";
        $checkRecordStmt = $db->prepare($checkRecordQuery);
        $checkRecordStmt->execute([$userId, $unlockedLevelId]);
        
        if ($checkRecordStmt->rowCount() == 0) {
            // 創建未完成的關卡記錄 (解鎖狀態: success_count=0)
            $insertRecordQuery = "INSERT INTO player_level_records 
                               (player_id, level_id, attempt_count, success_count) 
                               VALUES (?, ?, 0, 0)";
            $insertRecordStmt = $db->prepare($insertRecordQuery);
            $insertRecordStmt->execute([$userId, $unlockedLevelId]);
            
            // 檢查是否需要解鎖章節
            $checkChapterQuery = "SELECT * FROM player_chapter_records 
                                WHERE player_id = ? AND chapter_id = ?";
            $checkChapterStmt = $db->prepare($checkChapterQuery);
            $checkChapterStmt->execute([$userId, $chapterId]);
            
            if ($checkChapterStmt->rowCount() == 0) {
                $unlockChapterQuery = "INSERT INTO player_chapter_records 
                                     (player_id, chapter_id, is_completed) 
                                     VALUES (?, ?, 0)";
                $unlockChapterStmt = $db->prepare($unlockChapterQuery);
                $unlockChapterStmt->execute([$userId, $chapterId]);
            }
            
            $unlockedLevels[] = $unlockedLevelId;
        }
    }
    
    return $unlockedLevels;
}

/**
 * 檢查章節是否已完成
 * @param PDO $db 資料庫連接
 * @param int $chapterId 章節ID
 * @param int $userId 玩家ID
 * @return bool|int 如果章節完成返回章節ID，否則返回false
 */
function checkChapterCompletion($db, $chapterId, $userId) {
    // 獲取章節中的所有關卡
    $allLevelsStmt = $db->prepare("SELECT level_id FROM levels WHERE chapter_id = ?");
    $allLevelsStmt->execute([$chapterId]);
    $allLevelIds = $allLevelsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($allLevelIds)) {
        return false;
    }
    
    // 獲取玩家已完成的該章節關卡
    $placeholders = str_repeat('?,', count($allLevelIds) - 1) . '?';
    $completedStmt = $db->prepare("SELECT level_id FROM player_level_records 
                                  WHERE player_id = ? AND success_count > 0 
                                  AND level_id IN ($placeholders)");
    $params = array_merge([$userId], $allLevelIds);
    $completedStmt->execute($params);
    $completedLevelIds = $completedStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 判斷是否所有關卡都已完成
    if (count($allLevelIds) > 0 && count($allLevelIds) === count($completedLevelIds)) {
        // 標記章節完成
        $updateChapterStmt = $db->prepare("UPDATE player_chapter_records 
                                         SET is_completed = 1 
                                         WHERE player_id = ? AND chapter_id = ?");
        $updateChapterStmt->execute([$userId, $chapterId]);
        return $chapterId;
    }
    
    return false;
}

/**
 * 增加經驗值並檢查是否升級
 * @param PDO $db 資料庫連接
 * @param int $userId 玩家ID
 * @param int $expAmount 獲得的經驗值
 * @return array 升級信息
 */
function addExperienceAndCheckLevelUp($db, $userId, $expAmount) {
    // 獲取玩家當前等級和經驗值
    $playerStmt = $db->prepare("SELECT level, experience FROM players WHERE player_id = ?");
    $playerStmt->execute([$userId]);
    $playerData = $playerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$playerData) {
        throw new Exception("玩家數據不存在");
    }
    
    $currentLevel = $playerData['level'] ?? 1;
    $currentExp = $playerData['experience'] ?? 0;
    $newExp = $currentExp + $expAmount;
    
    // 計算升級所需經驗值
    $expToNextLevel = 100 * $currentLevel; // 簡單計算：下一級所需經驗 = 當前等級 * 100
    $levelUp = false;
    $newLevel = $currentLevel;
    
    // 檢查是否升級
    while ($newExp >= $expToNextLevel) {
        $newExp -= $expToNextLevel;
        $newLevel++;
        $expToNextLevel = 100 * $newLevel;
        $levelUp = true;
    }
    
    // 更新玩家資料
    $updateStmt = $db->prepare("UPDATE players SET level = ?, experience = ? WHERE player_id = ?");
    $updateStmt->execute([$newLevel, $newExp, $userId]);
    
    return [
        'levelUp' => $levelUp,
        'newLevel' => $newLevel,
        'currentExp' => $newExp,
        'expToNextLevel' => $expToNextLevel
    ];
}
?>
    $completedLevels = json_decode($player['completed_levels'] ?? '[]', true);
    
    // 檢查是否所有章節關卡都已完成
    $allCompleted = true;
    foreach ($levelIds as $levelId) {
        if (!in_array($levelId, $completedLevels)) {
            $allCompleted = false;
            break;
        }
    }
    
    if ($allCompleted) {
        // 更新玩家章節記錄
        $updateChapterRecordQuery = "UPDATE player_chapter_records 
                                   SET is_completed = 1 
                                   WHERE player_id = ? AND chapter_id = ?";
        $updateChapterStmt = $db->prepare($updateChapterRecordQuery);
        $updateChapterStmt->execute([$userId, $chapterId]);
        
        // 解鎖下一個章節
        $nextChapterId = $chapterId + 1;
        $updateNextChapterQuery = "UPDATE player_chapter_records 
                                 SET is_unlocked = 1 
                                 WHERE player_id = ? AND chapter_id = ?";
        $updateNextChapterStmt = $db->prepare($updateNextChapterQuery);
        $updateNextChapterStmt->execute([$userId, $nextChapterId]);
        
        // 獲取完成的章節信息
        $chapterQuery = "SELECT * FROM chapters WHERE chapter_id = ?";
        $chapterStmt = $db->prepare($chapterQuery);
        $chapterStmt->execute([$chapterId]);
        return $chapterStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return null;
}

/**
 * 增加經驗值並檢查是否升級
 * @param PDO $db 資料庫連接
 * @param int $userId 玩家ID
 * @param int $expAmount 經驗值增加量
 * @return array 包含升級信息的數組
 */
function addExperienceAndCheckLevelUp($db, $userId, $expAmount) {
    // 獲取玩家當前等級和經驗值
    $playerQuery = "SELECT player_id, level, experience, attack_power, base_hp FROM players WHERE player_id = ?";
    $playerStmt = $db->prepare($playerQuery);
    $playerStmt->execute([$userId]);
    $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        throw new Exception("找不到玩家數據");
    }
    
    // 確保經驗值欄位存在於資料庫中
    try {
        // 檢查 experience 列是否存在
        $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
        $checkColumnStmt = $db->query($checkColumnQuery);
        $experienceColumnExists = ($checkColumnStmt->rowCount() > 0);
        
        if (!$experienceColumnExists) {
            // 如果列不存在，則添加它
            $addColumnQuery = "ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0";
            $db->exec($addColumnQuery);
            error_log("Added missing 'experience' column to players table");
            
            // 設置初始經驗值為0
            $player['experience'] = 0;
        }
    } catch (PDOException $e) {
        error_log("Error checking/adding experience column: " . $e->getMessage());
        // 假設經驗值為0並繼續執行
        $player['experience'] = $player['experience'] ?? 0;
    }
    
    // 計算現有經驗值和新經驗值
    $currentLevel = intval($player['level']);
    $currentExp = intval($player['experience'] ?? 0);
    $newExp = $currentExp + $expAmount;
    
    // 升級邏輯變數
    $levelUp = false;
    $newLevel = $currentLevel;
    $levelsGained = 0;
    $finalExp = $newExp; // 預設為加上經驗值後的數值
    
    // 檢查是否需要升級 - 可能會連續升多級
    while (true) {
        // 查詢當前等級所需的下一級經驗值
        $nextLevelQuery = "SELECT required_exp, level_attribute_bonus, description FROM player_level_experience 
                           WHERE level = ? + 1";
        $nextLevelStmt = $db->prepare($nextLevelQuery);
        $nextLevelStmt->execute([$newLevel]);
        $nextLevelData = $nextLevelStmt->fetch(PDO::FETCH_ASSOC);
        
        // 如果找不到下一級數據，使用預設公式計算
        if (!$nextLevelData) {
            $expToNextLevel = $newLevel * 100;  // 預設公式
            $levelAttributeBonus = 1.05;  // 預設屬性提升5%
            $newLevelDescription = null;
        } else {
            $expToNextLevel = $nextLevelData['required_exp'];
            $levelAttributeBonus = $nextLevelData['level_attribute_bonus'];
            $newLevelDescription = $nextLevelData['description'];
        }
        
        // 計算溢出的經驗值
        if ($finalExp >= $expToNextLevel) {
            // 升級！
            $newLevel++;
            $levelsGained++;
            $levelUp = true;
            
            // 扣除升級所需經驗值，剩餘的作為新等級的經驗值
            $finalExp -= $expToNextLevel;
            
            // 計算新的攻擊力和HP
            if ($newLevel == $currentLevel + 1) { // 首次升級時更新屬性
                $newAttackPower = ceil($player['attack_power'] * $levelAttributeBonus);
                $newBaseHp = ceil($player['base_hp'] * $levelAttributeBonus);
            } else { // 連續升級時繼續提升
                $newAttackPower = ceil($newAttackPower * $levelAttributeBonus);
                $newBaseHp = ceil($newBaseHp * $levelAttributeBonus);
            }
            
            // 繼續檢查是否可以再次升級
        } else {
            // 不能再升級了，跳出循環
            break;
        }
    }
    
    // 更新資料庫中的玩家數據
    if ($levelUp) {
        try {
            // 更新玩家等級、經驗值、攻擊力和HP
            $updatePlayerQuery = "UPDATE players SET 
                level = ?, 
                experience = ?, 
                attack_power = ?,
                base_hp = ? 
                WHERE player_id = ?";
            $updatePlayerStmt = $db->prepare($updatePlayerQuery);
            $updatePlayerStmt->execute([
                $newLevel,
                $finalExp, // 使用扣除後的剩餘經驗值
                $newAttackPower,
                $newBaseHp,
                $userId
            ]);
            
            // 更新會話數據
            $_SESSION['level'] = $newLevel;
            $_SESSION['attack_power'] = $newAttackPower;
            $_SESSION['base_hp'] = $newBaseHp;
            
        } catch (PDOException $e) {
            error_log("Error updating player stats: " . $e->getMessage());
            throw $e;
        }
    } else {
        // 只更新經驗值
        try {
            $updateExpQuery = "UPDATE players SET experience = ? WHERE player_id = ?";
            $updateExpStmt = $db->prepare($updateExpQuery);
            $updateExpStmt->execute([$newExp, $userId]); // 使用新經驗值
        } catch (PDOException $e) {
            error_log("Error updating experience: " . $e->getMessage());
            throw $e;
        }
    }
    
    // 查詢下一級所需經驗值 (可能是剛升級的新級別的下一級)
    $nextLevelQuery = "SELECT required_exp, description FROM player_level_experience 
                      WHERE level = ? + 1";
    $nextLevelStmt = $db->prepare($nextLevelQuery);
    $nextLevelStmt->execute([$newLevel]);
    $nextLevelData = $nextLevelStmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果找不到下一級數據，使用預設公式
    $nextRequiredExp = $nextLevelData ? $nextLevelData['required_exp'] : ($newLevel * 100);
    $nextLevelTitle = $nextLevelData ? $nextLevelData['description'] : null;
    
    // 返回升級信息
    return [
        'levelUp' => $levelUp,
        'newLevel' => $newLevel,
        'levelsGained' => $levelsGained,
        'currentExp' => $finalExp, // 使用最終經驗值
        'expToNextLevel' => $nextRequiredExp,
        'expGained' => $expAmount,
        'newLevelTitle' => $newLevelDescription ?? ('等級 ' . $newLevel),
        'nextLevelTitle' => $nextLevelTitle ?? ('等級 ' . ($newLevel + 1))
    ];
}
?>
