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
            $this->conn = new PDO('mysql:host=' . $this->host .';port=' . 3309 . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec('set names utf8');
        } catch(PDOException $e) {
            echo '連接資料庫失敗: ' . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
