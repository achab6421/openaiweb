<?php
// 檢查玩家記錄的API
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

$userId = intval($_SESSION['user_id']); // 確保使用整數類型

// 包含資料庫連接
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 首先檢查並確保測試玩家存在
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
        
        // 重新獲取玩家數據
        $checkPlayerStmt->execute([$userId]);
    }
    
    // 確保 experience 列存在
    try {
        $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
        $checkColumnStmt = $db->query($checkColumnQuery);
        $experienceColumnExists = ($checkColumnStmt->rowCount() > 0);
        
        if (!$experienceColumnExists) {
            $addColumnQuery = "ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0";
            $db->exec($addColumnQuery);
        }
    } catch (PDOException $e) {
        error_log("Error checking/adding experience column: " . $e->getMessage());
    }
    
    $playerInfo = $checkPlayerStmt->fetch(PDO::FETCH_ASSOC);

    // 獲取已完成的關卡列表
    $completedLevels = json_decode($playerInfo['completed_levels'] ?? '[]', true);
    
    // 獲取關卡記錄
    $recordsQuery = "SELECT * FROM player_level_records WHERE player_id = ?";
    $recordsStmt = $db->prepare($recordsQuery);
    $recordsStmt->execute([$userId]);
    $levelRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 獲取章節記錄
    $chapterRecordsQuery = "SELECT * FROM player_chapter_records WHERE player_id = ?";
    $chapterRecordsStmt = $db->prepare($chapterRecordsQuery);
    $chapterRecordsStmt->execute([$userId]);
    $chapterRecords = $chapterRecordsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 返回結果
    echo json_encode([
        'success' => true,
        'playerInfo' => [
            'id' => $playerInfo['player_id'],
            'username' => $playerInfo['username'],
            'level' => $playerInfo['level'],
            'attack_power' => $playerInfo['attack_power'],
            'base_hp' => $playerInfo['base_hp'],
            'experience' => $playerInfo['experience'] ?? 0
        ],
        'completedLevels' => $completedLevels,
        'completedLevelsCount' => count($completedLevels),
        'levelRecords' => $levelRecords,
        'levelRecordsCount' => count($levelRecords),
        'chapterRecords' => $chapterRecords,
        'hasExperienceColumn' => true
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '數據庫操作失敗: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
?>
