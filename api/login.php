<?php
// 處理用戶登入的API
session_start();
header('Content-Type: application/json');

// 接收POST資料
$data = json_decode(file_get_contents('php://input'), true);

// 驗證必要欄位
if (!isset($data['account']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => '請提供帳號和密碼']);
    exit;
}

// 包含資料庫連接檔案
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 準備查詢
    $query = "SELECT player_id, username, account, password, attack_power, base_hp, level FROM players WHERE account = ?";
    $stmt = $db->prepare($query);
    
    // 綁定參數
    $stmt->bindParam(1, $data['account']);
    
    // 執行查詢
    $stmt->execute();
    
    // 檢查是否存在此帳號
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 驗證密碼
        if (password_verify($data['password'], $row['password'])) {
            // 密碼正確，設置session
            $_SESSION['user_id'] = $row['player_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['attack_power'] = $row['attack_power'];
            $_SESSION['base_hp'] = $row['base_hp'];
            $_SESSION['level'] = $row['level'];
            $_SESSION['logged_in'] = true;
            
            echo json_encode([
                'success' => true, 
                'message' => '登入成功',
                'user' => [
                    'id' => $row['player_id'],
                    'username' => $row['username'],
                    'level' => $row['level']
                ]
            ]);
        } else {
            // 密碼不正確
            echo json_encode(['success' => false, 'message' => '密語不正確']);
        }
    } else {
        // 帳號不存在
        echo json_encode(['success' => false, 'message' => '帳號不存在']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '登入過程中發生錯誤：' . $e->getMessage()]);
}
?>
