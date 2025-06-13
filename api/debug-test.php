<?php
header('Content-Type: text/html; charset=utf-8');

// 強制顯示所有錯誤
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP與Python環境診斷</h1>";

// PHP版本
echo "<h2>PHP資訊</h2>";
echo "PHP 版本: " . phpversion() . "<br>";
echo "作業系統: " . PHP_OS . "<br>";

// PHP執行命令能力
echo "<h2>執行命令功能</h2>";
if (function_exists('exec')) {
    echo "exec() 函數可用<br>";
    $output = [];
    exec("echo 測試命令", $output);
    echo "執行 'echo 測試命令' 結果: " . implode(', ', $output) . "<br>";
} else {
    echo "<span style='color:red'>exec() 函數不可用，請檢查PHP設定</span><br>";
}

// 臨時目錄權限檢查
echo "<h2>臨時目錄檢查</h2>";
$temp_dir = sys_get_temp_dir();
echo "臨時目錄: " . $temp_dir . "<br>";
if (is_writable($temp_dir)) {
    echo "臨時目錄可寫入<br>";
    $test_dir = $temp_dir . '/test_' . uniqid();
    if (mkdir($test_dir)) {
        echo "成功創建測試目錄<br>";
        if (file_put_contents($test_dir . '/test.txt', '測試中文內容')) {
            echo "成功寫入測試文件<br>";
            echo "文件內容: " . htmlspecialchars(file_get_contents($test_dir . '/test.txt')) . "<br>";
        } else {
            echo "<span style='color:red'>無法寫入測試文件</span><br>";
        }
        
        // 清理
        @unlink($test_dir . '/test.txt');
        @rmdir($test_dir);
    } else {
        echo "<span style='color:red'>無法創建測試目錄</span><br>";
    }
} else {
    echo "<span style='color:red'>臨時目錄不可寫入</span><br>";
}

// 尋找Python
echo "<h2>Python環境檢查</h2>";

function findPython() {
    $possibleCommands = ['python', 'python3', 'py'];
    
    foreach ($possibleCommands as $cmd) {
        $output = [];
        $return_var = 0;
        
        exec($cmd . ' --version 2>&1', $output, $return_var);
        if ($return_var === 0) {
            return ['command' => $cmd, 'version' => implode(' ', $output)];
        }
    }
    
    // 在 Windows 上嘗試常見的 Python 路徑
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $possiblePaths = [
            'C:\\Python39\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Program Files\\Python39\\python.exe',
            'C:\\Program Files\\Python310\\python.exe',
            'C:\\Program Files\\Python311\\python.exe',
            'C:\\laragon\\bin\\python\\python.exe',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $output = [];
                $return_var = 0;
                exec('"' . $path . '" --version 2>&1', $output, $return_var);
                if ($return_var === 0) {
                    return ['command' => $path, 'version' => implode(' ', $output)];
                }
            }
        }
    }
    
    return false;
}

$pythonInfo = findPython();
if ($pythonInfo) {
    echo "找到 Python: " . $pythonInfo['command'] . "<br>";
    echo "Python 版本: " . $pythonInfo['version'] . "<br>";
    
    // 測試執行簡單的Python程式
    echo "<h3>測試Python執行</h3>";
    $testCode = "# coding: utf-8\nprint('你好，世界!')\nprint('Hello, World!')\nprint('中文測試')";
    $tempFile = tempnam(sys_get_temp_dir(), 'py_');
    $tempFileWithExt = $tempFile . '.py';
    rename($tempFile, $tempFileWithExt);
    
    if (file_put_contents($tempFileWithExt, $testCode)) {
        echo "已寫入測試Python代碼<br>";
        
        $cmd = '"' . $pythonInfo['command'] . '" "' . $tempFileWithExt . '" 2>&1';
        echo "執行命令: " . htmlspecialchars($cmd) . "<br>";
        
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);
        
        echo "退出碼: " . $return_var . "<br>";
        echo "輸出結果: <pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
    } else {
        echo "<span style='color:red'>無法寫入Python測試文件</span><br>";
    }
    
    @unlink($tempFileWithExt);
} else {
    echo "<span style='color:red'>未找到Python解釋器</span><br>";
}

echo "<h2>環境變數</h2>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h2>PHP模塊</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";
?>
