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
            background: #181a1b;
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
<div class="main-container quest-page">
    <!-- 側邊欄 -->
    <div class="sidebar">
        <div class="player-info">
            <img src="../../assets/images/hunter-avatar.png" alt="獵人頭像" class="player-avatar">
            <h3><?= htmlspecialchars($_SESSION['username']) ?></h3>
            <div class="player-stats">
                <div class="stat">
                    <span>等級</span>
                    <strong><?= $_SESSION['level'] ?></strong>
                </div>
                <div class="stat">
                    <span>攻擊力</span>
                    <strong><?= $_SESSION['attack_power'] ?></strong>
                </div>
                <div class="stat">
                    <span>血量</span>
                    <strong><?= $_SESSION['base_hp'] ?></strong>
                </div>
            </div>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="../../dashboard.php" class="active">主頁</a></li>
                <li><a href="../../profile.php">獵人檔案</a></li>
                <li><a href="../../achievements.php">成就系統</a></li>
                <li><a href="../../game/maze/index.php">秘密任務</a></li>
                <li><a href="index.php">多人副本</a></li>
                <li><a href="../../api/logout.php">登出</a></li>
            </ul>
        </nav>
    </div>

    <!-- 主內容 -->
    <div class="main-content">
        <header class="dashboard-header">
            <h1>歡迎來到 PYTHON 怪物村多人副本大廳！</h1>
            <p>選擇開始你的狩獵旅程！</p>
        </header>

        <!-- 功能按鈕 -->
        <div class="btn-group mb-4" style="gap:18px;">
            <button class="btn btn-dungeon" onclick="loadPanel('dungeon_list.php')">
                <i class="fas fa-dungeon"></i> 副本列表
            </button>
            <button class="btn btn-room" onclick="loadPanel('room_list.php')">
                <i class="fas fa-search"></i> 搜尋房間
            </button>
        </div>

        <!-- 內嵌區塊 -->
        <div class="lobby-roomlist-panel">
            <iframe id="panelFrame" src="dungeon_list.php" title="副本面板"></iframe>
        </div>
    </div>
</div>

<script>
function loadPanel(page) {
    document.getElementById('panelFrame').src = page;
}
</script>
</body>
</html>
