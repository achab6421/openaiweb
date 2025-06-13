<?php
// 資料庫連線設定
class Database {
    private $host = 'localhost';
    private $db_name = 'python_monster_village';
    private $username = 'root';
    private $password = '';
    private $conn;

    // 連接資料庫
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8mb4"); // 使用 UTF-8 字符集
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>
