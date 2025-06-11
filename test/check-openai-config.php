<?php
// OpenAI 配置檢查工具
header('Content-Type: text/html; charset=utf-8');

echo "<h1>OpenAI 配置檢查工具</h1>";

// 載入 OpenAI 配置
require_once '../config/openai.php';

echo "<h2>環境變數檢查</h2>";
echo "<pre>";

// 檢查 .env 檔案
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    echo ".env 檔案存在，大小: " . filesize($envFile) . " 字節\n";
    echo "檔案內容預覽 (隱藏 API 金鑰):\n";
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // 隱藏 API 金鑰
        if (strpos($line, 'API_KEY') !== false) {
            $parts = explode('=', $line, 2);
            if (count($parts) > 1) {
                echo $parts[0] . "=[已隱藏]\n";
            } else {
                echo $line . "\n";
            }
        } else {
            echo $line . "\n";
        }
    }
} else {
    echo ".env 檔案不存在於預期位置\n";
}

echo "</pre>";

echo "<h2>OpenAI 配置載入結果</h2>";
echo "<pre>";

try {
    $openai = new OpenAIConfig();
    $openai->loadFromEnvironment();
    
    // 檢查 API 金鑰
    $apiKey = $openai->getApiKey();
    if (!empty($apiKey)) {
        $maskedKey = substr($apiKey, 0, 5) . '...' . substr($apiKey, -5);
        echo "API 金鑰: " . $maskedKey . " (長度: " . strlen($apiKey) . ")\n";
    } else {
        echo "API 金鑰: 未設置\n";
    }
    
    // 檢查助手 ID
    $problemGenId = $openai->getProblemGeneratorAssistantId();
    echo "題目生成助手 ID: " . ($problemGenId ?: "未設置") . "\n";
    
    $solutionValId = $openai->getSolutionValidatorAssistantId();
    echo "解答驗證助手 ID: " . ($solutionValId ?: "未設置") . "\n";
    
} catch (Exception $e) {
    echo "載入 OpenAI 配置時發生錯誤: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h2>OpenAI API 連接測試</h2>";
echo "<pre>";

try {
    $openai = new OpenAIConfig();
    $openai->loadFromEnvironment();
    $apiKey = $openai->getApiKey();
    
    if (empty($apiKey)) {
        echo "無法進行連接測試: API 金鑰未設置\n";
    } else {
        // 測試創建 Thread
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
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            echo "cURL 錯誤: " . curl_error($curl) . "\n";
        } else {
            echo "HTTP 響應碼: " . $httpCode . "\n";
            if ($httpCode == 200) {
                echo "API 連接測試成功!\n";
                $responseData = json_decode($response, true);
                echo "Thread ID: " . $responseData['id'] . "\n";
            } else {
                echo "API 響應: " . $response . "\n";
                
                // 檢查是否為認證問題
                $responseData = json_decode($response, true);
                if ($httpCode == 401 || (isset($responseData['error']) && $responseData['error']['type'] == 'invalid_request_error')) {
                    echo "\n可能的解決方案:\n";
                    echo "1. 確認 API 金鑰是否正確\n";
                    echo "2. 檢查 API 金鑰是否已過期\n";
                    echo "3. 檢查您的 OpenAI 帳戶餘額\n";
                }
            }
        }
        
        curl_close($curl);
    }
} catch (Exception $e) {
    echo "測試時發生錯誤: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h2>檢查助手是否存在</h2>";
echo "<pre>";

try {
    $openai = new OpenAIConfig();
    $openai->loadFromEnvironment();
    $apiKey = $openai->getApiKey();
    $problemGenId = $openai->getProblemGeneratorAssistantId();
    $solutionValId = $openai->getSolutionValidatorAssistantId();
    
    if (empty($apiKey)) {
        echo "無法檢查助手: API 金鑰未設置\n";
    } else {
        // 檢查題目生成助手
        if (!empty($problemGenId)) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.openai.com/v1/assistants/" . $problemGenId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $apiKey,
                    "OpenAI-Beta: assistants=v1"
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            echo "題目生成助手檢查結果:\n";
            echo "HTTP 響應碼: " . $httpCode . "\n";
            
            if ($httpCode == 200) {
                echo "助手存在且可訪問\n";
                $responseData = json_decode($response, true);
                echo "助手名稱: " . $responseData['name'] . "\n";
                echo "使用模型: " . $responseData['model'] . "\n";
            } else {
                echo "無法訪問此助手，響應: " . $response . "\n";
            }
            
            curl_close($curl);
        } else {
            echo "題目生成助手 ID 未設置，無法檢查\n";
        }
        
        echo "\n";
        
        // 檢查解答驗證助手
        if (!empty($solutionValId)) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.openai.com/v1/assistants/" . $solutionValId,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer " . $apiKey,
                    "OpenAI-Beta: assistants=v1"
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            echo "解答驗證助手檢查結果:\n";
            echo "HTTP 響應碼: " . $httpCode . "\n";
            
            if ($httpCode == 200) {
                echo "助手存在且可訪問\n";
                $responseData = json_decode($response, true);
                echo "助手名稱: " . $responseData['name'] . "\n";
                echo "使用模型: " . $responseData['model'] . "\n";
            } else {
                echo "無法訪問此助手，響應: " . $response . "\n";
            }
            
            curl_close($curl);
        } else {
            echo "解答驗證助手 ID 未設置，無法檢查\n";
        }
    }
} catch (Exception $e) {
    echo "檢查助手時發生錯誤: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<p>此工具幫助您確認 OpenAI 配置是否正確設置。使用上述信息排除可能的問題。</p>";
?>
