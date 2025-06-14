<?php
// 啟動 session 來追蹤用戶登入狀態
session_start();

// 設置網站基本資訊
$site_title = "Python 怪物村：AI 助教教你寫程式打怪獸！";
$site_description = "以魔物獵人風格學習 Python 程式設計";
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site_title ?></title>
    <meta name="description" content="<?= $site_description ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="landing-page">
    <div class="landing-container">
        <div class="landing-content">
            <img src="assets/images/dark-python-logo.png" alt="Python 怪物村" class="logo">
            <h1>Python 怪物村</h1>
            <h2>AI 助教教你寫程式打怪獸！</h2>
            <p>攜帶你的勇氣，深入地下城，用程式碼擊退邪惡怪物，成為最強的Python冒險者！</p>
            <button id="enter-village" class="cta-button">進入地下城</button>
        </div>
    </div>

    <!-- 登入/註冊模態框 -->
    <div id="auth-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div class="tabs">
                <button class="tab-button active" data-tab="login">登入世界</button>
                <button class="tab-button" data-tab="register">創建角色</button>
            </div>
            
            <div id="login-tab" class="tab-content active">
                <h3>回到冒險</h3>
                <form id="login-form">
                    <div class="form-group">
                        <label for="login-account">冒險者帳號</label>
                        <input type="text" id="login-account" name="account" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">通行密語</label>
                        <input type="password" id="login-password" name="password" required>
                    </div>
                    <button type="submit" class="submit-button">進入地下城</button>
                </form>
                <div class="error-message" id="login-error"></div>
            </div>
            
            <div id="register-tab" class="tab-content">
                <h3>成為新的冒險者</h3>
                <form id="register-form">
                    <div class="form-group">
                        <label for="register-username">冒險者名稱</label>
                        <input type="text" id="register-username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="register-account">冒險者帳號</label>
                        <input type="text" id="register-account" name="account" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">通行密語</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="register-confirm">確認密語</label>
                        <input type="password" id="register-confirm" name="confirm_password" required>
                    </div>
                    <button type="submit" class="submit-button">創建冒險者</button>
                </form>
                <div class="error-message" id="register-error"></div>
            </div>
        </div>
    </div>

    <!-- 功能按鈕 -->
    <div class="btn-group mb-4" style="gap:18px;">
        <button class="btn btn-dungeon" onclick="loadPanel('dungeon_list.php')">
            <i class="fas fa-dungeon"></i> 副本列表
        </button>
        <button class="btn btn-room" onclick="loadPanel('room_list.php')">
            <i class="fas fa-search"></i> 搜尋房間
        </button>
    </div>
    <style>
    .btn-group {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 18px;
        margin-bottom: 24px;
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
    </style>

    <script src="assets/js/auth.js"></script>
</body>
</html>
