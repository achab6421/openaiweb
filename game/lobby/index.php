<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>多人副本大廳 - Python 怪物村</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/chapter.css">
    <link rel="stylesheet" href="../../assets/css/quest.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: url("../../assets/images/bg1.png") no-repeat center center fixed;
            background-size: cover;
            color: #ccc;
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        body::-webkit-scrollbar {
            display: none;             /* Chrome、Safari */
        }
        .main-container {
            min-height: 100vh;
            width: 100vw;
            position: relative;
            overflow: hidden;
        }
        .main-content {
            background: #191a1c;
            border-radius: 18px;
            margin: 32px 0 0 0;
            padding: 32px 32px 32px 32px;
            min-height: 80vh;
            width: 100%;
        }
        .dashboard-header {
            font-size: 2rem;
            background-color: rgba(26, 20, 16, 0.9);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            border: 1px solid var(--border-color);
        }
        .lobby-roomlist-panel {
            width: 100%;
            background: transparent;
            border-radius: 12px;
            min-height: 60vh;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
        }
        .btn-group button {
            margin-right: 12px;
        }
        .btn-dungeon, .btn-room {
            font-size: 1.15rem;
            font-weight: bold;
            padding: 12px 32px;
            border-radius: 12px;
            border: none;
            transition: background 0.18s, box-shadow 0.18s, transform 0.12s;
            box-shadow: 0 2px 12px #0004;
            letter-spacing: 1px;
            outline: none;
        }
        .btn-dungeon {
            background: linear-gradient(90deg, #8b0000 60%, #ff4000 100%);
            color: #fff;
        }
        .btn-dungeon:hover, .btn-dungeon:focus {
            background: linear-gradient(90deg, #a80000 60%, #ff6a00 100%);
            color: #fff;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 18px #ff400055;
        }
        .btn-room {
            background: linear-gradient(90deg, #ffb84d 60%, #ff4000 100%);
            color: #232526;
        }
        .btn-room:hover, .btn-room:focus {
            background: linear-gradient(90deg, #ffd580 60%, #ff6a00 100%);
            color: #232526;
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 18px #ffb84d55;
        }
        .btn-group i {
            margin-right: 8px;
            font-size: 1.2em;
            vertical-align: middle;
        }
        iframe {
            border: none;
            width: 100%;
            height: 70vh;
        }
        @media (max-width: 900px) {
            .main-container { display: flex; flex-direction: column; justify-content: center; align-items: center; }
            .main-content {
                padding: 12px 4px;
            }
        }
    </style>
</head>
<body>
    <!-- 返回主頁按鈕（右上角） -->
    <div style="position:fixed;top:28px;right:38px;z-index:100;">
        <a href="../../dashboard.php"
           class="btn"
           style="
                font-size:1.05rem;
                padding:6px 18px;
                border-radius:8px;
                background: linear-gradient(90deg,#232526 60%,#181210 100%);
                color:#ffe4b5;
                border: 2px solid #ffb84d;
                font-weight:bold;
                letter-spacing:1px;
                box-shadow:0 2px 8px #0006;
                transition:background 0.2s,border 0.2s;
           "
           onmouseover="this.style.background='#2d2113';this.style.color='#fffbe6';this.style.borderColor='#ffe4b5';"
           onmouseout="this.style.background='linear-gradient(90deg,#232526 60%,#181210 100%)';this.style.color='#ffe4b5';this.style.borderColor='#ffb84d';"
        >
            <i class="fas fa-arrow-left" style="margin-right:7px;"></i>返回主頁
        </a>
    </div>
    <!-- 背景圖片已在 body 設定 -->
    <div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;">
        <div style="background:rgba(30,20,10,0.82);border-radius:18px;padding:48px 38px 38px 38px;box-shadow:0 8px 32px #0007;display:flex;flex-direction:column;align-items:center;">
            <h1 style="color:#ffe4b5;font-size:2.2rem;font-weight:bold;letter-spacing:2px;margin-bottom:32px;text-shadow:0 2px 8px #0008;">
                歡迎來到 PYTHON 怪物村多人副本大廳
            </h1>
            <div style="display:flex;gap:32px;">
                <a href="room_list.php" class="btn btn-lg" style="background:#8b0000;color:#fff;font-size:1.3rem;padding:18px 48px;border-radius:12px;font-weight:bold;box-shadow:0 2px 8px #0006;letter-spacing:2px;">
                    搜尋房間
                </a>
                <a href="dungeon_list.php" class="btn btn-lg" style="background:#ffb84d;color:#232526;font-size:1.3rem;padding:18px 48px;border-radius:12px;font-weight:bold;box-shadow:0 2px 8px #0006;letter-spacing:2px;">
                    副本列表
                </a>
            </div>
        </div>
    </div>



    
</div>

<script>

</body>
</html>
