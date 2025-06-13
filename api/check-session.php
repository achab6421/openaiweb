<?php
header('Content-Type: application/json; charset=utf-8');

// 開始或恢復會話
session_start();

// 檢查用戶是否登入
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// 返回會話狀態
echo json_encode([
    'success' => true,
    'logged_in' => $isLoggedIn,
    'user_id' => $userId,
    'username' => $username,
    'session_id' => session_id(),
    'session_data' => array_keys($_SESSION),
    'php_session_path' => session_save_path(),
    'cookies' => $_COOKIE
], JSON_UNESCAPED_UNICODE);
