<?php
// 處理用戶註冊的API
header('Content-Type: application/json');

// 接收POST資料
$data = json_decode(file_get_contents('php://input'), true);

// 驗證必要欄位
if (!isset($data['username']) || !isset($data['account']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => '請提供所有必要資訊']);
    exit;
}

// 包含資料庫連接檔案
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 檢查帳號是否已存在
    $query = "SELECT COUNT(*) as count FROM players WHERE account = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data['account']);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => '此帳號已被註冊']);
        exit;
    }
    
    // 檢查用戶名是否已存在
    $query = "SELECT COUNT(*) as count FROM players WHERE username = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $data['username']);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => '此獵人名稱已被使用']);
        exit;
    }
    
    // 密碼雜湊處理
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // 插入新用戶資料
    $query = "INSERT INTO players (username, account, password, attack_power, base_hp, level) VALUES (?, ?, ?, 10, 100, 1)";
    $stmt = $db->prepare($query);
    
    // 綁定參數
    $stmt->bindParam(1, $data['username']);
    $stmt->bindParam(2, $data['account']);
    $stmt->bindParam(3, $hashed_password);
    
    // 執行插入
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => '註冊成功！']);
    } else {
        echo json_encode(['success' => false, 'message' => '註冊失敗，請稍後再試']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '註冊過程中發生錯誤：' . $e->getMessage()]);
}
?>
