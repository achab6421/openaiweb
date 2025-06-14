<?php
header('Content-Type: text/html; charset=utf-8');

// 測試中文輸出
echo "<h1>中文輸出測試</h1>";
echo "這是一行中文文字<br>";
echo "English text<br>";

// 測試 Python 輸出
$cmd = "py -c \"print('你好，世界!')\"";
$output = [];
exec($cmd, $output, $return_var);

echo "<h2>Python 輸出</h2>";
echo "命令: " . htmlspecialchars($cmd) . "<br>";
echo "返回值: $return_var<br>";
echo "輸出: <pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";

// 測試臨時目錄寫入
echo "<h2>臨時目錄測試</h2>";

// 嘗試不同位置
$locations = [
    'C:/laragon/tmp',
    dirname(__FILE__) . '/../temp',
    sys_get_temp_dir()
];

foreach ($locations as $location) {
    echo "測試位置: " . htmlspecialchars($location) . " - ";
    
    if (!file_exists($location)) {
        echo "嘗試創建... ";
        $result = @mkdir($location, 0777, true);
        if ($result) {
            echo "成功創建<br>";
        } else {
            echo "創建失敗<br>";
            continue;
        }
    }
    
    if (is_writable($location)) {
        echo "可寫入! ";
        $test_file = $location . '/test_' . uniqid() . '.txt';
        if (file_put_contents($test_file, '中文測試內容')) {
            echo "寫入測試文件成功";
            @unlink($test_file);
        } else {
            echo "寫入測試文件失敗";
        }
        echo "<br>";
    } else {
        echo "不可寫入<br>";
    }
}
