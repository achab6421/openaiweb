<?php
// ...existing code...

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
    $content = '';
    foreach ($messages[0]['content'] as $part) {
        if ($part['type'] === 'text') {
            $content .= $part['text']['value'];
        }
    }
    
    return $content;
}

function parseValidationResponse($response) {
    // 嘗試解析JSON格式的回應
    $result = [];
    
    try {
        // 尋找JSON格式的內容
        if (preg_match('/```json(.*?)```/s', $response, $matches)) {
            $jsonStr = $matches[1];
            $jsonData = json_decode($jsonStr, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['isCorrect'])) {
                $result = $jsonData;
            } else {
                throw new Exception("Invalid JSON format in response");
            }
        } else {
            // 嘗試從文本中提取結果
            $isCorrect = false;
            $feedback = '';
            $explanation = '';
            
            // 檢查結果是否正確
            if (stripos($response, 'correct') !== false && 
                (stripos($response, 'incorrect') === false || 
                 stripos($response, 'not correct') === false)) {
                $isCorrect = true;
            }
            
            // 提取回饋和解釋
            if (preg_match('/feedback:?\s*(.*?)(?:explanation|$)/is', $response, $matches)) {
                $feedback = trim($matches[1]);
            }
            
            if (preg_match('/explanation:?\s*(.*?)$/is', $response, $matches)) {
                $explanation = trim($matches[1]);
            }
            
            $result = [
                'isCorrect' => $isCorrect,
                'feedback' => $feedback ?: '您的程式碼已被評估',
                'explanation' => $explanation ?: '系統無法提供詳細解釋'
            ];
        }
    } catch (Exception $e) {
        error_log("Error parsing validation response: " . $e->getMessage());
        
        // 提供預設回應
        $result = [
            'isCorrect' => false,
            'feedback' => '系統無法判斷您的解答是否正確',
            'explanation' => '請檢查您的程式碼並再次嘗試。'
        ];
    }
    
    return $result;
}
?>