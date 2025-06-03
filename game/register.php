<?php
// 初始化會話
session_start();

// 如果用戶已經登入，直接導向主選單頁面
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: main.php");
    exit;
}

// 引入數據庫連接文件
require_once "../config/database_game.php";

// 定義變數並初始化為空值
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";

// 處理表單提交
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 驗證用戶名
    if(empty(trim($_POST["username"]))) {
        $username_err = "請輸入用戶名。";
    } elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "用戶名只能包含字母、數字和下劃線。";
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
        $sql = "INSERT INTO users (username, password, current_level, attack_power, defense_power) VALUES (?, ?, 1, 10, 5)";
         
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('assets/images/register-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            max-width: 450px;
            padding: 30px;
            margin: 80px auto;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h2 {
            color: #2E7D32;
            font-weight: bold;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .register-button {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            border: none;
            width: 100%;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .register-button:hover {
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            display: block;
        }
        
        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        .back-to-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-size: 1rem;
            text-decoration: none;
            padding: 8px 16px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .back-to-home:hover {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home"><i class="fas fa-home me-2"></i>返回首頁</a>
    
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <img src="assets/images/python-logo.png" alt="Python Logo" class="logo">
                <h2>加入 Python 怪物村</h2>
                <p class="text-muted">創建帳號開始你的程式冒險</p>
            </div>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">用戶名</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                    <div class="form-text">用戶名只能包含字母、數字和下劃線</div>
                </div>    
                <div class="mb-3">
                    <label for="password" class="form-label">密碼</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                    <div class="form-text">密碼必須至少有6個字符</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">確認密碼</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-success register-button">
                        <i class="fas fa-user-plus me-2"></i>創建帳號
                    </button>
                </div>
            </form>
            
            <div class="login-link">
                已經有帳號？<a href="login.php" class="text-success">立即登入</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
