<?php
// 評估Python答案的API
header('Content-Type: application/json; charset=utf-8'); // 明確指定 UTF-8 編碼

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

// 檢查請求數據的完整性
if (!isset($data['levelId']) || !is_numeric($data['levelId']) || 
    !isset($data['userCode']) || empty($data['userCode']) ||
    !isset($data['threadId']) || empty($data['threadId']) ||
    !isset($data['problemStatement']) || empty($data['problemStatement'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data fields',
    ]);
    exit;
}

$levelId = intval($data['levelId']);
$userCode = $data['userCode'];
$threadId = $data['threadId'];
$problemStatement = $data['problemStatement'];

// 包含資料庫連接和OpenAI配置
require_once '../config/database.php';
require_once '../config/openai.php';

$database = new Database();
$db = $database->getConnection();

$openai = new OpenAIConfig();
$openai->loadFromEnvironment();

$apiKey = $openai->getApiKey();
$assistantId = $openai->getCodeEvaluationAssistantId();

if (empty($apiKey) || empty($assistantId)) {
    echo json_encode([
        'success' => false,
        'message' => 'OpenAI API configuration is incomplete',
    ]);
    exit;
}

// 添加評估用的消息到thread
$message = addEvaluationMessage($apiKey, $threadId, $problemStatement, $userCode);
if (!$message) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add evaluation message to thread',
    ]);
    exit;
}

// 執行助手以評估代碼
$run = runAssistant($apiKey, $threadId, $assistantId);
if (!$run) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to run assistant',
    ]);
    exit;
}

// 輪詢結果
$evaluation = pollForCompletion($apiKey, $threadId, $run['id']);
if (!$evaluation) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get response from assistant',
    ]);
    exit;
}

// 如果用戶程式碼通過測試，更新數據庫中的進度
$isCorrect = checkIfCorrect($evaluation);
if ($isCorrect) {
    try {
        updateUserProgress($db, $levelId, $_SESSION['user_id']);
    } catch (PDOException $e) {
        // 捕獲資料庫錯誤但不中斷流程
        error_log("資料庫更新進度錯誤: " . $e->getMessage());
        // 繼續處理，不讓資料庫錯誤影響評估結果的返回
    }
}

// 返回評估結果
echo json_encode([
    'success' => true,
    'evaluation' => $evaluation,
    'isCorrect' => $isCorrect
], JSON_UNESCAPED_UNICODE);
exit;

// 以下是輔助函數

function addEvaluationMessage($apiKey, $threadId, $problemStatement, $userCode) {
    $content = "請評估以下Python代碼是否正確解決了問題。

問題描述:
{$problemStatement}

用戶提交的代碼:
```python
{$userCode}
```

請評估代碼的正確性，效率及可讀性。請先執行代碼測試，確保能正確通過所有測試用例。評估內容包括:
1. 代碼是否能正確解決問題
2. 代碼是否有語法錯誤
3. 邏輯是否正確
4. 代碼效率如何
5. 可讀性和風格是否良好

請明確表示代碼是'正確'還是'不正確'，如果不正確，請說明原因。格式如下：
- 評估結果: [正確/不正確]
- 詳細分析: [您的分析]
- 改進建議: [如果有的話]

如果代碼正確，你的回應必須包含「評估結果: 正確」。";

    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/threads/{$threadId}/messages",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "role" => "user",
            "content" => $content
        ], JSON_UNESCAPED_UNICODE), // 確保中文字符正確編碼
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json; charset=utf-8", // 指定UTF-8編碼
            "OpenAI-Beta: assistants=v2"
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
        ], JSON_UNESCAPED_UNICODE), // 確保 JSON 編碼時保留 Unicode 字符
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json; charset=utf-8", // 指定 UTF-8 編碼
            "OpenAI-Beta: assistants=v2"
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
                "OpenAI-Beta: assistants=v2"
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
            "OpenAI-Beta: assistants=v2"
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
    
    if (isset($responseData['data']) && !empty($responseData['data'])) {
        $message = $responseData['data'][0];
        if ($message['role'] === 'assistant') {
            $content = '';
            foreach ($message['content'] as $contentItem) {
                if ($contentItem['type'] === 'text') {
                    $content .= $contentItem['text']['value'];
                }
            }
            return $content;
        }
    }
    
    return false;
}

function checkIfCorrect($evaluation) {
    // 檢查評估結果是否包含"評估結果: 正確"，注意可能有不同版本的空格和標點
    $patterns = [
        '評估結果: 正確',
        '評估結果：正確',
        '評估結果:正確',
        '評估結果：正確',
        '正確的答案',
        '答案正確',
        '答案是正確的'
    ];
    
    foreach ($patterns as $pattern) {
        if (mb_strpos($evaluation, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

function updateUserProgress($db, $levelId, $userId) {
    try {
        // 檢查表格是否存在
        $tableExists = false;
        try {
            $checkTable = $db->query("SHOW TABLES LIKE 'user_progress'");
            $tableExists = ($checkTable->rowCount() > 0);
        } catch (PDOException $e) {
            // 表格檢查失敗，假設表格不存在
            $tableExists = false;
        }
        
        // 如果表格不存在，創建它
        if (!$tableExists) {
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `user_progress` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` varchar(50) NOT NULL,
                `level_id` int(11) NOT NULL,
                `completed_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_level_idx` (`user_id`,`level_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $db->exec($createTableSQL);
            error_log("自動創建 user_progress 表格");
        }
        
        // 正常的進度更新流程
        $checkQuery = "SELECT * FROM user_progress WHERE user_id = ? AND level_id = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(1, $userId);
        $checkStmt->bindParam(2, $levelId);
        $checkStmt->execute();
        
        // 如果未完成，則記錄完成狀態
        if ($checkStmt->rowCount() == 0) {
            $insertQuery = "INSERT INTO user_progress (user_id, level_id, completed_at) VALUES (?, ?, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(1, $userId);
            $insertStmt->bindParam(2, $levelId);
            $insertStmt->execute();
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("無法更新用戶進度: " . $e->getMessage());
        // 在測試環境中，我們可以允許這個錯誤
        return false;
    }
}

