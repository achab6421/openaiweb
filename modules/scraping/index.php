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
    <title>爬蟲練習 - AI 助教陪你學 Python</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .header-container {
            background: linear-gradient(135deg, #a29bfe, #6c5ce7);
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
        <h1><i class="fas fa-spider"></i> 爬蟲練習</h1>
        <p class="lead">學習如何使用Python抓取和分析網路數據，建立實用的爬蟲工具。</p>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        HTTP基礎與網頁結構 <span class="badge lesson-tag beginner">初學者</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">了解網頁結構與HTTP請求</h5>
                        <p class="card-text">認識HTML、CSS和JavaScript基礎，以及HTTP請求的工作原理，為爬蟲開發打基礎。</p>
                        <a href="lessons/http_basics.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        Requests入門 <span class="badge lesson-tag beginner">初學者</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">使用Requests套件進行HTTP請求</h5>
                        <p class="card-text">學習如何使用Python的Requests套件發送GET和POST請求，獲取網頁內容。</p>
                        <a href="lessons/requests.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        BeautifulSoup解析HTML <span class="badge lesson-tag intermediate">中級</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">使用BeautifulSoup解析網頁內容</h5>
                        <p class="card-text">掌握如何使用BeautifulSoup套件解析HTML文檔，提取所需的數據元素。</p>
                        <a href="lessons/beautiful_soup.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        Selenium自動化瀏覽器 <span class="badge lesson-tag intermediate">中級</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">使用Selenium控制瀏覽器進行爬蟲</h5>
                        <p class="card-text">學習如何使用Selenium自動化瀏覽器，爬取動態加載的網頁內容。</p>
                        <a href="lessons/selenium.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        數據存儲與處理 <span class="badge lesson-tag intermediate">中級</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">爬取數據的存儲與處理方法</h5>
                        <p class="card-text">掌握如何將爬取的數據保存為CSV、JSON等格式，以及使用Pandas進行數據處理。</p>
                        <a href="lessons/data_storage.php" class="btn btn-primary">開始學習</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card lesson-card">
                    <div class="card-header">
                        高級爬蟲技術 <span class="badge lesson-tag advanced">進階</span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">反爬蟲對策與多線程爬蟲</h5>
                        <p class="card-text">學習如何處理網站反爬蟲機制，使用代理IP、模擬用戶行為，以及使用多線程提高爬蟲效率。</p>
                        <a href="lessons/advanced.php" class="btn btn-primary">開始學習</a>
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
