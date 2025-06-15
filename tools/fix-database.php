<?php
// 資料庫修復工具 - 新增或修復缺失的資料表列
session_start();

// 簡易安全檢查
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// 只要求登入即可訪問
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if (!$isLoggedIn) {
    echo "請先登入";
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 設定標題
    $pageTitle = "資料庫修復工具";
?>
<!DOCTYPE html>
<html lang="zh-tw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .success { 
            color: green;
            background-color: #e8f5e9;
            padding: 5px;
            border-radius: 3px;
        }
        .error { 
            color: red;
            background-color: #ffebee;
            padding: 5px;
            border-radius: 3px;
        }
        .warning { 
            color: orange;
            background-color: #fff3e0;
            padding: 5px;
            border-radius: 3px;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            margin: 5px;
        }
        .button.danger {
            background-color: #f44336;
        }
        .button.primary {
            background-color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>資料庫修復工具</h1>

        <?php if (isset($_GET['action']) && $_GET['action'] == 'fix_experience'): ?>
            <h2>修復 players 表中的 experience 欄位</h2>

            <?php
            // 檢查 experience 欄位是否存在
            $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
            $checkColumnStmt = $db->query($checkColumnQuery);
            $experienceColumnExists = ($checkColumnStmt->rowCount() > 0);

            if ($experienceColumnExists) {
                echo "<div class='success'>players 表已有 experience 欄位，無需修復。</div>";
            } else {
                try {
                    // 新增 experience 欄位
                    $addColumnQuery = "ALTER TABLE players ADD COLUMN experience INT NOT NULL DEFAULT 0";
                    $db->exec($addColumnQuery);
                    echo "<div class='success'>成功新增 experience 欄位到 players 表。</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>新增欄位時發生錯誤: " . $e->getMessage() . "</div>";
                }
            }
            ?>

            <p><a href="fix-database.php" class="button primary">返回</a></p>
        <?php else: ?>
            <h2>資料庫結構檢查</h2>

            <table>
                <thead>
                    <tr>
                        <th>檢查項目</th>
                        <th>狀態</th>
                        <th>動作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 檢查 players 表中的 experience 欄位
                    $checkExpQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
                    $expExists = false;
                    
                    try {
                        $checkExpStmt = $db->query($checkExpQuery);
                        $expExists = ($checkExpStmt->rowCount() > 0);
                    } catch (PDOException $e) {
                        echo "<tr><td>檢查 experience 欄位</td><td><span class='error'>錯誤: " . $e->getMessage() . "</span></td><td></td></tr>";
                    }
                    
                    if (isset($checkExpStmt)) {
                        if ($expExists) {
                            echo "<tr><td>檢查 players.experience 欄位</td><td><span class='success'>已存在</span></td><td></td></tr>";
                        } else {
                            echo "<tr><td>檢查 players.experience 欄位</td><td><span class='warning'>不存在</span></td><td><a href='?action=fix_experience' class='button'>修復</a></td></tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <h2>資料庫操作</h2>
            <p>這裡列出可能需要的資料庫操作按鈕。</p>
            
            <div>
                <form method="post" action="../battle-test.php">
                    <button type="submit" class="button primary">前往戰鬥測試頁面</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
} catch (PDOException $e) {
    echo "資料庫連線失敗: " . $e->getMessage();
}
?>
