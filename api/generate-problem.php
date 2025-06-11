<?php
// 生成Python題目的API
header('Content-Type: application/json');

// 檢查是否為AJAX請求
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
    ]);
    exit;
}

// 檢查會話
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
    ]);
    exit;
}

// 解析請求數據
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['levelId']) || !is_numeric($data['levelId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid level ID',
    ]);
    exit;
}

$levelId = intval($data['levelId']);
$chapterId = isset($data['chapterId']) ? intval($data['chapterId']) : 0;
$teachingPoint = isset($data['teachingPoint']) ? $data['teachingPoint'] : '';

// 包含資料庫連接和OpenAI配置
require_once '../config/database.php';
require_once '../config/openai.php';

$database = new Database();
$db = $database->getConnection();

$openai = new OpenAIConfig();
$openai->loadFromEnvironment();

$apiKey = $openai->getApiKey();
$assistantId = $openai->getProblemGeneratorAssistantId();

if (empty($apiKey) || empty($assistantId)) {
    echo json_encode([
        'success' => false,
        'message' => 'OpenAI API configuration is incomplete',
    ]);
    exit;
}

// 獲取關卡和章節信息
$query = "SELECT l.*, c.chapter_name, c.summary, m.teaching_point 
          FROM levels l 
          JOIN chapters c ON l.chapter_id = c.chapter_id 
          JOIN monsters m ON l.monster_id = m.monster_id 
          WHERE l.level_id = ?";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $levelId);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Level not found',
    ]);
    exit;
}

$levelInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// 創建OpenAI API請求
$threadId = createThread($apiKey);
if (!$threadId) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create OpenAI thread',
    ]);
    exit;
}

// 添加消息到線程
$message = addMessage($apiKey, $threadId, $levelInfo);
if (!$message) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add message to thread',
    ]);
    exit;
}

// 執行助手以生成題目
$run = runAssistant($apiKey, $threadId, $assistantId);
if (!$run) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to run assistant',
    ]);
    exit;
}

// 輪詢結果
$problem = pollForCompletion($apiKey, $threadId, $run['id']);
if (!$problem) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get response from assistant',
    ]);
    exit;
}

// 返回生成的問題
echo json_encode([
    'success' => true,
    'problem' => $problem,
    'threadId' => $threadId
]);
exit;

// 以下是輔助函數

function createThread($apiKey) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/threads",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1"
        ],
    ]);
    
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl) || $statusCode != 200) {
        error_log("OpenAI API error (create thread): " . curl_error($curl) . " - Response: " . $response);
        curl_close($curl);
        return false;
    }
    
    curl_close($curl);
    $responseData = json_decode($response, true);
    
    return $responseData['id'] ?? false;
}

function addMessage($apiKey, $threadId, $levelInfo) {
    $content = "請為Python初學者生成一個程式練習題目。這個題目將用於我們的編程學習平台的關卡挑戰。

章節名稱：{$levelInfo['chapter_name']}
章節描述：{$levelInfo['summary']}
本關教學重點：{$levelInfo['teaching_point']}
關卡ID：{$levelInfo['level_id']}

請確保生成的題目：
1. 適合初學者理解
2. 難度適中，與教學重點相符
3. 包含明確的題目描述
4. 包含輸入和輸出示例
5. 有清晰的提示或提示

格式要求：
- 開始以markdown格式的標題「## 挑戰題目」
- 接著是問題描述
- 然後是輸入/輸出說明和示例
- 如果需要，可以提供一些提示
- 最後提供一段初始代碼框架，讓學生填充

生成的題目應該是具體的、可執行的Python程式，而非理論問題。";

    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/threads/{$threadId}/messages",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "role" => "user",
            "content" => $content
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1"
        ],
    ]);
    
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl) || $statusCode != 200) {
        error_log("OpenAI API error (add message): " . curl_error($curl) . " - Response: " . $response);
        curl_close($curl);
        return false;
    }
    
    curl_close($curl);
    $responseData = json_decode($response, true);
    
    return $responseData['id'] ?? false;
}

function runAssistant($apiKey, $threadId, $assistantId) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/threads/{$threadId}/runs",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "assistant_id" => $assistantId
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1"
        ],
    ]);
    
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl) || $statusCode != 200) {
        error_log("OpenAI API error (run assistant): " . curl_error($curl) . " - Response: " . $response);
        curl_close($curl);
        return false;
    }
    
    curl_close($curl);
    return json_decode($response, true);
}

function pollForCompletion($apiKey, $threadId, $runId) {
    $maxAttempts = 30;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $apiKey,
                "OpenAI-Beta: assistants=v1"
            ],
        ]);
        
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($statusCode != 200) {
            error_log("OpenAI API error (poll status): Status code: {$statusCode} - Response: {$response}");
            return false;
        }
        
        $responseData = json_decode($response, true);
        $status = $responseData['status'] ?? '';
        
        if ($status === 'completed') {
            // 獲取助手的回復
            return getAssistantResponse($apiKey, $threadId);
        } else if ($status === 'failed' || $status === 'expired' || $status === 'cancelled') {
            error_log("OpenAI run failed with status: {$status}");
            return false;
        }
        
        // 等待一段時間後再檢查
        $attempts++;
        sleep(1);
    }
    
    error_log("OpenAI polling timeout after {$maxAttempts} attempts");
    return false;
}

function getAssistantResponse($apiKey, $threadId) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/threads/{$threadId}/messages?limit=1&order=desc",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "OpenAI-Beta: assistants=v1"
        ],
    ]);
    
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if (curl_errno($curl) || $statusCode != 200) {
        error_log("OpenAI API error (get messages): " . curl_error($curl) . " - Response: " . $response);
        curl_close($curl);
        return false;
    }
    
    curl_close($curl);
    
    $responseData = json_decode($response, true);
    $messages = $responseData['data'] ?? [];
    
    if (empty($messages) || $messages[0]['role'] !== 'assistant') {
        return false;
    }
    
    // 組合所有內容部分
    $fullContent = '';
    foreach ($messages[0]['content'] as $contentPart) {
        if ($contentPart['type'] === 'text') {
            $fullContent .= $contentPart['text']['value'];
        }
    }
    
    return $fullContent;
}
?>
