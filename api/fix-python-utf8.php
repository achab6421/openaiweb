<?php
header('Content-Type: text/html; charset=utf-8');

// 強制顯示所有錯誤
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Python UTF-8 編碼修復測試</h1>";

// 創建臨時目錄
$temp_dir = 'C:/laragon/tmp/py_test_' . uniqid();
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

echo "使用臨時目錄: " . $temp_dir . "<br>";

// 創建包含中文字符的測試Python文件
$py_file = $temp_dir . '/test_utf8.py';
$test_code = <<<EOT
# -*- coding: utf-8 -*-
import sys
print("系統編碼設置:")
print("默認編碼:", sys.getdefaultencoding())
print("標準輸出編碼:", sys.stdout.encoding)
print("文件系統編碼:", sys.getfilesystemencoding())
print("\n測試中文輸出:")
print("你好，世界!")
print("這是一行中文測試")
print("繁體中文：測試，簡體中文：测试")
EOT;

// 使用UTF-8編碼寫入Python文件
file_put_contents($py_file, $test_code);
echo "已創建測試Python文件<br>";

// 定義要測試的不同執行方法
$methods = [
    'method1' => [
        'name' => '直接執行',
        'cmd' => 'py "' . $py_file . '"'
    ],
    'method2' => [
        'name' => '設置PYTHONIOENCODING',
        'cmd' => 'set PYTHONIOENCODING=utf-8 && py "' . $py_file . '"'
    ],
    'method3' => [
        'name' => '使用-u參數',
        'cmd' => 'py -u "' . $py_file . '"'
    ],
    'method4' => [
        'name' => '結合PYTHONIOENCODING和-u參數',
        'cmd' => 'set PYTHONIOENCODING=utf-8 && py -u "' . $py_file . '"'
    ],
    'method5' => [
        'name' => '通過文件重定向',
        'cmd' => 'py "' . $py_file . '" > "' . $temp_dir . '/output.txt"',
        'readFile' => $temp_dir . '/output.txt'
    ]
];

// 測試各種方法並顯示結果
echo "<h2>測試結果</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>方法</th><th>命令</th><th>輸出結果</th></tr>";

foreach ($methods as $key => $method) {
    echo "<tr>";
    echo "<td>" . $method['name'] . "</td>";
    echo "<td>" . htmlspecialchars($method['cmd']) . "</td>";
    
    // 執行命令
    $output = [];
    $return_var = 0;
    
    // 設置UTF-8代碼頁
    exec('chcp 65001', $dummy);
    exec($method['cmd'], $output, $return_var);
    
    // 如果需要從文件讀取輸出
    if (isset($method['readFile']) && file_exists($method['readFile'])) {
        $output = [file_get_contents($method['readFile'])];
        // 嘗試不同的編碼轉換
        $output[] = "\n--- 使用 mb_convert_encoding: ---\n";
        $output[] = mb_convert_encoding($output[0], 'UTF-8', 'auto');
        $output[] = "\n--- 使用 iconv: ---\n";
        $output[] = iconv('GBK', 'UTF-8//IGNORE', $output[0]);
    }
    
    echo "<td><pre>";
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre></td>";
    
    echo "</tr>";
}

echo "</table>";

// 測試 base64 編碼傳輸方法
echo "<h2>Base64 傳輸方法測試</h2>";

$base64_test = $temp_dir . '/base64_test.py';
$base64_code = <<<EOT
# -*- coding: utf-8 -*-
import base64

print("====BASE64_OUTPUT_START====")
print(base64.b64encode("你好，世界！這是一個中文測試".encode('utf-8')).decode('utf-8'))
print("====BASE64_OUTPUT_END====")
EOT;

file_put_contents($base64_test, $base64_code);

exec('chcp 65001 && py "' . $base64_test . '"', $base64_output);

echo "<pre>";
foreach ($base64_output as $line) {
    if (strpos($line, "====BASE64_OUTPUT_START====") !== false) {
        continue;
    }
    if (strpos($line, "====BASE64_OUTPUT_END====") !== false) {
        continue;
    }
    
    echo "Base64 編碼: " . $line . "<br>";
    echo "解碼結果: " . base64_decode($line) . "<br>";
}
echo "</pre>";

// 建議解決方案
echo "<h2>推薦解決方案</h2>";
echo "<p>根據以上測試結果，修改 executePythonCode 函數使用最有效的方法處理中文輸出。</p>";

// 清理臨時文件
function cleanupDir($dir) {
    if (file_exists($dir)) {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }
}

register_shutdown_function(function() use ($temp_dir) {
    cleanupDir($temp_dir);
});
?>
