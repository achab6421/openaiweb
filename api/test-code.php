<?php
// 測試執行Python程式碼的API
header('Content-Type: application/json; charset=utf-8'); // 確保回應使用UTF-8編碼

// 檢查是否為AJAX請求
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查會話
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 解析請求數據
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['code'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No code provided',
    ], JSON_UNESCAPED_UNICODE);
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
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 創建臨時目錄和文件
$temp_dir = createTempDirectory();
$py_file = $temp_dir . '/code.py';
$input_file = $temp_dir . '/input.txt';

try {
    // 將代碼寫入臨時文件
    file_put_contents($py_file, $code, LOCK_EX);
    
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
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 修復這行的語法錯誤
    echo json_encode([
        'success' => false,
        'message' => 'Execution error: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
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
 * 創建臨時目錄 - 修改為使用 Laragon 的臨時目錄
 * @return string 臨時目錄路徑
 */
function createTempDirectory() {
    // 改用 laragon 的臨時目錄而非 Windows 的 Temp 目錄
    $temp_base = 'C:/laragon/tmp';
    
    // 如果 laragon 目錄不可用，嘗試其他位置
    if (!is_writable($temp_base)) {
        $temp_base = dirname(__FILE__) . '/../temp';
        
        // 如果目錄不存在就創建它
        if (!file_exists($temp_base)) {
            mkdir($temp_base, 0777, true);
        }
    }
    
    $temp_dir = $temp_base . '/py_' . uniqid();
    
    if (!file_exists($temp_dir)) {
        mkdir($temp_dir, 0777, true); // 使用 0777 確保權限充足
    }
    
    return $temp_dir;
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
    
    // 修改Python檔案，添加Base64編碼輸出的包裝
    $original_code = file_get_contents($py_file);
    $base64_wrapper = <<<PYTHON
# -*- coding: utf-8 -*-
import sys
import base64
import traceback

# 保存原始的標準輸出
original_stdout = sys.stdout

# 創建新的輸出攔截器
class OutputInterceptor:
    def __init__(self):
        self.output = ''
    
    def write(self, text):
        self.output += text
    
    def flush(self):
        pass

# 攔截標準輸出
interceptor = OutputInterceptor()
sys.stdout = interceptor

try:
    # 執行原始代碼
{indented_code}
except Exception as e:
    print("錯誤: ", str(e))
    print(traceback.format_exc())

# 恢復原始標準輸出
sys.stdout = original_stdout

# 輸出Base64編碼的結果
print("==BASE64_OUTPUT_BEGIN==")
encoded_output = base64.b64encode(interceptor.output.encode('utf-8')).decode('utf-8')
print(encoded_output)
print("==BASE64_OUTPUT_END==")
PYTHON;

    // 縮排原始代碼
    $indented_code = '';
    foreach(explode("\n", $original_code) as $line) {
        $indented_code .= "    " . $line . "\n";
    }
    
    // 替換占位符並寫入修改後的Python檔案
    $base64_code = str_replace("{indented_code}", $indented_code, $base64_wrapper);
    file_put_contents($py_file, $base64_code);
    
    // 設置命令列編碼為UTF-8
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec('chcp 65001', $dummy, $chcpReturn);
    }
    
    // 執行Python程式
    $cmd = $python_cmd . " " . escapeshellarg($py_file) . " > " . 
           escapeshellarg($stdout_file) . " 2> " . 
           escapeshellarg($stderr_file);
    
    if (file_exists($input_file)) {
        // 如果有輸入檔案，先將內容讀入記憶體再通過Python的sys.stdin處理
        $input_content = file_get_contents($input_file);
        // 修改Python代碼以處理輸入(實際應用中可能需要根據情況調整)
    }
    
    // 記錄即將執行的命令
    error_log("Executing command: " . $cmd);
    
    // 執行命令
    exec($cmd, $output_raw, $return_var);
    
    // 讀取輸出檔案
    $stdout_content = '';
    $base64_output = '';
    $in_base64_section = false;
    
    if (file_exists($stdout_file)) {
        $lines = file($stdout_file, FILE_IGNORE_NEW_LINES);
        
        foreach ($lines as $line) {
            if ($line === "==BASE64_OUTPUT_BEGIN==") {
                $in_base64_section = true;
                continue;
            }
            
            if ($line === "==BASE64_OUTPUT_END==") {
                $in_base64_section = false;
                continue;
            }
            
            if ($in_base64_section) {
                $base64_output .= $line;
            } else {
                $stdout_content .= $line . "\n";
            }
        }
        
        // 解碼Base64輸出
        if (!empty($base64_output)) {
            try {
                $decoded_output = base64_decode($base64_output, true);
                if ($decoded_output !== false) {
                    // 成功解碼後，用解碼內容替換輸出
                    $stdout_content = $decoded_output;
                }
            } catch (Exception $e) {
                error_log("Base64解碼失敗: " . $e->getMessage());
                // 解碼失敗時繼續使用原始輸出
            }
        }
        
        if ($stdout_content !== false) {
            $output = preg_split('/\r\n|\r|\n/', $stdout_content);
        }
        @unlink($stdout_file);
    }
    
    // 讀取錯誤檔案
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
    $is_error = (!empty($errors) || $is_timeout || $return_var !== 0);
    
    if ($is_timeout) {
        $errors[] = "執行超時：程式運行時間超過限制";
    }
    
    // 清理空字符串
    $output = array_filter($output, function($line) {
        return $line !== "";
    });
    
    $errors = array_filter($errors, function($line) {
        return $line !== "";
    });
    
    return [
        'output' => implode("\n", $output),
        'errors' => implode("\n", $errors),
        'isError' => $is_error,
        'executionTime' => $execution_time
    ];
}

/**
 * 清理臨時文件和目錄
 * @param string $dir 臨時目錄路徑
 */
function cleanupTempFiles($dir) {
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
?>
