<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入，如果未登入則重定向到登入頁面
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// 引入數據庫連接文件
require_once "config/database.php";

// 取得用戶最新的關卡信息
$sql = "SELECT current_level FROM users WHERE id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["id"]);
    if($stmt->execute()){
        $stmt->store_result();
        if($stmt->num_rows == 1){
            $stmt->bind_result($current_level);
            $stmt->fetch();
            $_SESSION["current_level"] = $current_level; // 更新會話中的關卡信息
        }
    }
    $stmt->close();
}

// 關閉連接
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>AI 助教陪你學 Python：一站式學習語法、pip 與爬蟲技術</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .welcome-container {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .feature-card {
            flex: 0 0 31%;
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(90deg, #74ebd5, #ACB6E5);
            color: white;
            font-weight: bold;
            border-bottom: none;
            padding: 15px;
            font-size: 1.2em;
        }
        .card-body {
            padding: 20px;
        }
        .btn-menu {
            background-color: #6c5ce7;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-menu:hover {
            background-color: #5649d1;
            transform: scale(1.05);
            color: white;
        }
        .level-badge {
            background-color: #4834d4;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .icon-feature {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #6c5ce7;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="#">
            <img src="https://via.placeholder.com/40x40" width="30" height="30" class="d-inline-block align-top" alt="Logo">
            AI 助教陪你學 Python
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i> 個人資料</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> 登出</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="welcome-container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>歡迎回來，<?php echo htmlspecialchars($_SESSION["username"]); ?>！</h1>
                <p class="lead">繼續您的Python學習之旅。</p>
            </div>
            <div class="col-md-4 text-right">
                <div class="level-badge">
                    <i class="fas fa-trophy"></i> 目前關卡: <?php echo htmlspecialchars($_SESSION["current_level"]); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card-container">
        <div class="card feature-card">
            <div class="card-header">
                <i class="fas fa-code"></i> 實作導向
            </div>
            <div class="card-body text-center">
                <div class="icon-feature">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <p>透過實際案例學習Python編程，從基礎到進階技巧。</p>
                <a href="modules/practical/index.php" class="btn btn-menu">進入學習</a>
            </div>
        </div>
        
        <div class="card feature-card">
            <div class="card-header">
                <i class="fas fa-spider"></i> 爬蟲練習
            </div>
            <div class="card-body text-center">
                <div class="icon-feature">
                    <i class="fas fa-search"></i>
                </div>
                <p>學習如何使用Python抓取和分析網路數據，建立實用的爬蟲工具。</p>
                <a href="modules/scraping/index.php" class="btn btn-menu">開始練習</a>
            </div>
        </div>
        
        <div class="card feature-card">
            <div class="card-header">
                <i class="fas fa-tasks"></i> 關卡練習
            </div>
            <div class="card-body text-center">
                <div class="icon-feature">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <p>通過各種難度的Python編程挑戰，提升您的問題解決能力。</p>
                <a href="modules/challenges/index.php" class="btn btn-menu">挑戰關卡</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
