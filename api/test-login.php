<?php
// 測試專用登入 API - 僅用於開發環境
header('Content-Type: application/json; charset=utf-8');

// 檢查是否為AJAX請求
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 解析請求數據
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => '缺少用戶名或密碼',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$username = $data['username'];
$password = $data['password'];

// 測試環境下的簡單驗證 - 生產環境中請使用資料庫驗證
if ($username === 'testuser' && $password === 'password') {
    // 設置會話變數
    session_start();
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = '999';  // 測試用戶ID
    $_SESSION['username'] = $username;
    $_SESSION['attack_power'] = 10;
    $_SESSION['base_hp'] = 100;
    $_SESSION['level'] = 1;
    
    echo json_encode([
        'success' => true,
        'message' => '登入成功',
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'message' => '使用者名稱或密碼錯誤',
    ], JSON_UNESCAPED_UNICODE);
}
