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
        $sql = "SELECT id, username, password, current_level, attack_power, defense_power FROM users WHERE username = ?";

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
                    $stmt->bind_result($id, $username, $hashed_password, $current_level, $attack_power, $defense_power);
                    if($stmt->fetch()) {
                        if(password_verify($password, $hashed_password)) {
                            // 密碼正確，啟動新的會話
                            session_start();

                            // 存儲數據到會話變量
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["current_level"] = $current_level;
                            $_SESSION["attack_power"] = $attack_power;
                            $_SESSION["defense_power"] = $defense_power;

                            // 重定向用戶到主選單頁面
                            header("location: main.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('assets/images/login-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            padding: 30px;
            margin: 100px auto;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: #2E7D32;
            font-weight: bold;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .login-button {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            border: none;
            width: 100%;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .login-button:hover {
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
        }
        
        .logo {
            width: 80px;
            height: 80px;
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
        <div class="login-container">
            <div class="login-header">
                <img src="assets/images/python-logo.png" alt="Python Logo" class="logo">
                <h2>Python 怪物村</h2>
                <p class="text-muted">登入以開始你的程式冒險</p>
            </div>

            <?php 
            if(!empty($login_err)){
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">用戶名</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <div class="invalid-feedback"><?php echo $username_err; ?></div>
                    </div>
                </div>    
                <div class="mb-3">
                    <label for="password" class="form-label">密碼</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <div class="invalid-feedback"><?php echo $password_err; ?></div>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-success login-button">
                        <i class="fas fa-sign-in-alt me-2"></i>登入
                    </button>
                </div>
            </form>
            
            <div class="register-link">
                還沒有帳號？<a href="register.php" class="text-success">立即註冊</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
