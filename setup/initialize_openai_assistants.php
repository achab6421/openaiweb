<?php
// OpenAI 助手初始化工具

// 顯示所有錯誤
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 包含 OpenAI 配置檔案
require_once '../config/openai.php';

// 獲取 API 金鑰
$openai = new OpenAIConfig();
$openai->loadFromEnvironment();
$apiKey = $openai->getApiKey();

if (empty($apiKey)) {
    die("<h2>錯誤</h2><p>未設置 OpenAI API 金鑰。請在 .env 檔案中配置 OPENAI_API_KEY。</p>");
}

// 處理表單提交
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_problem_generator'])) {
        $result = createProblemGeneratorAssistant($apiKey);
        if (isset($result['id'])) {
            $message = "題目生成助手創建成功！ID: " . $result['id'];
        } else {
            $message = "創建題目生成助手失敗: " . json_encode($result);
        }
    } elseif (isset($_POST['create_solution_validator'])) {
        $result = createSolutionValidatorAssistant($apiKey);
        if (isset($result['id'])) {
            $message = "解答驗證助手創建成功！ID: " . $result['id'];
        } else {
            $message = "創建解答驗證助手失敗: " . json_encode($result);
        }
    }
}

/**
 * 創建問題生成助手
 */
function createProblemGeneratorAssistant($apiKey) {
    // 從系統指令檔案讀取指令
    $instructions = file_get_contents(__DIR__ . '/../docs/openai_system_instructions.md');
    
    // 提取題目生成助手指令
    preg_match('/## Python 怪物村 - 題目生成助手指令\s*```(.*?)```/s', $instructions, $matches);
    $instructions = isset($matches[1]) ? trim($matches[1]) : 'Generate Python programming problems for beginners.';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/assistants",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "gpt-4-1106-preview",
            "name" => "Python 怪物村題目生成助手",
            "description" => "根據教學重點生成適合初學者的 Python 程式練習題",
            "instructions" => $instructions,
            "tools" => [{"type" => "code_interpreter"}]
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v2"  // 更新為 v2
        ],
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ["error" => $error];
    }
    
    return json_decode($response, true);
}

/**
 * 創建解答驗證助手
 */
function createSolutionValidatorAssistant($apiKey) {
    // 從系統指令檔案讀取指令
    $instructions = file_get_contents(__DIR__ . '/../docs/openai_system_instructions.md');
    
    // 提取解答驗證助手指令
    preg_match('/## Python 怪物村 - 解答驗證助手指令\s*```(.*?)```/s', $instructions, $matches);
    $instructions = isset($matches[1]) ? trim($matches[1]) : 'Validate Python code solutions submitted by beginners.';
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.openai.com/v1/assistants",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "gpt-4-1106-preview",
            "name" => "Python 怪物村解答驗證助手",
            "description" => "評估學生Python程式解答是否正確並提供反饋",
            "instructions" => $instructions,
            "tools" => [{"type" => "code_interpreter"}]
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v2"  // 更新為 v2
        ],
    ]);
    
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        return ["error" => $error];
    }
    
    return json_decode($response, true);
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenAI 助手初始化工具</title>
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .message {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin-bottom: 20px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            margin-right: 10px;
            border-radius: 4px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
        .info {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>OpenAI 助手初始化工具</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="info">
            <p>此工具將使用您的 OpenAI API 金鑰創建兩個助手：</p>
            <ol>
                <li><strong>題目生成助手</strong> - 根據教學重點生成 Python 練習題</li>
                <li><strong>解答驗證助手</strong> - 評估學生提交的程式碼解答</li>
            </ol>
            <p>創建後，您需要將助手的 ID 複製到 .env 檔案中。</p>
        </div>
        
        <form method="post">
            <button type="submit" name="create_problem_generator" class="btn">建立題目生成助手</button>
            <button type="submit" name="create_solution_validator" class="btn">建立解答驗證助手</button>
        </form>
        
        <div class="info" style="margin-top: 20px;">
            <p>建立完成後，請將生成的助手 ID 複製到 .env 檔案中：</p>
            <pre>
OPENAI_PROBLEM_GENERATOR_ID=asst_YOUR_ID_HERE
OPENAI_SOLUTION_VALIDATOR_ID=asst_YOUR_ID_HERE</pre>
        </div>
        
        <p><a href="../index.php">返回首頁</a></p>
    </div>
</body>
</html>
