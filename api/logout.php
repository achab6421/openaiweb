<?php
// 處理用戶登出
session_start();

// 清除所有session變數
$_SESSION = array();

// 如果有session cookie，把它也刪除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 最後，刪除session
session_destroy();

// 重定向回首頁
header("Location: ../index.php");
exit;
?>
