<?php
// Python執行測試和調試
session_start();
$_SESSION['logged_in'] = true;

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 嘗試尋找 Python 執行檔
function findPythonExecutable() {
    $possibleCommands = ['python', 'python3', 'py'];
    $possiblePaths = [
        'C:\\Python39\\python.exe',
        'C:\\Python310\\python.exe',
        'C:\\Python311\\python.exe',
        'C:\\Program Files\\Python39\\python.exe',
        'C:\\Program Files\\Python310\\python.exe',
        'C:\\Program Files\\Python311\\python.exe',
        'C:\\Program Files (x86)\\Python39\\python.exe',
        'C:\\Program Files (x86)\\Python310\\python.exe',
        'C:\\Program Files (x86)\\Python311\\python.exe',
        'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python39\\python.exe',
        'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
        'C:\\Users\\' . getenv('USERNAME') . '\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
        'C:\\laragon\\bin\\python\\python.exe',
        'C:\\xampp\\python\\python.exe',
        'D:\\Python39\\python.exe',
        'D:\\Python310\\python.exe',
        'D:\\Python311\\python.exe'
    ];
    
    // 先嘗試基本命令
    foreach ($possibleCommands as $cmd) {
        $output = [];
        $return_var = 0;
        exec("{$cmd} --version 2>&1", $output, $return_var);
        if ($return_var === 0) {
            return $cmd;
        }
    }
    
    // 嘗試特定路徑
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $output = [];
            $return_var = 0;
            exec("\"{$path}\" --version 2>&1", $output, $return_var);
            if ($return_var === 0) {
                return $path;
            }
        }
    }
    
    return null;
}

$pythonExe = findPythonExecutable();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python 執行調試工具</title>
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        textarea {
            width: 100%;
            min-height: 100px;
            font-family: monospace;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .output {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .debug-info {
            background-color: #f0f7ff;
            border: 1px solid #d1e3ff;
            padding: 10px;
            margin-top: 15px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Python 執行調試工具</h1>
    
    <div class="container">
        <div class="card">
            <h2>Python 環境檢查</h2>
            <div>
                <?php if ($pythonExe): ?>
                    <p class="success">找到 Python 執行檔: <?= htmlspecialchars($pythonExe) ?></p>
                    <?php 
                        $output = [];
                        $return_var = 0;
                        if (strpos($pythonExe, ' ') !== false) {
                            exec("\"{$pythonExe}\" --version 2>&1", $output, $return_var);
                        } else {
                            exec("{$pythonExe} --version 2>&1", $output, $return_var);
                        }
                        echo "<p>版本信息: " . htmlspecialchars(implode("\n", $output)) . "</p>";
                    ?>
                <?php else: ?>
                    <p class="error">無法找到 Python 執行檔。請安裝 Python 並確保它在系統 PATH 中。</p>
                    <p>建議您下載並安裝 <a href="https://www.python.org/downloads/" target="_blank">Python</a>，並在安裝時勾選「Add Python to PATH」選項。</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>測試 1: 直接命令執行</h2>
            <div id="test1-result">
                <?php if ($pythonExe): ?>
                    <?php
                    $test_code = "print('Hello, World!')\nprint('This is a test')";
                    $temp_file = sys_get_temp_dir() . '/py_test_' . uniqid() . '.py';
                    file_put_contents($temp_file, $test_code);
                    
                    echo "<p>測試代碼:</p>";
                    echo "<pre>" . htmlspecialchars($test_code) . "</pre>";
                    
                    echo "<p>命令執行結果:</p>";
                    $output = [];
                    $return_var = 0;
                    
                    $command = strpos($pythonExe, ' ') !== false ? 
                              "\"{$pythonExe}\" " . escapeshellarg($temp_file) . " 2>&1" : 
                              "{$pythonExe} " . escapeshellarg($temp_file) . " 2>&1";
                    
                    exec($command, $output, $return_var);
                    
                    if ($return_var === 0) {
                        echo "<div class='output'>" . htmlspecialchars(implode("\n", $output)) . "</div>";
                        echo "<p class='success'>執行成功 (返回代碼: {$return_var})</p>";
                        echo "<p>執行命令: " . htmlspecialchars($command) . "</p>";
                    } else {
                        echo "<div class='error'>執行失敗 (返回代碼: {$return_var})</div>";
                        echo "<div class='output'>" . htmlspecialchars(implode("\n", $output)) . "</div>";
                        echo "<p>執行命令: " . htmlspecialchars($command) . "</p>";
                    }
                    
                    @unlink($temp_file);
                    ?>
                <?php else: ?>
                    <p class="error">無法測試，因為未找到 Python 執行檔</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>測試 2: 檔案重定向</h2>
            <div id="test2-result">
                <?php if ($pythonExe): ?>
                    <?php
                    $test_code = "print('File redirection test')\nprint('Multiple lines output')";
                    $temp_dir = sys_get_temp_dir() . '/py_test_' . uniqid();
                    mkdir($temp_dir);
                    $temp_file = $temp_dir . '/test.py';
                    $output_file = $temp_dir . '/output.txt';
                    $error_file = $temp_dir . '/error.txt';
                    file_put_contents($temp_file, $test_code);
                    
                    echo "<p>測試代碼:</p>";
                    echo "<pre>" . htmlspecialchars($test_code) . "</pre>";
                    
                    echo "<p>檔案重定向結果:</p>";
                    
                    $py_command = strpos($pythonExe, ' ') !== false ? 
                                 "\"{$pythonExe}\"" : $pythonExe;
                    
                    $cmd = "{$py_command} " . escapeshellarg($temp_file) . " > " . 
                           escapeshellarg($output_file) . " 2> " . 
                           escapeshellarg($error_file);
                    
                    $output_raw = [];
                    $return_var = 0;
                    exec($cmd, $output_raw, $return_var);
                    
                    echo "<p>執行命令: " . htmlspecialchars($cmd) . "</p>";
                    echo "<p>返回代碼: {$return_var}</p>";
                    
                    if (file_exists($output_file)) {
                        $file_content = file_get_contents($output_file);
                        echo "<p>標準輸出:</p>";
                        echo "<div class='output'>" . htmlspecialchars($file_content) . "</div>";
                    } else {
                        echo "<div class='error'>輸出檔案不存在</div>";
                    }
                    
                    if (file_exists($error_file)) {
                        $error_content = file_get_contents($error_file);
                        if (!empty($error_content)) {
                            echo "<p>錯誤輸出:</p>";
                            echo "<div class='error'>" . htmlspecialchars($error_content) . "</div>";
                        }
                    }
                    
                    @unlink($temp_file);
                    @unlink($output_file);
                    @unlink($error_file);
                    @rmdir($temp_dir);
                    ?>
                <?php else: ?>
                    <p class="error">無法測試，因為未找到 Python 執行檔</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>測試 3: API 函數測試</h2>
            <textarea id="python-code">print('Hello, World!')
print('Testing Python execution')</textarea>
            <button id="run-test" <?= $pythonExe ? '' : 'disabled' ?>>執行測試</button>
            <div id="api-result"></div>
            
            <?php if (!$pythonExe): ?>
                <p class="error">無法執行 API 測試，因為未找到 Python 執行檔</p>
            <?php else: ?>
                <p>已在系統環境變數中設置 Python 執行檔: <code id="python-path"><?= htmlspecialchars($pythonExe) ?></code></p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>安裝 Python 指南</h2>
            <p>如果您還沒有安裝 Python 或測試結果顯示 Python 不可用，請按照以下步驟操作：</p>
            <ol>
                <li>訪問 <a href="https://www.python.org/downloads/" target="_blank">Python 官方網站</a> 下載最新版本</li>
                <li>安裝時，勾選「Add Python to PATH」選項</li>
                <li>完成安裝後，重啟您的網頁伺服器</li>
                <li>刷新此頁面進行測試</li>
            </ol>
            <p>如果您已經安裝了 Python，但系統無法找到它，您可以：</p>
            <ol>
                <li>找到您的 Python 安裝位置（通常在 C:\Python39 或 C:\Users\[用戶名]\AppData\Local\Programs\Python\Python39）</li>
                <li>將 Python 安裝位置添加到系統 PATH 環境變數</li>
                <li>或者在本頁面頂部記下找到的 Python 路徑，然後修改 <code>test-code.php</code> 文件，直接指定 Python 路徑</li>
            </ol>
        </div>
    </div>
    
    <script>
        document.getElementById('run-test').addEventListener('click', function() {
            const code = document.getElementById('python-code').value;
            const resultDiv = document.getElementById('api-result');
            const pythonPath = document.getElementById('python-path').textContent;
            
            resultDiv.innerHTML = '<p>正在執行測試...</p>';
            
            fetch('../api/test-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    code: code,
                    pythonPath: pythonPath
                }),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let result = '<p class="success">執行成功</p>';
                    
                    if (data.output) {
                        result += '<p>輸出結果:</p>';
                        result += `<div class="output">${data.output}</div>`;
                    } else {
                        result += '<p>沒有輸出</p>';
                    }
                    
                    if (data.errors) {
                        result += '<p>錯誤信息:</p>';
                        result += `<div class="error">${data.errors}</div>`;
                    }
                    
                    result += `<p>執行時間: ${data.executionTime} 毫秒</p>`;
                    
                    // 顯示調試信息
                    result += '<div class="debug-info">原始響應: ' + 
                              JSON.stringify(data, null, 2) + '</div>';
                    
                    resultDiv.innerHTML = result;
                } else {
                    resultDiv.innerHTML = `<div class="error">執行失敗: ${data.message}</div>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="error">請求錯誤: ${error.message}</div>`;
            });
        });
    </script>
</body>
</html>
