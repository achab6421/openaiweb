<?php
// 資料庫設置工具
require_once 'config/database.php';

// 提供基本的HTML格式
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資料庫設置工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border: 1px solid #ddd;
            overflow-x: auto;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>資料庫設置工具</h1>
        
        <?php
        // 檢查是否有設置操作
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        if ($action == 'setup') {
            try {
                // 創建數據庫連接
                $database = new Database();
                $db = $database->getConnection();
                
                echo "<h2>資料庫連接成功</h2>";
                echo "<p>資料庫類型: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
                echo "<p>資料庫版本: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
                
                // 創建必要表格
                $tables = [
                    // 使用者進度表
                    "user_progress" => "CREATE TABLE IF NOT EXISTS `user_progress` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` varchar(50) NOT NULL,
                        `level_id` int(11) NOT NULL,
                        `completed_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `user_level_idx` (`user_id`,`level_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
                ];
                
                echo "<h2>表格設置結果:</h2>";
                
                foreach ($tables as $table => $sql) {
                    try {
                        // 檢查表格是否已存在
                        $result = $db->query("SHOW TABLES LIKE '$table'");
                        $tableExists = ($result->rowCount() > 0);
                        
                        if ($tableExists) {
                            echo "<p class='warning'>表格 <strong>$table</strong> 已存在，將不會被修改。</p>";
                        } else {
                            // 創建表格
                            $db->exec($sql);
                            echo "<p class='success'>成功創建表格 <strong>$table</strong></p>";
                        }
                    } catch (PDOException $e) {
                        echo "<p class='error'>創建表格 <strong>$table</strong> 失敗: " . $e->getMessage() . "</p>";
                    }
                }
                
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo "<h2>資料庫連接錯誤</h2>";
                echo "<p>" . $e->getMessage() . "</p>";
                echo "</div>";
            }
        } else {
            // 顯示設置按鈕
            echo "<p>點擊下方按鈕設置資料庫表格</p>";
            echo "<p class='warning'>注意：此操作會創建必要的資料表，但不會刪除或修改已存在的表格。</p>";
            echo "<form method='get'>";
            echo "<input type='hidden' name='action' value='setup'>";
            echo "<button type='submit'>設置資料庫</button>";
            echo "</form>";
        }
        ?>
        
        <h2>數據庫設定檢查</h2>
        <pre>
設定檔位置: <?php echo realpath('config/database.php'); ?>

<?php
    // 顯示配置檔案內容
    $config_content = file_exists('config/database.php') ? 
        highlight_file('config/database.php', true) : 
        '配置文件不存在';
    
    echo $config_content;
?>
        </pre>
        
        <p><a href="debug-eval.php">返回除錯頁面</a></p>
    </div>
</body>
</html>
