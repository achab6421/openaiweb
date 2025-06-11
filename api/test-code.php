<?php
// 測試執行Python程式碼的API
header('Content-Type: application/json');

// 檢查是否為AJAX請求
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
    ]);
    exit;
}

// 檢查會話
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
    ]);
    exit;
}

// 解析請求數據
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['code'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No code provided',
    ]);
    exit;
}

$code = $data['code'];
$test_input = isset($data['input']) ? $data['input'] : '';
$levelId = isset($data['levelId']) ? intval($data['levelId']) : 0;
$pythonPath = isset($data['pythonPath']) ? $data['pythonPath'] : null;

// 如果提供了Python路徑，則設置全局變數
if ($pythonPath) {
    $GLOBALS['python_path'] = $pythonPath;
}

// 檢查Python是否可用
if (!isPythonAvailable()) {
    echo json_encode([
        'success' => false,
        'message' => 'Python interpreter not available',
    ]);
    exit;
}

// 創建臨時目錄和文件
$temp_dir = createTempDirectory();
$py_file = $temp_dir . '/code.py';
$input_file = $temp_dir . '/input.txt';

try {
    // 將代碼寫入臨時文件
    file_put_contents($py_file, $code);
    
    // 如果有測試輸入，則寫入輸入文件
    if (!empty($test_input)) {
        file_put_contents($input_file, $test_input);
    }
    
    // 執行Python程式碼，限制執行時間和資源
    $result = executePythonCode($py_file, $input_file);
    
    // 返回執行結果
    echo json_encode([
        'success' => true,
        'output' => $result['output'],
        'errors' => $result['errors'],
        'isError' => $result['isError'],
        'executionTime' => $result['executionTime'],
        'pythonPath' => $GLOBALS['python_path'] ?? 'unknown'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Execution error: ' . $e->getMessage(),
    ]);
} finally {
    // 清理臨時文件和目錄
    cleanupTempFiles($temp_dir);
}

exit;

/**
 * 檢查Python解釋器是否可用
 * @return bool
 */
function isPythonAvailable() {
    // 如果已經設置了Python路徑，優先使用
    if (isset($GLOBALS['python_path'])) {
        $output = [];
        $return_var = 0;
        $cmd = strpos($GLOBALS['python_path'], ' ') !== false ? 
              "\"{$GLOBALS['python_path']}\" --version 2>&1" : 
              "{$GLOBALS['python_path']} --version 2>&1";
        
        exec($cmd, $output, $return_var);
        if ($return_var === 0) {
            return true;
        }
    }
    
    $possibleCommands = ['python', 'python3', 'py'];
    
    foreach ($possibleCommands as $cmd) {
        $output = [];
        $return_var = 0;
        
        // 在 Windows 上可能需要檢查不同路徑
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // 測試基本命令
            exec($cmd . ' --version 2>&1', $output, $return_var);
            if ($return_var === 0) {
                $GLOBALS['python_path'] = $cmd;
                return true;
            }
            
            // 嘗試使用完整路徑
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
                'C:\\Users\\' . get_system_user() . '\\AppData\\Local\\Programs\\Python\\Python39\\python.exe',
                'C:\\Users\\' . get_system_user() . '\\AppData\\Local\\Programs\\Python\\Python310\\python.exe',
                'C:\\Users\\' . get_system_user() . '\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
                'C:\\laragon\\bin\\python\\python.exe',
                'C:\\xampp\\python\\python.exe',
                'D:\\Python39\\python.exe',
                'D:\\Python310\\python.exe',
                'D:\\Python311\\python.exe'
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $output = [];
                    $return_var = 0;
                    exec("\"{$path}\" --version 2>&1", $output, $return_var);
                    if ($return_var === 0) {
                        $GLOBALS['python_path'] = $path;
                        return true;
                    }
                }
            }
        } else {
            // Linux/Mac 測試
            exec($cmd . ' --version 2>&1', $output, $return_var);
            if ($return_var === 0) {
                $GLOBALS['python_path'] = $cmd;
                return true;
            }
        }
    }
    
    return false;
}

/**
 * 獲取當前系統用戶名 - 改名以避免與PHP內建函數衝突
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
 * 創建臨時目錄
 * @return string 臨時目錄路徑
 */
function createTempDirectory() {
    $temp_base = sys_get_temp_dir();
    $temp_dir = $temp_base . '/py_' . uniqid();
    
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    return $temp_dir;  // 添加缺少的 return 語句
}

/**
 * 執行Python程式碼
 * @param string $py_file Python文件路徑
 * @param string $input_file 輸入文件路徑
 * @return array 執行結果
 */
function executePythonCode($py_file, $input_file) {
    $start_time = microtime(true);
    $output = [];
    $errors = [];
    $return_var = 0;
    
    // 使用之前找到的可用 Python 路徑
    $python_cmd = isset($GLOBALS['python_path']) ? $GLOBALS['python_path'] : 'python';
    $python_cmd = strpos($python_cmd, ' ') !== false ? "\"{$python_cmd}\"" : $python_cmd;
    
    // 構建命令，使用直接重定向而不是 PowerShell
    $stdout_file = dirname($py_file) . '/stdout.txt';
    $stderr_file = dirname($py_file) . '/stderr.txt';
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows 系統
        if (file_exists($input_file)) {
            // 使用 type 命令提供輸入
            $cmd = "type " . escapeshellarg($input_file) . " | " . $python_cmd . " " . 
                   escapeshellarg($py_file) . " > " . 
                   escapeshellarg($stdout_file) . " 2> " . 
                   escapeshellarg($stderr_file);
        } else {
            $cmd = $python_cmd . " " . escapeshellarg($py_file) . " > " . 
                   escapeshellarg($stdout_file) . " 2> " . 
                   escapeshellarg($stderr_file);
        }
    } else {
        // Linux/Mac 系統
        if (file_exists($input_file)) {
            $cmd = "cat " . escapeshellarg($input_file) . " | " . $python_cmd . " " . 
                   escapeshellarg($py_file) . " > " . 
                   escapeshellarg($stdout_file) . " 2> " . 
                   escapeshellarg($stderr_file);
        } else {
            $cmd = $python_cmd . " " . escapeshellarg($py_file) . " > " . 
                   escapeshellarg($stdout_file) . " 2> " . 
                   escapeshellarg($stderr_file);
        }
    }
    
    // 記錄即將執行的命令
    error_log("Executing command: " . $cmd);
    
    // 執行命令
    exec($cmd, $output_raw, $return_var);
    
    // 讀取輸出文件
    if (file_exists($stdout_file)) {
        $stdout_content = file_get_contents($stdout_file);
        if ($stdout_content !== false) {
            $output = preg_split('/\r\n|\r|\n/', $stdout_content);
        }
        @unlink($stdout_file);
    }
    
    // 讀取錯誤文件
    if (file_exists($stderr_file)) {
        $stderr_content = file_get_contents($stderr_file);
        if ($stderr_content !== false) {
            $errors = preg_split('/\r\n|\r|\n/', $stderr_content);
        }
        @unlink($stderr_file);
    }
    
    // 計算執行時間
    $execution_time = microtime(true) - $start_time;
    $execution_time = round($execution_time * 1000); // 轉換為毫秒
    
    // 處理可能的超時情況
    $is_timeout = ($return_var === 124);
    $is_error = (!empty($errors) || $is_timeout);
    
    if ($is_timeout) {
        $errors[] = "程式執行超時";
    }
    
    // 過濾空行，但保留重要的空內容
    $output = array_filter($output, function($line) {
        // 保留數字 0 和其他非空字符串
        return $line !== '' || $line === '0';
    });
    
    $errors = array_filter($errors, function($line) {
        return $line !== '';
    });
    
    // 記錄輸出結果
    error_log("Python execution return code: " . $return_var);
    error_log("Python execution output: " . print_r($output, true));
    error_log("Python execution errors: " . print_r($errors, true));
    
    return [
        'output' => implode("\n", $output),
        'errors' => implode("\n", $errors),
        'isError' => $is_error,
        'executionTime' => $execution_time
    ];
}

/**
 * 清理臨時文件和目錄
 * @param string $temp_dir 臨時目錄路徑
 */
function cleanupTempFiles($temp_dir) {
    // 刪除目錄中的所有文件
    $files = glob($temp_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    
    // 刪除目錄
    @rmdir($temp_dir);
}
?>
