<?php
session_start();
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>遊戲房間</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 使用 remixicon CDN 以支援 fi-* icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
 <!--icond-->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">

    <style>
        body { background: #181a1b; color: #fff; }
        .room-main { max-width: 700px; margin: 40px auto; background: #23272e; border-radius: 16px; box-shadow: 0 4px 32px #000a; padding: 32px 32px 24px 32px; }
        .room-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; }
        .room-title { font-size: 2.2rem; font-weight: bold; margin-bottom: 0; }
        .room-code-box { display: flex; align-items: center; gap: 12px; margin-top: 18px; }
        .room-code-label { font-size: 1.1rem; font-weight: 500; color: #bbb; }
        .room-code-value { font-size: 1.3rem; font-weight: bold; color: #fff; letter-spacing: 2px; }
        .btn-copy { background: #1769fa; color: #fff; border-radius: 8px; font-weight: 500; padding: 4px 18px; font-size: 1rem; }
        .room-count-box { background: #23272e; color: #fff; border-radius: 8px; padding: 8px 22px; font-size: 1.1rem; font-weight: 500; margin-left: auto; }
        .room-section-title { font-size: 1.25rem; font-weight: bold; margin-top: 32px; margin-bottom: 18px; }
        .member-list { margin-bottom: 30px; }
        .member-card { background: #232526; border-radius: 10px; padding: 18px 24px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #292929; }
        .member-info { display: flex; align-items: center; gap: 12px; }
        .member-icon { font-size: 1.5rem; }
        .crown { color: #ffd700; font-size: 1.5em; margin-right: 6px; vertical-align: middle; }
        .ready-icon { color: #4caf50; font-size: 1.3em; }
        .notready-icon { color: #f44336; font-size: 1.3em; }
        .btn-kick { background: #e53935; color: #fff; border-radius: 8px; font-size: 1rem; font-weight: 500; padding: 4px 18px; }
        .btn-disband { background: #b71c1c; color: #fff; border-radius: 8px; font-size: 1rem; font-weight: 500; padding: 4px 18px; margin-left: 10px; }
        .btn-setting { background: #444; color: #fff; border-radius: 8px; font-size: 1rem; font-weight: 500; padding: 4px 18px; margin-left: 10px; }
        .btn-battle { background: #1e88e5; color: #fff; border-radius: 8px; font-size: 1.1rem; font-weight: 600; padding: 8px 32px; margin-top: 18px; box-shadow: 0 2px 8px #0002; }
        .room-setting-box { background: #23272e; border-radius: 10px; padding: 24px 18px 18px 18px; border: 1px solid #23272e; margin-top: 30px; }
        .form-check-label, .form-label { color: #fff; }
        .form-control[readonly] { background: #353535; color: #fff; border: none; }
        @media (max-width: 600px) {
            .room-main { padding: 10px 2vw; }
            .room-title { font-size: 1.3rem; }
            .room-section-title { font-size: 1.1rem; }
            .member-card { padding: 10px 8px; }
            .room-setting-box { padding: 14px 6px 10px 6px; }
        }
    </style>
</head>
<body>
<div class="room-main">
    <div class="room-header">
        <div>
            <div class="room-title" id="roomName">房間名稱</div>
            <div class="room-code-box mt-2">
                <span class="room-code-label">房間代碼：</span>
                <span class="room-code-value" id="roomCode">------</span>
                <button class="btn btn-copy" id="btnCopyCode"><i class="fas fa-copy me-1"></i>複製代碼</button>
            </div>
        </div>
        <div class="room-count-box mt-3 mt-md-0" id="roomCountBox">
            目前人數 <span id="memberCount" style="color:#4a90e2;">0/4</span>
        </div>
    </div>
    <hr style="border-color:#333;">
    <div>
        <div class="room-section-title">房間成員</div>
        <div id="memberList" class="member-list"></div>
    </div>
    <hr style="border-color:#333;">
    <div class="room-section-title">房間設定</div>
    <div class="room-setting-box">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="privateRoom" disabled>
            <label class="form-check-label" for="privateRoom">私人房間</label>
        </div>
        <input type="text" class="form-control" id="roomPassword" placeholder="密碼" readonly>
    </div>
    <div class="d-flex justify-content-end mt-4 gap-2">
        <button id="btnSetting" class="btn btn-setting d-none"><i class="fas fa-gear"></i> 房間設定</button>
        <button id="btnDisband" class="btn btn-disband d-none"><i class="fas fa-trash"></i> 解散房間</button>
        <button id="btnStartBattle" class="btn btn-battle">
            <i class="fas fa-fire me-1"></i> 戰鬥開始
        </button>
    </div>
    <a href="index.php" class="btn btn-outline-light mt-4"><i class="fas fa-arrow-left me-1"></i> 返回大廳</a>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/js/all.min.js"></script>
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

function getQuery(name) {
    const url = new URL(window.location.href);
    return url.searchParams.get(name);
}

const code = getQuery('code');
const currentUser = <?php echo json_encode($username); ?>;
let roomOwner = null;
let maxPlayers = 4;

// 複製房間代碼
document.getElementById('btnCopyCode').onclick = function() {
    navigator.clipboard.writeText(code);
    Swal.fire({
        icon: 'success',
        title: '已複製房間代碼',
        html: `<b>${code}</b>`,
        timer: 1200,
        showConfirmButton: false
    });
};

// 取得房間資訊與成員
db.ref('rooms/' + code).on('value', snap => {
    const room = snap.val();
    if (!room) {
        Swal.fire({
            icon: 'error',
            title: '房間不存在或已被刪除',
            confirmButtonText: '返回大廳'
        }).then(() => {
            location.href = 'index.php';
        });
        return;
    }
    document.getElementById('roomName').textContent = room.name || '未命名房間';
    document.getElementById('roomCode').textContent = code;
    document.getElementById('privateRoom').checked = !!room.private;
    document.getElementById('roomPassword').value = room.password || '';
    maxPlayers = room.max_players || 4;

    // 修正房主取得方式
    // 如果 room.owner 沒有，預設第一位成員為房主
    let members = room.members ? Object.keys(room.members) : [];
    roomOwner = room.owner;
    if (!roomOwner && members.length > 0) {
        roomOwner = members[0];
    }

    document.getElementById('memberCount').textContent = `${members.length}/${maxPlayers}`;

    // 房主顯示設定按鈕和解散按鈕
    if (roomOwner === currentUser) {
        document.getElementById('btnSetting').classList.remove('d-none');
        document.getElementById('btnDisband').classList.remove('d-none');
    } else {
        document.getElementById('btnSetting').classList.add('d-none');
        document.getElementById('btnDisband').classList.add('d-none');
    }

    // 成員
    let html = '';
    members.forEach(name => {
        html += `<div class="member-card">
            <span class="member-info">
                ${roomOwner === name
                    ? '<i class="fi fi-br-home" title="房主"></i>'
                    : '<i class="fi fi-br-user member-icon" title="成員"></i>'
                }
                ${name}
            </span>
            ${
                (roomOwner === currentUser && name !== currentUser)
                ? `<button class="btn btn-kick btn-sm" onclick="kickMember('${name}')">踢出</button>`
                : ''
            }
        </div>`;
    });
    document.getElementById('memberList').innerHTML = html || '<div class="text-light">暫無成員</div>';
});

// 踢出成員
function kickMember(name) {
    Swal.fire({
        title: `確定要將 ${name} 踢出房間嗎？`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '踢出',
        cancelButtonText: '取消'
    }).then(result => {
        if (result.isConfirmed) {
            db.ref('rooms/' + code + '/members/' + name).remove();
        }
    });
}

// 解散房間
document.getElementById('btnDisband').onclick = function() {
    Swal.fire({
        title: '確定要解散這個房間嗎？',
        text: '所有成員都會被移除，房間將永久刪除！',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '解散',
        cancelButtonText: '取消'
    }).then(result => {
        if (result.isConfirmed) {
            db.ref('rooms/' + code).remove().then(() => {
                Swal.fire({icon:'success', title:'房間已解散'}).then(() => {
                    window.location.href = 'index.php';
                });
            });
        }
    });
};

// 房間設定（房主可切換公開/私人與密碼）
document.getElementById('btnSetting').onclick = function() {
    db.ref('rooms/' + code).once('value').then(snap => {
        const room = snap.val();
        Swal.fire({
            title: '房間設定',
            html:
                `<div class="form-check mb-2" style="text-align:left;">
                    <input class="form-check-input" type="checkbox" id="swal-private" ${room.private ? 'checked' : ''}>
                    <label class="form-check-label" for="swal-private">私人房間</label>
                </div>
                <input id="swal-password" class="swal2-input" placeholder="密碼" value="${room.password || ''}" ${room.private ? '' : 'disabled'}>`,
            focusConfirm: false,
            preConfirm: () => {
                const isPrivate = document.getElementById('swal-private').checked;
                const password = document.getElementById('swal-password').value.trim();
                if (isPrivate && !password) {
                    Swal.showValidationMessage('私人房間需填寫密碼');
                    return false;
                }
                return { isPrivate, password };
            },
            didOpen: () => {
                // 這裡要用 input event 或 change event
                document.getElementById('swal-private').addEventListener('change', function() {
                    const pwdInput = document.getElementById('swal-password');
                    pwdInput.disabled = !this.checked;
                    if (!this.checked) pwdInput.value = '';
                });
            },
            confirmButtonText: '儲存',
            showCancelButton: true
        }).then(result => {
            if (!result.isConfirmed || !result.value) return;
            db.ref('rooms/' + code).update({
                private: result.value.isPrivate,
                password: result.value.isPrivate ? result.value.password : ""
            }).then(() => {
                Swal.fire({icon:'success', title:'房間設定已更新'});
            });
        });
    });
};

// 戰鬥開始按鈕
document.getElementById('btnStartBattle').onclick = function() {
    window.location.href = 'battle_multi.html?code=' + encodeURIComponent(code);
};
</script>
</body>
</html>
