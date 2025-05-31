<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入，如果未登入則重定向到登入頁面
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: ../../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>實作導向學習 - AI 助教陪你學 Python</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .header-container {
            background: linear-gradient(135deg, #74ebd5, #ACB6E5);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .lesson-card {
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .lesson-card:hover {
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
        .lesson-tag {
            font-size: 0.8em;
            margin-right: 5px;
            padding: 3px 8px;
            border-radius: 10px;
        }
        .beginner {
            background-color: #badc58;
            color: #333;
        }
        .intermediate {
            background-color: #f0932b;
            color: white;
        }
        .advanced {
            background-color: #eb4d4b;
            color: white;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <a class="navbar-brand" href="../../dashboard.php">
            <img src="https://via.placeholder.com/40x40" width="30" height="30" class="d-inline-block align-top" alt="Logo">
            AI 助教陪你學 Python
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../../dashboard.php"><i class="fas fa-home"></i> 主選單</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../profile.php"><i class="fas fa-user-circle"></i> 個人資料</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../logout.php"><i class="fas fa-sign-out-alt"></i> 登出</a>
                </li>
            </ul>
        </div>
    </nav>

    <a href="../../dashboard.php" class="btn btn-secondary btn-back"><i class="fas fa-arrow-left"></i> 返回主選單</a>

    <div class="header-container">
        <h1><i class="fas fa-laptop-code"></i> 實作導向學習</h1>
        <p class="lead">透過實際案例學習Python編程，從基礎到進階技巧。</p>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        Python基礎入門 <span class="badge lesson-tag beginner">初學者</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">變數、數據類型與基本語法</h5>
                        <p class="card-text">學習Python的基礎概念，包括變數宣告、基本數據類型、運算符和簡單的控制流程。</p>
                        <a href="lessons/basics.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        流程控制 <span class="badge lesson-tag beginner">初學者</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">條件語句與循環</h5>
                        <p class="card-text">深入學習Python中的if-else條件語句、for和while循環以及如何控制程序流程。</p>
                        <a href="lessons/flow_control.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        函數與模組 <span class="badge lesson-tag beginner">初學者</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">自定義函數與內建模組</h5>
                        <p class="card-text">學習如何定義和使用函數，傳遞參數，以及利用Python的內建模組來擴展功能。</p>
                        <a href="lessons/functions.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        數據結構 <span class="badge lesson-tag intermediate">中級</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">列表、元組、字典與集合</h5>
                        <p class="card-text">深入理解Python的各種數據結構，學習如何操作和處理複雜的數據。</p>
                        <a href="lessons/data_structures.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        文件操作與異常處理 <span class="badge lesson-tag intermediate">中級</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">讀寫文件與錯誤處理</h5>
                        <p class="card-text">學習如何在Python中讀寫文件，以及如何使用try-except處理程序中可能出現的錯誤。</p>
                        <a href="lessons/files_exceptions.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        面向對象編程 <span class="badge lesson-tag advanced">進階</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">類、對象與繼承</h5>
                        <p class="card-text">進入Python的面向對象編程世界，學習如何定義類、創建對象以及實現繼承和多態。</p>
                        <a href="lessons/oop.php" class="btn btn-primary">開始學習</a>
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
