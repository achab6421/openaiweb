<?php
// 本地 proxy，安全地呼叫 OpenAI API
header('Content-Type: application/json');
$dotenv_path = __DIR__ . '/.env';
$env = [];
if (file_exists($dotenv_path)) {
    $lines = file($dotenv_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value, "\"' ");
        }
    }
}
$OPENAI_API_KEY = isset($env['OPENAI_API_KEY']) ? $env['OPENAI_API_KEY'] : '';
if (!$OPENAI_API_KEY) {
    echo json_encode(['output' => '未設定 OPENAI_API_KEY']);
    exit;
}
$input = '';
$system_prompt = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $input = isset($data['input']) ? $data['input'] : '';
    $system_prompt = isset($data['system_prompt']) ? $data['system_prompt'] : '';
}
if (!$input) {
    echo json_encode(['output' => '請輸入內容']);
    exit;
}

// 若有 system_prompt，包成 messages 格式
if ($system_prompt) {
    $payload = [
        'model' => 'gpt-4.1',
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $input]
        ]
    ];
} else {
    $payload = [
        'model' => 'gpt-4.1',
        'input' => $input
    ];
}
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $OPENAI_API_KEY
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$result = curl_exec($ch);
$curl_errno = curl_errno($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curl_errno) {
    echo json_encode(['output' => 'curl 錯誤: ' . $curl_error]);
    exit;
}

$response = json_decode($result, true);
// 支援 chat/completions 格式
if (isset($response['choices'][0]['message']['content'])) {
    echo json_encode(['output' => $response['choices'][0]['message']['content']]);
} elseif (isset($response['output'])) {
    echo json_encode(['output' => $response['output']]);
} else {
    echo json_encode([
        'output' => 'AI 回覆失敗，請稍後再試。',
        'debug' => [
            'http_code' => $http_code,
            'raw' => $result,
            'response' => $response
        ]
    ]);
}
