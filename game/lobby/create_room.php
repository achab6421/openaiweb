<?php
session_start();
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>建立房間</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #111;
            color: #fff;
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        .create-room-box {
            background: #232526;
            border-radius: 18px;
            padding: 38px 38px 28px 38px;
            min-width: 350px;
            max-width: 420px;
            box-shadow: 0 8px 32px #0007;
        }
        .create-room-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 28px;
            color: #fff;
        }
        .form-label {
            color: #fff;
            font-weight: 500;
        }
        .form-control, .form-select {
            background: #363738;
            color: #fff;
            border: none;
            margin-bottom: 18px;
        }
        .form-control:focus, .form-select:focus {
            background: #363738;
            color: #fff;
            border: 1.5px solid #4a90e2;
            box-shadow: none;
        }
        .form-check-label {
            color: #fff;
            font-weight: 400;
        }
        .btn-cancel {
            background: #444;
            color: #fff;
            border-radius: 8px;
            margin-right: 10px;
            min-width: 80px;
        }
        .btn-create {
            background: #2257e7;
            color: #fff;
            border-radius: 8px;
            min-width: 80px;
        }
        .btn-create:active, .btn-cancel:active {
            filter: brightness(0.95);
        }
        .form-check-input:checked {
            background-color: #2257e7;
            border-color: #2257e7;
        }
        .form-check-input {
            border-radius: 3px;
        }
        @media (max-width: 600px) {
            .create-room-box {
                padding: 18px 8px 18px 8px;
                min-width: unset;
                max-width: 98vw;
            }
            .create-room-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
    <div class="create-room-box w-100">
        <form id="createRoomForm" autocomplete="off">
            <div class="create-room-title text-center mb-4">建立房間</div>
            <div class="mb-3">
                <label for="roomName" class="form-label">房間名稱</label>
                <input type="text" class="form-control" id="roomName" name="room_name" placeholder="請輸入房間名稱" required>
            </div>
            <div class="mb-3 form-check">
                <input class="form-check-input" type="checkbox" id="privateRoom" name="private_room">
                <label class="form-check-label" for="privateRoom">私人房間</label>
            </div>
            <div class="mb-3">
                <label for="roomPassword" class="form-label">密碼</label>
                <input type="text" class="form-control" id="roomPassword" name="room_password" placeholder="密碼" disabled>
            </div>
            <div class="mb-3">
                <label for="maxPlayers" class="form-label">人數上限</label>
                <select class="form-select" name="max_players" id="maxPlayers" required>
                    <option value="2">2人</option>
                    <option value="3">3人</option>
                    <option value="4">4人</option>
                </select>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-cancel" onclick="window.location.href='index.php'">取消</button>
                <button type="submit" class="btn btn-create">建立</button>
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

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('createRoomForm');
    const roomPassword = document.getElementById('roomPassword');
    const privateRoom = document.getElementById('privateRoom');
    const maxPlayers = document.getElementById('maxPlayers');

    privateRoom.addEventListener('change', function () {
        roomPassword.disabled = !this.checked;
        if (!this.checked) roomPassword.value = '';
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const name = form.querySelector('[name="room_name"]').value.trim();
        const isPrivate = form.querySelector('[name="private_room"]').checked;
        const password = form.querySelector('[name="room_password"]').value.trim();
        const maxPlayersVal = form.querySelector('[name="max_players"]').value;
        const roomId = Math.random().toString().slice(2, 9);

        // firebase 寫入
        db.ref('rooms/' + roomId).set({
            name: name,
            members: { ["<?php echo addslashes($username); ?>"]: true },
            max_players: maxPlayersVal,
            private: isPrivate,
            password: isPrivate ? password : "",
            createdAt: Date.now()
        }).then(() => {
            Swal.fire({
                icon: 'success',
                title: '房間建立成功！',
                html: `<div>房間ID：<b>${roomId}</b><br>人數上限：${maxPlayersVal}人</div>`,
                confirmButtonText: '進入房間'
            }).then(() => {
                window.location.href = `room.php?code=${roomId}`;
            });
        }).catch(() => {
            Swal.fire({icon:'error', title:'房間建立失敗'});
        });
    });
});
</script>
</body>
</html>
