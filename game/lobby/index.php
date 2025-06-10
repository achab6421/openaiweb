<?php
session_start();
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: #181a1b;
            color: #ccc;
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        .main-container {
            min-height: 100vh;
            width: 100vw;
            position: relative;
            overflow: hidden;
        }
        .left-title {
            position: absolute;
            left: 6vw;
            top: 40vh;
            transform: translateY(-50%);
            z-index: 2;
        }
        .game-title {
            font-size: 4rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 20px;
            text-shadow: 2px 2px 12px #000a;
            position: relative;
            top: -190px;
            left:190px
        }
        .game-title i {
            font-size: 3rem;
            margin-right: 10px;
        }
        .right-panel {
            position: absolute;
            right: 25vw;
            top: 16vh;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            z-index: 2;
        }
        .menu-btn {
            width: 260px;
            font-size: 1.6rem;
            font-weight: 500;
            border-radius: 16px;
            margin-bottom: 28px;
            box-shadow: 0 6px 18px #0003;
            transition: filter 0.15s, transform 0.1s;
            border: none;
        }
        .menu-btn:last-child {
            margin-bottom: 0;
        }
        .menu-btn:active {
            filter: brightness(0.95);
            transform: translateY(2px);
        }
        .btn-create { background: #0356d6; color: #fff; }
        .btn-join { background: #16b364; color: #fff; }
        .btn-list { background: #c89d0c; color: #fff; }
        @media (max-width: 900px) {
            .left-title, .right-panel {
                position: static;
                transform: none;
                align-items: center;
                text-align: center;
            }
            .main-container { display: flex; flex-direction: column; justify-content: center; align-items: center; }
            .game-title { font-size: 2.2rem; }
            .menu-btn { width: 180px; font-size: 1.1rem; }
            .right-panel { align-items: center; margin-top: 30px; }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="left-title">
        <div class="game-title">
            <i class="fas fa-gamepad"></i>
            Python 怪物村
        </div>
    </div>
    <div class="right-panel">
        <button class="btn menu-btn btn-create" onclick="window.location.href='create_room.php'">建立房間</button>
        <button class="btn menu-btn btn-join" onclick="window.location.href='join_room.php'">加入房間</button>
        <button class="btn menu-btn btn-list" onclick="window.location.href='room_list.php'">房間列表</button>
        <button class="btn menu-btn btn-outline-light" style="background:#444;color:#fff;margin-top:10px;" onclick="window.location.href='/ai/dashboard.php'">
            <i class="fas fa-arrow-left me-1"></i> 返回主頁
        </button>
    </div>
</div>
</body>
</html>
