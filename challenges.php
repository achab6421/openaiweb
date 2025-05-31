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

// 取得用戶當前關卡
$current_level = $_SESSION["current_level"];

// 關閉連接
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>關卡練習 - Python教學網站</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .header-container {
            background: linear-gradient(135deg, #eb4d4b, #e056fd);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .challenge-card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .challenge-card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .difficulty-badge {
            font-size: 0.8em;
            margin-right: 5px;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .easy {
            background-color: #badc58;
            color: #333;
        }
        .medium {
            background-color: #f0932b;
            color: white;
        }
        .hard {
            background-color: #eb4d4b;
            color: white;
        }
        .locked {
            opacity: 0.5;
            pointer-events: none;
        }
        .locked-icon {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .btn-back {
            margin-bottom: 20px;
        }
        .progress-container {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .progress {
            height: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="dashboard.php">
            <img src="https://via.placeholder.com/40x40" width="30" height="30" class="d-inline-block align-top" alt="Logo">
            Python教學網站
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> 主選單</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><i class="fas fa-user-circle"></i> 個人資料</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> 登出</a>
                </li>
            </ul>
        </div>
    </nav>

    <a href="dashboard.php" class="btn btn-secondary btn-back"><i class="fas fa-arrow-left"></i> 返回主選單</a>

    <div class="header-container">
        <h1><i class="fas fa-tasks"></i> 關卡練習</h1>
        <p class="lead">通過各種難度的Python編程挑戰，提升您的問題解決能力。</p>
    </div>

    <div class="progress-container">
        <h4>進度追蹤</h4>
        <div class="progress">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($current_level / 10) * 100; ?>%" aria-valuenow="<?php echo $current_level; ?>" aria-valuemin="0" aria-valuemax="10">
                <?php echo $current_level; ?>/10 關卡
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 1) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 1: 第一個Python程式 <span class="badge difficulty-badge easy">簡單</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 1): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">Hello, World!</h5>
                            <p class="card-text">創建一個簡單的Python程式，印出 "Hello, World!" 並了解Python基本語法。</p>
                            <a href="challenge.php?id=1" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 2) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 2: 變數與運算 <span class="badge difficulty-badge easy">簡單</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 2): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">基本計算器</h5>
                            <p class="card-text">創建一個簡單的計算器程式，練習使用變數、輸入輸出和基本算術運算。</p>
                            <a href="challenge.php?id=2" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 3) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 3: 條件語句 <span class="badge difficulty-badge easy">簡單</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 3): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">判斷奇偶數</h5>
                            <p class="card-text">創建一個程式判斷輸入的數字是奇數還是偶數，練習使用if-else條件語句。</p>
                            <a href="challenge.php?id=3" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 4) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 4: 循環結構 <span class="badge difficulty-badge medium">中等</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 4): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">乘法表生成器</h5>
                            <p class="card-text">使用循環結構創建一個乘法表生成器，練習使用for和while循環。</p>
                            <a href="challenge.php?id=4" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 5) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 5: 列表操作 <span class="badge difficulty-badge medium">中等</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 5): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">列表處理與分析</h5>
                            <p class="card-text">創建一個程式處理列表數據，包括排序、過濾和基本統計計算。</p>
                            <a href="challenge.php?id=5" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 6) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 6: 函數應用 <span class="badge difficulty-badge medium">中等</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 6): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">自定義函數庫</h5>
                            <p class="card-text">創建一個包含多個實用函數的模組，練習函數定義和模組組織。</p>
                            <a href="challenge.php?id=6" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 7) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 7: 字典與JSON <span class="badge difficulty-badge hard">困難</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 7): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">數據管理系統</h5>
                            <p class="card-text">創建一個使用字典和JSON存儲數據的簡單管理系統。</p>
                            <a href="challenge.php?id=7" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card challenge-card <?php if($current_level < 8) echo 'locked'; ?>">
                    <div class="card-header">
                        關卡 8: 檔案操作 <span class="badge difficulty-badge hard">困難</span>
                    </div>
                    <div class="card-body">
                        <?php if($current_level < 8): ?>
                            <div class="text-center">
                                <i class="fas fa-lock locked-icon"></i>
                                <p>完成前一關卡解鎖</p>
                            </div>
                        <?php else: ?>
                            <h5 class="card-title">文字檔案處理工具</h5>
                            <p class="card-text">創建一個能夠讀取、分析和修改文字檔案的程式。</p>
                            <a href="challenge.php?id=8" class="btn btn-primary">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
