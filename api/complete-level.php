<?php
// 完成關卡和解鎖下一關的API
header('Content-Type: application/json; charset=utf-8');

// 檢查是否為AJAX請求
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查會話
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 解析請求數據
$data = json_decode(file_get_contents('php://input'), true);

// 檢查請求數據的完整性
if (!isset($data['levelId']) || !is_numeric($data['levelId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data fields',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$levelId = intval($data['levelId']);
$chapterId = isset($data['chapterId']) ? intval($data['chapterId']) : null;
$userId = intval($_SESSION['user_id']); // 確保使用整數類型

// 包含資料庫連接
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 開始交易前先檢查玩家是否存在
    $checkPlayerQuery = "SELECT * FROM players WHERE player_id = ?";
    $checkPlayerStmt = $db->prepare($checkPlayerQuery);
    $checkPlayerStmt->execute([$userId]);
    
    if ($checkPlayerStmt->rowCount() == 0) {
        // 創建測試玩家
        $createPlayerQuery = "INSERT INTO players (player_id, username, account, password, level, attack_power, base_hp) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
        $createPlayerStmt = $db->prepare($createPlayerQuery);
        $createPlayerStmt->execute([
            $userId,
            $_SESSION['username'] ?? '測試玩家',
            'test_account_' . $userId,
            password_hash('test_password', PASSWORD_DEFAULT),
            $_SESSION['level'] ?? 1,
            $_SESSION['attack_power'] ?? 10,
            $_SESSION['base_hp'] ?? 100
        ]);
        
        error_log("Created test player with ID: $userId");
    }

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
            
            // 更新玩家的已解鎖關卡列表 (添加到 JSON)
            $playerQuery = "SELECT completed_levels FROM players WHERE player_id = ?";
            $playerStmt = $db->prepare($playerQuery);
            $playerStmt->execute([$userId]);
            $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
            
            $completedLevels = json_decode($player['completed_levels'] ?? '[]', true);
            if (!in_array($unlockedLevelId, $completedLevels)) {
                $completedLevels[] = $unlockedLevelId;
                
                $updatePlayerQuery = "UPDATE players 
                                     SET completed_levels = ? 
                                     WHERE player_id = ?";
                $updatePlayerStmt = $db->prepare($updatePlayerQuery);
                $updatePlayerStmt->execute([json_encode($completedLevels), $userId]);
            }
        }
        
        // 獲取關卡名稱
        $levelNameQuery = "SELECT * FROM levels WHERE level_id = ?";
        $levelNameStmt = $db->prepare($levelNameQuery);
        $levelNameStmt->execute([$unlockedLevelId]);
        $levelInfo = $levelNameStmt->fetch(PDO::FETCH_ASSOC);
        
        // 添加到解鎖列表
        $unlockedLevels[] = [
            'id' => $unlockedLevelId,
            'chapter_id' => $chapterId,
            'name' => "關卡 #" . $unlockedLevelId // 可根據實際情況修改
        ];
    }
    
    return $unlockedLevels;
}

/**
 * 檢查章節是否已完成
 */
function checkChapterCompletion($db, $chapterId, $userId) {
    if (!$chapterId) return null;
    
    // 獲取章節中的所有關卡
    $levelsQuery = "SELECT level_id FROM levels WHERE chapter_id = ?";
    $levelsStmt = $db->prepare($levelsQuery);
    $levelsStmt->execute([$chapterId]);
    
    $levelIds = [];
    while ($level = $levelsStmt->fetch(PDO::FETCH_ASSOC)) {
        $levelIds[] = $level['level_id'];
    }
    
    if (empty($levelIds)) return null;
    
    // 獲取玩家已完成的關卡
    $playerQuery = "SELECT completed_levels FROM players WHERE player_id = ?";
    $playerStmt = $db->prepare($playerQuery);
    $playerStmt->execute([$userId]);
    $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
    
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
    
    // 查詢下一級所需經驗值
    $nextLevelQuery = "SELECT required_exp, level_attribute_bonus, description FROM player_level_experience 
                       WHERE level = ? + 1";
    $nextLevelStmt = $db->prepare($nextLevelQuery);
    $nextLevelStmt->execute([$currentLevel]);
    $nextLevelData = $nextLevelStmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果找不到下一級數據，使用預設公式計算
    if (!$nextLevelData) {
        $expToNextLevel = $currentLevel * 100;  // 預設公式
        $levelAttributeBonus = 1.05;  // 預設屬性提升5%
        $newLevelDescription = null;
    } else {
        $expToNextLevel = $nextLevelData['required_exp'];
        $levelAttributeBonus = $nextLevelData['level_attribute_bonus'];
        $newLevelDescription = $nextLevelData['description'];
    }
    
    // 檢查是否需要升級
    $levelUp = false;
    $newLevel = $currentLevel;
    $levelsGained = 0;
    
    // 如果經驗值足夠，升級玩家
    if ($newExp >= $expToNextLevel) {
        // 進行升級
        $newLevel = $currentLevel + 1;
        $levelsGained = 1;
        $levelUp = true;
        
        // 計算新的攻擊力和HP
        $newAttackPower = ceil($player['attack_power'] * $levelAttributeBonus);
        $newBaseHp = ceil($player['base_hp'] * $levelAttributeBonus);
        
        // 更新玩家數據
        try {
            $updatePlayerQuery = "UPDATE players SET 
                level = ?, 
                experience = ?, 
                attack_power = ?,
                base_hp = ? 
                WHERE player_id = ?";
            $updatePlayerStmt = $db->prepare($updatePlayerQuery);
            $updatePlayerStmt->execute([
                $newLevel,
                $newExp,  // 保留溢出的經驗值，不再減去所需經驗值
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
            $updateExpStmt->execute([$newExp, $userId]);
        } catch (PDOException $e) {
            error_log("Error updating experience: " . $e->getMessage());
            throw $e;
        }
    }
    
    // 查詢下一級所需經驗值 (可能是剛升級的新級別的下一級)
    $nextLevelQuery = "SELECT required_exp FROM player_level_experience 
                      WHERE level = ? + 1";
    $nextLevelStmt = $db->prepare($nextLevelQuery);
    $nextLevelStmt->execute([$newLevel]);
    $nextLevelData = $nextLevelStmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果找不到下一級數據，使用預設公式
    $nextRequiredExp = $nextLevelData ? $nextLevelData['required_exp'] : ($newLevel * 100);
    
    // 返回升級信息
    return [
        'levelUp' => $levelUp,
        'newLevel' => $newLevel,
        'levelsGained' => $levelsGained,
        'currentExp' => $newExp,
        'expToNextLevel' => $nextRequiredExp,
        'expGained' => $expAmount,
        'newLevelTitle' => $newLevelDescription ?? ('等級 ' . $newLevel)
    ];
}
?>
