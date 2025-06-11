<?php
// API響應調試工具
header('Content-Type: text/html; charset=utf-8');

echo "<h1>API 調試工具</h1>";

echo "<h2>PHP 版本與模塊</h2>";
echo "<pre>";
echo "PHP 版本: " . phpversion() . "\n";
echo "已加載的模塊:\n";
print_r(get_loaded_extensions());
echo "</pre>";

echo "<h2>cURL 信息</h2>";
echo "<pre>";
if (function_exists('curl_version')) {
    $curl_info = curl_version();
    print_r($curl_info);
    echo "cURL 可用\n";
} else {
    echo "cURL 不可用\n";
}
echo "</pre>";

echo "<h2>會話設置</h2>";
echo "<pre>";
echo "Session 保存路徑: " . session_save_path() . "\n";
echo "Session 狀態: " . (session_status() === PHP_SESSION_ACTIVE ? "活躍" : "非活躍") . "\n";
echo "</pre>";

echo "<h2>環境變數</h2>";
echo "<pre>";
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    echo "發現 .env 文件\n";
    echo "文件大小: " . filesize($env_file) . " 字節\n";
    echo "文件權限: " . substr(sprintf('%o', fileperms($env_file)), -4) . "\n";
} else {
    echo ".env 文件不存在於 " . $env_file . "\n";
}

if (class_exists('OpenAIConfig')) {
    try {
        require_once '../config/openai.php';
        $openai = new OpenAIConfig();
        $openai->loadFromEnvironment();
        
        echo "OpenAI 配置加載結果:\n";
        echo "API 金鑰存在: " . (!empty($openai->getApiKey()) ? "是" : "否") . "\n";
        echo "題目生成助手 ID 存在: " . (!empty($openai->getProblemGeneratorAssistantId()) ? "是" : "否") . "\n";
        echo "解答驗證助手 ID 存在: " . (!empty($openai->getSolutionValidatorAssistantId()) ? "是" : "否") . "\n";
    } catch (Exception $e) {
        echo "加載 OpenAIConfig 時出錯: " . $e->getMessage() . "\n";
    }
} else {
    echo "OpenAIConfig 類不存在\n";
}
echo "</pre>";

echo "<h2>資料庫連接測試</h2>";
echo "<pre>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "資料庫連接: 成功\n";
    
    // 嘗試執行簡單查詢
    $query = "SELECT COUNT(*) as count FROM levels";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "關卡表中的記錄數: " . $row['count'] . "\n";
    
} catch (Exception $e) {
    echo "資料庫連接錯誤: " . $e->getMessage() . "\n";
}
echo "</pre>";

echo "<h2>PHP 錯誤記錄</h2>";
echo "<pre>";
$log_file = ini_get('error_log');
echo "錯誤日誌位置: " . $log_file . "\n";

if (file_exists($log_file)) {
    echo "日誌文件大小: " . filesize($log_file) . " 字節\n";
    echo "最近的錯誤 (最後 20 行):\n";
    
    $log_content = file($log_file);
    $last_lines = array_slice($log_content, -20);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "找不到錯誤日誌文件\n";
}
echo "</pre>";

echo "<p>此工具幫助診斷 API 響應問題。查看上面的信息以識別問題區域。</p>";
?>
