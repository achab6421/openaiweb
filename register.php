<?php
// 引入數據庫連接文件
require_once "config/database.php";

// 定義變數並初始化為空值
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";

// 處理表單提交
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 驗證用戶名
    if(empty(trim($_POST["username"]))) {
        $username_err = "請輸入用戶名。";
    } else {
        // 準備查詢語句
        $sql = "SELECT id FROM users WHERE username = ?";

        if($stmt = $conn->prepare($sql)) {
            // 綁定變量到預處理語句
            $stmt->bind_param("s", $param_username);

            // 設置參數
            $param_username = trim($_POST["username"]);

            // 執行預處理語句
            if($stmt->execute()) {
                // 存儲結果
                $stmt->store_result();

                if($stmt->num_rows == 1) {
                    $username_err = "此用戶名已被使用。";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "發生錯誤，請稍後再試。";
            }

            // 關閉語句
            $stmt->close();
        }
    }

    // 驗證密碼
    if(empty(trim($_POST["password"]))) {
        $password_err = "請輸入密碼。";
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "密碼必須至少有6個字符。";
    } else {
        $password = trim($_POST["password"]);
    }

    // 驗證確認密碼
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "請確認密碼。";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "密碼不匹配。";
        }
    }

    // 檢查輸入錯誤，然後再插入數據庫
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // 準備插入語句
        $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
         
        if($stmt = $conn->prepare($sql)) {
            // 綁定變量到預處理語句
            $stmt->bind_param("ss", $param_username, $param_password);
            
            // 設置參數
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // 創建密碼的哈希值
            
            // 執行預處理語句
            if($stmt->execute()) {
                // 重定向到登入頁面
                header("location: login.php");
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
    <title>註冊 - AI 助教陪你學 Python</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .wrapper {
            width: 360px;
            padding: 20px;
            margin: 50px auto;
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
        <p>請填寫此表格來創建一個帳號。</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>用戶名</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>密碼</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label>確認密碼</label>
                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="提交">
                <input type="reset" class="btn btn-secondary ml-2" value="重置">
            </div>
            <p>已經有一個帳號？ <a href="login.php">在這裡登入</a></p>
        </form>
    </div>    
</body>
</html>
