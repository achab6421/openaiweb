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
$suggest_questions = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $input = isset($data['input']) ? $data['input'] : '';
    $system_prompt = isset($data['system_prompt']) ? $data['system_prompt'] : '';
    $suggest_questions = !empty($data['suggest_questions']);
}
if (!$input) {
    echo json_encode(['output' => '請輸入內容']);
    exit;
}

// 若有 system_prompt，包成 messages 格式
$messages = [];
if ($system_prompt) {
    $messages[] = ['role' => 'system', 'content' => $system_prompt];
}
$messages[] = ['role' => 'user', 'content' => $input];

// 若需要建議問題，加入 assistant 指令
if ($suggest_questions) {
    $messages[] = [
        'role' => 'assistant',
        'content' => '請根據上方題目與提問，額外產生 3 個使用者可能會問的相關問題，僅以 JSON 陣列輸出（如 ["問題1","問題2","問題3"]），不要有多餘說明。'
    ];
}

$payload = [
    'model' => 'gpt-4.1',
    'messages' => $messages
];
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
$suggested_questions = [];
if (
    isset($response['choices'][0]['message']['content']) &&
    $suggest_questions
) {
    // 嘗試解析最後一段 assistant 回覆為 JSON 陣列
    $content = $response['choices'][0]['message']['content'];
    if (preg_match('/\[(.*?)\]/s', $content, $m)) {
        $json = '[' . $m[1] . ']';
        $arr = json_decode($json, true);
        if (is_array($arr)) {
            $suggested_questions = $arr;
        }
    }
}

if (isset($response['choices'][0]['message']['content'])) {
    echo json_encode([
        'output' => $response['choices'][0]['message']['content'],
        'suggested_questions' => $suggested_questions
    ]);
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
