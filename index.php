<?php
// 初始化會話
session_start();

// 如果用戶已經登入，直接導向主選單頁面
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// 導向到登入頁面
header("location: login.php");
exit;
?>
