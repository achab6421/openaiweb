<?php
// Python 系統環境檢查
header('Content-Type: text/html; charset=utf-8');

// 允許直接顯示錯誤
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * 執行系統命令並返回結果
 * @param string $cmd 要執行的命令
 * @return array 包含輸出、錯誤代碼和執行時間
 */
function executeCommand($cmd) {
    $start = microtime(true);
    $output = [];
    $return_var = null;
    exec($cmd . ' 2>&1', $output, $return_var);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    return [
        'output' => implode("\n", $output),
        'return_code' => $return_var,
        'time' => $time
    ];
}

/**
 * 檢查命令是否可執行
 * @param string $cmd 要檢查的命令
 * @return bool
 */
function isCommandAvailable($cmd) {
    $result = executeCommand($cmd . ' --version');
    return $result['return_code'] === 0;
}

/**
 * 獲取可能的 Python 安裝路徑
 * @return array 路徑列表
 */
function getPossiblePythonPaths() {
    $paths = [];
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows 可能的路徑
        $drives = ['C:', 'D:'];
        $versions = ['Python37', 'Python38', 'Python39', 'Python310', 'Python311', 'Python312'];
        $locations = [
            '%s\\%s\\python.exe',
            '%s\\Program Files\\%s\\python.exe',
            '%s\\Program Files (x86)\\%s\\python.exe',
            '%s\\Users\\' . get_system_user() . '\\AppData\\Local\\Programs\\Python\\%s\\python.exe',
            '%s\\laragon\\bin\\python\\python.exe',
            '%s\\xampp\\python\\python.exe'
        ];
        
        foreach ($drives as $drive) {
            foreach ($versions as $version) {
                foreach ($locations as $location) {
                    $path = sprintf($location, $drive, $version);
                    $paths[] = $path;
                }
            }
            // 檢查沒有版本號的路徑
            $paths[] = sprintf('%s\\laragon\\bin\\python\\python.exe', $drive);
            $paths[] = sprintf('%s\\xampp\\python\\python.exe', $drive);
        }
    } else {
        // Linux/Mac 可能的路徑
        $paths = [
            '/usr/bin/python',
            '/usr/bin/python3',
            '/usr/local/bin/python',
            '/usr/local/bin/python3'
        ];
    }
    
    return $paths;
}

/**
 * 獲取當前系統用戶名 - 修改名稱避免衝突
 * @return string
 */
function get_system_user() {
    if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $user = posix_getpwuid(posix_geteuid());
        return $user['name'];
    }
    
    // Windows 用戶，嘗試獲取環境變數
    $user = getenv('USERNAME');
    if ($user !== false) {
        return $user;
    }
    
    return 'user'; // 默認值
}

/**
 * 檢查 PHP 的執行權限
 */
function checkPhpExecPermissions() {
    $disabled_functions = explode(',', ini_get('disable_functions'));
    $exec_disabled = in_array('exec', $disabled_functions);
    $system_disabled = in_array('system', $disabled_functions);
    $proc_open_disabled = in_array('proc_open', $disabled_functions);
    
    $safe_mode = ini_get('safe_mode');
    
    return [
        'exec_disabled' => $exec_disabled,
        'system_disabled' => $system_disabled,
        'proc_open_disabled' => $proc_open_disabled,
        'safe_mode' => $safe_mode === '1' || $safe_mode === 'On',
        'can_execute' => !$exec_disabled && !$system_disabled
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python 系統環境檢查</title>
    <style>
        body {
            font-family: 'Noto Sans TC', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        h1, h2 {
            color: #2c3e50;
        }
        
        .section {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        
        .result-box {
            background-color: #fff;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .success {
            color: green;
            font-weight: bold;
        }
        
        .warning {
            color: orange;
            font-weight: bold;
        }
        
        .error {
            color: red;
            font-weight: bold;
        }
        
        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: Consolas, monospace;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Python 系統環境檢查工具</h1>
        
        <div class="section">
            <h2>1. PHP 執行權限檢查</h2>
            <?php
            $php_permissions = checkPhpExecPermissions();
            ?>
            <div class="result-box">
                <p>
                    <strong>exec 函數:</strong> 
                    <?php if ($php_permissions['exec_disabled']): ?>
                        <span class="error">已禁用 ⚠️</span>
                    <?php else: ?>
                        <span class="success">可用 ✅</span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong>system 函數:</strong> 
                    <?php if ($php_permissions['system_disabled']): ?>
                        <span class="error">已禁用 ⚠️</span>
                    <?php else: ?>
                        <span class="success">可用 ✅</span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong>proc_open 函數:</strong> 
                    <?php if ($php_permissions['proc_open_disabled']): ?>
                        <span class="error">已禁用 ⚠️</span>
                    <?php else: ?>
                        <span class="success">可用 ✅</span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong>安全模式:</strong> 
                    <?php if ($php_permissions['safe_mode']): ?>
                        <span class="warning">已啟用 ⚠️</span>
                    <?php else: ?>
                        <span class="success">未啟用 ✅</span>
                    <?php endif; ?>
                </p>
                <p>
                    <strong>結論:</strong> 
                    <?php if ($php_permissions['can_execute']): ?>
                        <span class="success">PHP 可以執行外部命令 ✅</span>
                    <?php else: ?>
                        <span class="error">PHP 無法執行外部命令 ⚠️</span>
                        <p>請修改 php.ini 設定，移除 disable_functions 中的 exec 和 system 函數限制。</p>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <div class="section">
            <h2>2. 基本 Python 命令檢查</h2>
            <div class="result-box">
                <?php
                $python_commands = ['python', 'python3', 'py'];
                $found = false;
                
                foreach ($python_commands as $cmd):
                    $result = executeCommand($cmd . ' --version');
                ?>
                <h3>命令: <?= htmlspecialchars($cmd) ?></h3>
                <p>
                    <strong>狀態:</strong> 
                    <?php if ($result['return_code'] === 0): ?>
                        <span class="success">可用 ✅</span>
                        <?php $found = true; ?>
                    <?php else: ?>
                        <span class="error">不可用 ❌</span>
                    <?php endif; ?>
                </p>
                <?php if ($result['return_code'] === 0): ?>
                <p><strong>版本信息:</strong> <?= htmlspecialchars($result['output']) ?></p>
                <?php else: ?>
                <p><strong>錯誤信息:</strong> <pre><?= htmlspecialchars($result['output'] ?: '沒有輸出') ?></pre></p>
                <?php endif; ?>
                <hr>
                <?php endforeach; ?>
                
                <p>
                    <strong>結論:</strong> 
                    <?php if ($found): ?>
                        <span class="success">找到可用的 Python 命令 ✅</span>
                    <?php else: ?>
                        <span class="error">未找到可用的 Python 命令 ❌</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <div class="section">
            <h2>3. Python 路徑檢查</h2>
            <div class="result-box">
                <table>
                    <tr>
                        <th>Python 安裝路徑</th>
                        <th>狀態</th>
                    </tr>
                    <?php
                    $paths = getPossiblePythonPaths();
                    $found_path = false;
                    
                    foreach ($paths as $path):
                        if (file_exists($path)):
                            $test_cmd = '"' . $path . '" --version';
                            $result = executeCommand($test_cmd);
                            $found_path = $found_path || ($result['return_code'] === 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($path) ?></td>
                        <td>
                            <?php if ($result['return_code'] === 0): ?>
                                <span class="success">可用 ✅ (<?= htmlspecialchars($result['output']) ?>)</span>
                            <?php else: ?>
                                <span class="warning">存在但不可執行 ⚠️</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                        endif;
                    endforeach;
                    
                    if (!$found_path):
                    ?>
                    <tr>
                        <td colspan="2"><span class="error">未找到可用的 Python 路徑 ❌</span></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <div class="section">
            <h2>4. 系統信息</h2>
            <div class="result-box">
                <p><strong>PHP 版本:</strong> <?= phpversion() ?></p>
                <p><strong>作業系統:</strong> <?= PHP_OS ?></p>
                <p><strong>伺服器軟體:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? '未知' ?></p>
                <p><strong>當前用戶:</strong> <?= get_system_user() ?></p>
                <p><strong>臨時目錄:</strong> <?= sys_get_temp_dir() ?></p>
                <p><strong>環境變數 PATH:</strong> <pre><?= htmlspecialchars(getenv('PATH')) ?></pre></p>
            </div>
        </div>
        
        <div class="section">
            <h2>5. 解決方案</h2>
            <div class="result-box">
                <h3>如果 Python 檢查失敗，請嘗試以下解決方案：</h3>
                <ol>
                    <li>確保已安裝 Python (推薦 Python 3.8 或更高版本)。</li>
                    <li>將 Python 安裝目錄添加到系統 PATH 環境變數。</li>
                    <li>
                        <p>修改 <code>test-code.php</code> 文件，手動指定 Python 路徑：</p>
                        <pre>
function isPythonAvailable() {
    // 直接指定 Python 路徑
    $GLOBALS['python_path'] = 'C:\\Path\\To\\Python\\python.exe'; // 替換為實際路徑
    
    // 驗證路徑是否正確
    $output = [];
    $return_var = 0;
    exec('"' . $GLOBALS['python_path'] . '" --version 2>&1', $output, $return_var);
    
    return $return_var === 0;
}</pre>
                    </li>
                    <li>確保 PHP 有權限執行外部命令 (在 php.ini 中檢查 disable_functions 設定)。</li>
                    <li>如果使用共享主機，請聯絡主機提供商開啟執行權限。</li>
                </ol>
            </div>
        </div>
        
        <div class="section">
            <h2>6. 測試執行簡單 Python 代碼</h2>
            <div class="result-box">
                <form method="post">
                    <input type="hidden" name="test_python" value="1">
                    <button type="submit" class="btn">執行 Python 測試</button>
                </form>
                
                <?php if (isset($_POST['test_python'])): ?>
                    <h3>測試結果:</h3>
                    <?php
                    // 創建臨時文件
                    $temp_file = tempnam(sys_get_temp_dir(), 'py_') . '.py';
                    file_put_contents($temp_file, 'print("Hello from Python!")');
                    
                    $cmd = '';
                    
                    // 嘗試找到可用的 Python 命令
                    foreach (['python', 'python3', 'py'] as $cmd_test) {
                        $result = executeCommand($cmd_test . ' --version');
                        if ($result['return_code'] === 0) {
                            $cmd = $cmd_test;
                            break;
                        }
                    }
                    
                    // 如果未找到基本命令，嘗試搜索路徑
                    if (empty($cmd)) {
                        foreach (getPossiblePythonPaths() as $path) {
                            if (file_exists($path)) {
                                $test_cmd = '"' . $path . '" --version';
                                $result = executeCommand($test_cmd);
                                if ($result['return_code'] === 0) {
                                    $cmd = '"' . $path . '"';
                                    break;
                                }
                            }
                        }
                    }
                    
                    if (!empty($cmd)) {
                        $result = executeCommand($cmd . ' ' . escapeshellarg($temp_file));
                        if ($result['return_code'] === 0) {
                            echo '<p class="success">測試成功! ✅</p>';
                            echo '<pre>' . htmlspecialchars($result['output']) . '</pre>';
                        } else {
                            echo '<p class="error">測試失敗 ❌</p>';
                            echo '<pre>' . htmlspecialchars($result['output']) . '</pre>';
                        }
                    } else {
                        echo '<p class="error">找不到可用的 Python 命令 ❌</p>';
                    }
                    
                    // 清理
                    @unlink($temp_file);
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
