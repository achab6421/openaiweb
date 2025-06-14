<?php
// 測試升級功能的API端點
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
if (!isset($data['expAmount']) || !is_numeric($data['expAmount'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid exp amount',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$expAmount = intval($data['expAmount']);
$userId = intval($_SESSION['user_id']); // 確保使用整數類型

// 包含資料庫連接
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 首先確保測試玩家存在
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

    // 檢查 experience 列是否存在
    $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
    $checkColumnStmt = $db->query($checkColumnQuery);
    $experienceColumnExists = ($checkColumnStmt->rowCount() > 0);
    
    if (!$experienceColumnExists) {
        // 如果列不存在，則添加它
        $addColumnQuery = "ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0";
        $db->exec($addColumnQuery);
        error_log("Added missing 'experience' column to players table");
    }
    
    // 獲取玩家當前等級和經驗值
    $playerQuery = "SELECT player_id, level, attack_power, base_hp FROM players WHERE player_id = ?";
    $playerStmt = $db->prepare($playerQuery);
    $playerStmt->execute([$userId]);
    $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        echo json_encode([
            'success' => false,
            'message' => '找不到玩家數據',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 獲取當前經驗值
    $currentExp = 0;
    try {
        $expQuery = "SELECT experience FROM players WHERE player_id = ?";
        $expStmt = $db->prepare($expQuery);
        $expStmt->execute([$userId]);
        $expData = $expStmt->fetch(PDO::FETCH_ASSOC);
        if ($expData && isset($expData['experience'])) {
            $currentExp = intval($expData['experience']);
        }
    } catch (PDOException $e) {
        // 如果查詢失敗，假設經驗值為0
        error_log("Error getting experience: " . $e->getMessage());
    }
    
    $currentLevel = intval($player['level']);
    $newExp = $currentExp + $expAmount;
    
    // 檢查是否需要升級 (簡單公式: 每級所需經驗值為 level * 100)
    $expToNextLevel = $currentLevel * 100;
    $levelUp = false;
    $newLevel = $currentLevel;
    
    // 如果經驗值足夠，升級玩家
    if ($newExp >= $expToNextLevel) {
        $newLevel = $currentLevel + 1;
        $levelUp = true;
        
        // 計算新的攻擊力和HP (每級增加5%的基礎值)
        $newAttackPower = ceil($player['attack_power'] * 1.05);
        $newBaseHp = ceil($player['base_hp'] * 1.05);
        
        try {
            // 更新玩家數據
            $updateQuery = "UPDATE players SET level = ?, attack_power = ?, base_hp = ? WHERE player_id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$newLevel, $newAttackPower, $newBaseHp, $userId]);
            
            // 嘗試更新經驗值
            $updateExpQuery = "UPDATE players SET experience = ? WHERE player_id = ?";
            $updateExpStmt = $db->prepare($updateExpQuery);
            $updateExpStmt->execute([($newExp - $expToNextLevel), $userId]);
        } catch (PDOException $e) {
            error_log("Error updating player stats: " . $e->getMessage());
            throw $e;
        }
        
        // 更新會話數據
        $_SESSION['level'] = $newLevel;
        $_SESSION['attack_power'] = $newAttackPower;
        $_SESSION['base_hp'] = $newBaseHp;
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
    
    // 返回結果
    echo json_encode([
        'success' => true,
        'message' => '經驗值增加成功',
        'expAmount' => $expAmount,
        'levelUp' => $levelUp,
        'newLevel' => $newLevel,
        'currentExp' => $levelUp ? $newExp - $expToNextLevel : $newExp,
        'expToNextLevel' => $levelUp ? $newLevel * 100 : $expToNextLevel
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Error in test-level-up.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '數據庫操作失敗: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("General error in test-level-up.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '操作失敗: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
?>
