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

// 創建臨時Python文件
$temp_dir = sys_get_temp_dir();
$temp_file = tempnam($temp_dir, 'py_');
$py_file = $temp_file . '.py';

// 重命名文件使其有.py擴展名
rename($temp_file, $py_file);

// 將代碼寫入臨時文件
file_put_contents($py_file, $code);

// 執行Python程式碼，限制執行時間和資源
$output = [];
$return_var = 0;

// 嘗試執行Python程式碼
$command = "timeout 5s python " . escapeshellarg($py_file) . " 2>&1";
exec($command, $output, $return_var);

// 刪除臨時文件
@unlink($py_file);

// 準備響應
$result = implode("\n", $output);
$is_error = $return_var !== 0;

// 如果執行超時
if ($return_var === 124) {
    $result = "程式執行超時 (超過5秒)";
    $is_error = true;
}

echo json_encode([
    'success' => true,
    'output' => $result,
    'isError' => $is_error
]);
exit;
?>
