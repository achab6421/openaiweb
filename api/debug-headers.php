<?php
header('Content-Type: application/json; charset=utf-8');

// 記錄收到的所有頭部和請求內容
$headers = getallheaders();
$rawBody = file_get_contents('php://input');
$parsedBody = json_decode($rawBody, true);

// 返回調試信息
echo json_encode([
    'success' => true,
    'headers' => $headers,
    'raw_body' => $rawBody,
    'parsed_body' => $parsedBody,
    'server' => $_SERVER,
    'method' => $_SERVER['REQUEST_METHOD'],
    'ajax_header' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : 'not set'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
