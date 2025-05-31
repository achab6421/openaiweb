<?php
// 初始化會話
session_start();

// 如果用戶已經登入，直接導向主選單頁面
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// 引入數據庫連接文件
require_once "config/database.php";

// 定義變數並初始化為空值
$username = $password = "";
$username_err = $password_err = $login_err = "";

// 處理表單提交
if($_SERVER["REQUEST_METHOD"] == "POST") {

    // 檢查用戶名
    if(empty(trim($_POST["username"]))) {
        $username_err = "請輸入用戶名。";
    } else {
        $username = trim($_POST["username"]);
    }

    // 檢查密碼
    if(empty(trim($_POST["password"]))) {
        $password_err = "請輸入密碼。";
    } else {
        $password = trim($_POST["password"]);
    }

    // 驗證憑據
    if(empty($username_err) && empty($password_err)) {
        // 準備查詢語句
        $sql = "SELECT id, username, password, current_level FROM users WHERE username = ?";

        if($stmt = $conn->prepare($sql)) {
            // 綁定變量到預處理語句
            $stmt->bind_param("s", $param_username);

            // 設置參數
            $param_username = $username;

            // 執行預處理語句
            if($stmt->execute()) {
                // 存儲結果
                $stmt->store_result();

                // 檢查用戶是否存在，如果是則驗證密碼
                if($stmt->num_rows == 1) {
                    // 綁定結果變量
                    $stmt->bind_result($id, $username, $hashed_password, $current_level);
                    if($stmt->fetch()) {
                        if(password_verify($password, $hashed_password)) {
                            // 密碼正確，啟動新的會話
                            session_start();

                            // 存儲數據到會話變量
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["current_level"] = $current_level;

                            // 重定向用戶到主選單頁面
                            header("location: dashboard.php");
                        } else {
                            // 密碼不正確
                            $login_err = "用戶名或密碼不正確。";
                        }
                    }
                } else {
                    // 用戶不存在
                    $login_err = "用戶名或密碼不正確。";
                }
            } else {
                echo "發生錯誤，請稍後再試。";
            }

            // 關閉語句
            $stmt->close();
        }
    }

    // 關閉連接
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>登入 - AI 助教陪你學 Python</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 100px auto;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #343a40;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>AI 助教陪你學 Python</h2>
        <p>請填寫您的憑據以登入。</p>

        <?php 
        if(!empty($login_err)){
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>用戶名</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary btn-block" value="登入">
            </div>
            <p>還沒有帳號？ <a href="register.php">現在註冊</a></p>
        </form>
    </div>
</body>
</html>
