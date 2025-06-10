<?php
session_start();
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>加入房間</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #181a1b; color: #fff; min-height: 100vh; }
        .join-room-box {
            background: #232526;
            border-radius: 18px;
            padding: 38px 38px 28px 38px;
            min-width: 350px;
            max-width: 420px;
            box-shadow: 0 8px 32px #0007;
        }
        .join-room-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 28px;
            color: #fff;
        }
        .form-label { color: #fff; font-weight: 500; }
        .form-control {
            background: #363738;
            color: #fff;
            border: none;
            margin-bottom: 18px;
        }
        .form-control:focus {
            background: #363738;
            color: #fff;
            border: 1.5px solid #4a90e2;
            box-shadow: none;
        }
        .btn-cancel {
            background: #444;
            color: #fff;
            border-radius: 8px;
            margin-right: 10px;
            min-width: 80px;
        }
        .btn-join {
            background: #16b364;
            color: #fff;
            border-radius: 8px;
            min-width: 80px;
        }
        .btn-join:active, .btn-cancel:active { filter: brightness(0.95); }
        @media (max-width: 600px) {
            .join-room-box { padding: 18px 8px 18px 8px; min-width: unset; max-width: 98vw; }
            .join-room-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
    <div class="join-room-box w-100">
        <form id="joinRoomForm" autocomplete="off">
            <div class="join-room-title text-center mb-4">加入房間</div>
            <div class="mb-3">
                <label for="roomCode" class="form-label">房間代碼</label>
                <input type="text" class="form-control" id="roomCode" name="room_code" placeholder="請輸入房間代碼" required>
            </div>
            <div class="mb-3">
                <label for="roomPassword" class="form-label">密碼（如有）</label>
                <input type="text" class="form-control" id="roomPassword" name="room_password" placeholder="密碼（若為私人房間需填）">
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-cancel" onclick="window.location.href='index.php'">取消</button>
                <button type="submit" class="btn btn-join">加入</button>
            </div>
        </form>
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

document.getElementById('joinRoomForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const code = document.getElementById('roomCode').value.trim();
    const password = document.getElementById('roomPassword').value.trim();
    if (!code) {
        Swal.fire({icon:'warning', title:'請輸入房間代碼'});
        return;
    }
    const user = <?php echo json_encode($username); ?>;
    db.ref('rooms/' + code).once('value').then(snap => {
        const room = snap.val();
        if (!room) return Swal.fire({icon:'error', title:'房間不存在'});
        if (room.private && room.password !== password) {
            Swal.fire({icon:'error', title:'密碼錯誤'});
            return;
        }
        const members = room.members ? Object.keys(room.members) : [];
        if (members.length >= (room.max_players || 4)) {
            Swal.fire({icon:'error', title:'房間已滿'});
            return;
        }
        // 已經是成員直接進入
        if (members.includes(user)) {
            window.location.href = 'room.php?code=' + code;
            return;
        }
        // 加入房間
        db.ref('rooms/' + code + '/members/' + user).set(true).then(() => {
            window.location.href = 'room.php?code=' + code;
        });
    });
});
</script>
</body>
</html>
