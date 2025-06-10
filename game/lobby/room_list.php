<?php
session_start();
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>房間列表</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body { background: #181a1b; color: #fff; min-height: 100vh; }
        .room-list-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 28px;
            color: #fff;
        }
        .room-card {
            background: #232526;
            border-radius: 16px;
            padding: 28px 18px 18px 18px;
            box-shadow: 0 2px 12px #0004;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
            min-height: 220px;
        }
        .room-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #fff;
            text-align: center;
        }
        .badge-public {
            background: #16753b;
            color: #fff;
            font-size: 1em;
            border-radius: 8px;
            padding: 4px 14px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .badge-private {
            background: #b91c1c;
            color: #fff;
            font-size: 1em;
            border-radius: 8px;
            padding: 4px 14px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .room-count {
            font-size: 1.1rem;
            margin-bottom: 18px;
            color: #fff;
            text-align: center;
        }
        .btn-join {
            background: #2257e7;
            color: #fff;
            border-radius: 8px;
            min-width: 120px;
            font-size: 1.05rem;
            font-weight: 500;
            margin-top: auto;
        }
        .btn-join:active { filter: brightness(0.95); }
        .btn-back {
            background: #444;
            color: #fff;
            border-radius: 8px;
            min-width: 80px;
            margin-top: 32px;
        }
        @media (max-width: 991px) {
            .room-card { min-height: 200px; }
        }
        @media (max-width: 767px) {
            .room-card { min-height: 180px; }
        }
        @media (max-width: 575px) {
            .room-card { min-height: unset; }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="room-list-title text-center mb-4">房間列表</div>
    <div id="roomCardsRow" class="row g-4"></div>
    <div class="d-flex justify-content-end">
        <button class="btn btn-back" onclick="window.location.href='index.php'">返回</button>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
<script>
const firebaseConfig = {
  apiKey: "AIzaSyCCLkT0VSweTF1-w_ecMybR7WdnvHs0oKA",
  authDomain: "openai-dbd3b.firebaseapp.com",
  databaseURL: "https://openai-dbd3b-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "openai-dbd3b",
  storageBucket: "openai-dbd3b.appspot.com",
  messagingSenderId: "977828405782",
  appId: "1:977828405782:web:eeb71ec2d11c7edfa10b37",
  measurementId: "G-Y5DJ7B9LXM"
};
firebase.initializeApp(firebaseConfig);
const db = firebase.database();

const currentUser = <?php echo json_encode($username); ?>;

function renderRoomCards(rooms) {
    let html = '';
    let hasRoom = false;
    Object.values(rooms).forEach(room => {
        const members = room.members ? Object.keys(room.members) : [];
        // 顯示公開房間，或自己已加入的私人房間
        if (room.private) {
            if (!members.includes(currentUser)) return;
        }
        hasRoom = true;
        html += `
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="room-card h-100">
                <div class="room-name">${room.name || '未命名房間'}</div>
                ${
                    room.private
                    ? `<span class="badge-private">私人房間 <i class="fas fa-lock"></i></span>`
                    : `<span class="badge-public">公開房間</span>`
                }
                <div class="room-count">人數：<b>${members.length}</b> / <b>${room.max_players || 4}</b></div>
                <button class="btn btn-join" onclick="window.location.href='room.php?code=${room.code || ''}'">加入房間</button>
            </div>
        </div>
        `;
    });
    document.getElementById('roomCardsRow').innerHTML = hasRoom ? html : '<div class="text-light text-center">目前沒有可加入的房間</div>';
}

db.ref('rooms').on('value', snap => {
    const rooms = snap.val() || {};
    renderRoomCards(rooms);
});
</script>
</body>
</html>
