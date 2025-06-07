<?php
/**
 * OpenAI API 整合助手
 * 負責處理與OpenAI API通訊的功能
 */

// 載入環境變數
$env_file = dirname(__DIR__) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '//') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), '"\'');
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * 創建或獲取OpenAI助手的對話執行緒
 * 
 * @param string $user_id 用戶ID
 * @return string 執行緒ID
 */
function createOrGetThread($user_id) {
    $thread_file = dirname(__DIR__) . '/temp/threads/' . $user_id . '_thread.txt';
    
    // 確保目錄存在
    if (!file_exists(dirname($thread_file))) {
        mkdir(dirname($thread_file), 0777, true);
    }
    
    if (file_exists($thread_file)) {
        return file_get_contents($thread_file);
    }
    
    // 創建新執行緒
    $api_key = getenv('OPENAI_API_KEY');
    $url = 'https://api.openai.com/v1/threads';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'OpenAI-Beta: assistants=v1'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $thread_data = json_decode($response, true);
    if (isset($thread_data['id'])) {
        file_put_contents($thread_file, $thread_data['id']);
        return $thread_data['id'];
    }
    
    return null;
}

/**
 * 向OpenAI助手添加新消息
 * 
 * @param string $thread_id 執行緒ID
 * @param string $message 要發送的消息
 * @return string 消息ID
 */
function addMessageToThread($thread_id, $message) {
    $api_key = getenv('OPENAI_API_KEY');
    $url = "https://api.openai.com/v1/threads/{$thread_id}/messages";
    
    $data = [
        'role' => 'user',
        'content' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'OpenAI-Beta: assistants=v1'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $message_data = json_decode($response, true);
    if (isset($message_data['id'])) {
        return $message_data['id'];
    }
    
    return null;
}

/**
 * 運行OpenAI助手執行處理回答
 * 
 * @param string $thread_id 執行緒ID
 * @return string 運行ID
 */
function runAssistant($thread_id) {
    $api_key = getenv('OPENAI_API_KEY');
    $assistant_id = getenv('OPENAI_ASSISTANT_ID');
    $url = "https://api.openai.com/v1/threads/{$thread_id}/runs";
    
    $data = [
        'assistant_id' => $assistant_id
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'OpenAI-Beta: assistants=v1'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $run_data = json_decode($response, true);
    if (isset($run_data['id'])) {
        return $run_data['id'];
    }
    
    return null;
}

/**
 * 檢查助手運行狀態
 * 
 * @param string $thread_id 執行緒ID
 * @param string $run_id 運行ID
 * @return string 運行狀態
 */
function checkRunStatus($thread_id, $run_id) {
    $api_key = getenv('OPENAI_API_KEY');
    $url = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'OpenAI-Beta: assistants=v1'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $status_data = json_decode($response, true);
    if (isset($status_data['status'])) {
        return $status_data['status'];
    }
    
    return 'unknown';
}

/**
 * 獲取助手回應消息
 * 
 * @param string $thread_id 執行緒ID
 * @return array 消息列表
 */
function getMessages($thread_id) {
    $api_key = getenv('OPENAI_API_KEY');
    $url = "https://api.openai.com/v1/threads/{$thread_id}/messages";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'OpenAI-Beta: assistants=v1'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $messages_data = json_decode($response, true);
    if (isset($messages_data['data'])) {
        return $messages_data['data'];
    }
    
    return [];
}

/**
 * 生成假網頁與隱藏答案
 * 
 * @param string $theme 主題
 * @param string $difficulty 難度
 * @return array 生成的網頁內容和隱藏答案
 */
function generateFakeWebpage($theme, $difficulty) {
    // 根據用戶ID獲取執行緒
    $thread_id = createOrGetThread('web_generator');
    
    // 添加消息到執行緒
    $prompt = "你需要生成一個假網站的HTML代碼，主題是關於{$theme}。這個網站應該包含以下內容：
    1. 看起來像真實的網站，有標題、導航、內容區域
    2. 包含多個可能的答案線索
    3. 隱藏{$difficulty}難度的真實答案在HTML中 (越難的難度應該讓答案越隱蔽)
    4. 真實答案應該是一個12位的英文數字混合字符串，格式為 ans_XXXXXX，藏在HTML的某個地方
    
    請只回復HTML代碼和真實答案。格式如下：
    
    HTML代碼：
    ```html
    (你生成的HTML代碼)
    ```
    
    答案：ans_XXXXXX";
    
    addMessageToThread($thread_id, $prompt);
    
    // 運行助手
    $run_id = runAssistant($thread_id);
    
    // 等待助手完成生成
    $status = 'queued';
    while ($status == 'queued' || $status == 'in_progress') {
        sleep(1);
        $status = checkRunStatus($thread_id, $run_id);
    }
    
    // 獲取助手回應
    $messages = getMessages($thread_id);
    $response = '';
    
    if (!empty($messages) && isset($messages[0]['content'][0]['text'])) {
        $response = $messages[0]['content'][0]['text']['value'];
    }
    
    // 解析回應
    $html = '';
    $answer = '';
    
    if (preg_match('/```html(.*?)```/s', $response, $html_matches)) {
        $html = trim($html_matches[1]);
    }
    
    if (preg_match('/答案：(ans_[a-zA-Z0-9]{6,})/s', $response, $answer_matches)) {
        $answer = $answer_matches[1];
    }
    
    return [
        'html' => $html,
        'answer' => $answer
    ];
}
?>
