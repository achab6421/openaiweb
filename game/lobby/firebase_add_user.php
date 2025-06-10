<?php
// firebase_add_user.php

// 顯示錯誤（開發用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 取得暱稱（GET 傳入），若無就設為 anonymous
$username = $_GET['username'] ?? 'anonymous';

// ✅ Firebase Realtime Database 網址（注意結尾一定是 /）
$firebase_url = "https://openai-dbd3b-default-rtdb.asia-southeast1.firebasedatabase.app/";

// 要儲存的位置與檔名（注意結尾要 .json）
$endpoint = "room/members/" . urlencode($username) . ".json";

// 資料內容
$data = [
    'joinedAt' => time()
];

// 建立 HTTP PUT 請求
$options = [
    'http' => [
        'method'  => 'PUT',
        'header'  => "Content-Type: application/json\r\n",
        'content' => json_encode($data),
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($firebase_url . $endpoint, false, $context);

// 設定 JSON 回傳格式
header('Content-Type: application/json');

// ✅ 若請求失敗，回傳錯誤 JSON
if ($response === false) {
    echo json_encode([
        'success' => false,
        'error' => error_get_last()
    ]);
} else {
    echo $response;
}
?>