<?php
// 創建OpenAI對話的API
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

// 包含 OpenAI 配置
require_once '../config/openai.php';

$openai = new OpenAIConfig();
$openai->loadFromEnvironment();

$apiKey = $openai->getApiKey();

if (empty($apiKey)) {
    echo json_encode([
        'success' => false,
        'message' => 'OpenAI API configuration is incomplete',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 創建一個新的 thread
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.openai.com/v1/threads",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json",
        "OpenAI-Beta: assistants=v2"
    ],
]);

$response = curl_exec($curl);
$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if (curl_errno($curl) || $statusCode != 200) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create OpenAI thread',
        'statusCode' => $statusCode,
        'response' => json_decode($response, true)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

curl_close($curl);
$responseData = json_decode($response, true);

echo json_encode([
    'success' => true,
    'threadId' => $responseData['id'],
    'responseData' => $responseData
], JSON_UNESCAPED_UNICODE);
?>
