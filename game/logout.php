<?php
// 初始化會話
session_start();

// 刪除所有會話變量
$_SESSION = array();

// 如果有session cookie，清除它
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 銷毀會話
session_destroy();

// 重定向到登入頁面
header("location: index.php");
exit;
?>
