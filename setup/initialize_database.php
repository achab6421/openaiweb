<?php
// 資料庫初始化腳本

// 顯示所有錯誤
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 資料庫連線設定
$host = 'localhost';
$db_name = 'python_monster_village';
$username = 'root';
$password = '';

try {
    // 連接 MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Python 怪物村資料庫初始化</h1>";
    
    // 創建資料庫
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>資料庫 '$db_name' 已創建或已存在</p>";
    
    // 選擇資料庫
    $pdo->exec("USE `$db_name`");
    
    // 執行基礎資料表結構 SQL
    echo "<h2>建立基礎表結構</h2>";
    $schema_sql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schema_sql);
    echo "<p>基礎表結構建立完成</p>";
    
    // 執行額外資料表 SQL
    echo "<h2>建立額外表結構</h2>";
    $additional_sql = file_get_contents(__DIR__ . '/../database/additional_tables.sql');
    $pdo->exec($additional_sql);
    echo "<p>額外表結構建立完成</p>";
    
    // 執行種子資料 SQL
    echo "<h2>載入初始資料</h2>";
    $seed_sql = file_get_contents(__DIR__ . '/../database/seed.sql');
    $pdo->exec($seed_sql);
    echo "<p>初始資料載入完成</p>";
    
    echo "<h2>資料庫初始化完成</h2>";
    echo "<p><a href='../index.php'>點擊這裡返回首頁</a></p>";
    
} catch(PDOException $e) {
    die("<h2>資料庫初始化錯誤</h2><p>" . $e->getMessage() . "</p>");
}
?>
