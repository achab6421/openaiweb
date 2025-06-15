<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
$db = new Database();         // ✅ 確保這行執行了
$pdo = $db->getConnection();  // ✅ 這才會產生 $pdo
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
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
            scrollbar-width: none;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        body::-webkit-scrollbar {
            display: none; /* Chrome, Safari */
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
        .dungeon-select-bar {
            background: #232526;
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 32px;
            box-shadow: 0 2px 12px #0005;
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 480px;
        }
        .dungeon-select-bar label {
            color: #fff;
            font-weight: bold;
            margin-bottom: 0;
        }
        .dungeon-select-bar select {
            background: #363738;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 18px;
            font-size: 1.1rem;
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
<div class="container-fluid min-vh-100 d-flex flex-column align-items-center justify-content-center">
    <?php
    // 取得副本列表
    $dungeon_stmt = $pdo->prepare("SELECT id, name FROM dungeons ORDER BY id ASC");
    $dungeon_stmt->execute();
    $dungeon_list = $dungeon_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 取得預設選擇
    $selected_dungeon_id = isset($_GET['dungeon_id']) ? intval($_GET['dungeon_id']) : ($dungeon_list[0]['id'] ?? 0);
    ?>
    <form method="get" class="dungeon-select-bar mb-4">
        <label for="dungeon_id">選擇副本：</label>
        <select name="dungeon_id" id="dungeon_id" onchange="this.form.submit()">
            <?php foreach ($dungeon_list as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $selected_dungeon_id == $d['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="create-room-box w-100">
        <form id="createRoomForm" autocomplete="off">
            <div class="create-room-title text-center mb-4">建立房間</div>
            <input type="hidden" name="dungeon_id" value="<?= $selected_dungeon_id ?>">
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
                <button type="button" class="btn btn-cancel" onclick="window.location.href='dungeon_list.php'">取消</button>
                <button type="submit" class="btn btn-create">建立</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('createRoomForm');
    const roomPassword = document.getElementById('roomPassword');
    const privateRoom = document.getElementById('privateRoom');
    privateRoom.addEventListener('change', function () {
        roomPassword.disabled = !this.checked;
        if (!this.checked) roomPassword.value = '';
    });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('/OPENAIWEB/api/create_room_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '房間建立成功！',
                        html: `邀請碼：<b>${data.invite_code}</b><br>人數上限：${data.max_members}人`,
                        confirmButtonText: '進入房間'
                    }).then(() => {
                        window.location.href = `room.php?code=${data.invite_code}`;
                    });
                } else {
                    Swal.fire({icon:'error', title:'房間建立失敗', text: data.message || '未知錯誤'});
                }
            } catch (err) {
                console.error('無法解析 JSON：', text);
                Swal.fire({icon:'error', title:'格式錯誤', text:'伺服器未回傳正確格式'});
            }
        })
        .catch(err => {
            console.error('Fetch 錯誤：', err);
            Swal.fire({icon:'error', title:'建立失敗', text: err.message || '連線失敗'});
        });
    });
});
</script>
</body>
</html>