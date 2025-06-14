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
$userId = $_SESSION['user_id'];

// 包含資料庫連接
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 開始交易
    $db->beginTransaction();
    
    // 1. 更新玩家已完成關卡記錄
    updatePlayerLevelRecord($db, $userId, $levelId);
    
    // 2. 解鎖下一個關卡
    $unlockedLevels = unlockNextLevels($db, $levelId, $userId);
    
    // 3. 檢查章節是否已完成
    $completedChapter = checkChapterCompletion($db, $chapterId, $userId);
    
    // 提交交易
    $db->commit();
    
    // 返回結果
    echo json_encode([
        'success' => true,
        'message' => '關卡完成記錄成功',
        'unlockedLevels' => $unlockedLevels,
        'completedChapter' => $completedChapter
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
}
exit;

/**
 * 更新玩家關卡記錄
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
                      SET success_count = success_count + 1 
                      WHERE record_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$record['record_id']]);
    } else {
        // 不存在記錄，創建新記錄
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
            // 創建未完成的關卡記錄
            $insertRecordQuery = "INSERT INTO player_level_records 
                               (player_id, level_id, attempt_count, success_count) 
                               VALUES (?, ?, 0, 0)";
            $insertRecordStmt = $db->prepare($insertRecordQuery);
            $insertRecordStmt->execute([$userId, $unlockedLevelId]);
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
?>
