<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
$invite_code = $_POST['invite_code'] ?? ($_GET['code'] ?? '');

$db = new Database();
$pdo = $db->getConnection();

if (!$pdo) {
    die('資料庫連線失敗');
}

// 取得房間資訊
$stmt = $pdo->prepare("SELECT t.*, d.name AS dungeon_name FROM teams t LEFT JOIN dungeons d ON t.dungeon_id = d.id WHERE t.invite_code = ?");
$stmt->execute([$invite_code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

$showPwdError = false;
if (!$room) {
    echo '<div class="container py-5 text-center text-light"><h2>房間不存在或已被刪除</h2><a href="room_list.php" class="btn btn-secondary mt-3">返回房間列表</a></div>';
    exit;
}

// 取得成員資料
$stmt = $pdo->prepare("SELECT tm.user_id, p.username FROM team_members tm JOIN players p ON tm.user_id = p.player_id WHERE tm.team_id = ? ORDER BY tm.joined_at ASC, tm.member_id ASC");
$stmt->execute([$room['team_id']]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$room_owner_id = $members[0]['user_id'] ?? null;
$member_count = count($members);

// ===================== 處理房間相關 POST =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 踢人
    if (isset($_POST['kick_user_id']) && $user_id == $room_owner_id && $_POST['kick_user_id'] != $room_owner_id) {
        $kick_id = intval($_POST['kick_user_id']);
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$room['team_id'], $kick_id]);
        header("Location: room.php?code=" . urlencode($invite_code));
        exit;
    }

    // 解散房間
    if (isset($_POST['disband_room']) && $user_id == $room_owner_id) {
        $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = ?");
        $stmt->execute([$room['team_id']]);
        header("Location: room_list.php");
        exit;
    }

    // 房間設定（公開/私密、密碼）
    if (isset($_POST['update_setting']) && $user_id == $room_owner_id) {
        $is_private = isset($_POST['private_room']) ? 1 : 0;
        $room_password = $is_private ? trim($_POST['room_password'] ?? '') : '';
        $stmt = $pdo->prepare("UPDATE teams SET is_public = ?, room_password = ? WHERE team_id = ?");
        $stmt->execute([$is_private ? 0 : 1, $room_password, $room['team_id']]);
        header("Location: room.php?code=" . urlencode($invite_code));
        exit;
    }

    // 加入房間（只在尚未加入且非房主情況下）
    if (!isset($_POST['kick_user_id']) && !isset($_POST['disband_room']) && !isset($_POST['update_setting']) && !isset($_POST['action'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$room['team_id'], $user_id]);
        $alreadyInRoom = $stmt->fetchColumn() > 0;
        if (!$alreadyInRoom) {
            if (!$room['is_public']) {
                $input_pwd = trim($_POST['room_password'] ?? '');
                if ($input_pwd !== $room['room_password']) {
                    $showPwdError = true;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, joined_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$room['team_id'], $user_id]);
                    header("Location: room.php?code=" . urlencode($invite_code));
                    exit;
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, joined_at) VALUES (?, ?, NOW())");
                $stmt->execute([$room['team_id'], $user_id]);
                header("Location: room.php?code=" . urlencode($invite_code));
                exit;
            }
        }
    }
}

// 開始遊戲
if (
    (isset($_POST['action']) && $_POST['action'] === 'start') ||
    (isset($_GET['action']) && $_GET['action'] === 'start')
) {
    header("Location: battle_multi.php?code=" . urlencode($invite_code));
    exit;
}
// ===================== POST 區塊結束 =====================
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($room['room_name'] ?: '未命名房間') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/room.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.ROOM_USER_ID = <?= json_encode($user_id) ?>;
        window.ROOM_OWNER_ID = <?= json_encode($room_owner_id) ?>;
        window.ROOM_MAX_MEMBERS = <?= json_encode(intval($room['max_members'])) ?>;
        window.ROOM_IS_OWNER = <?= ($user_id == $room_owner_id) ? 'true' : 'false' ?>;
    </script>
</head>
<body>
<?php if ($showPwdError): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: '密碼錯誤',
            text: '請確認您輸入的房間密碼正確！',
            confirmButtonText: '返回房間列表'
        }).then(() => {
            window.location.href = 'room_list.php';
        });
    });
</script>
<?php endif; ?>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
    <div class="room-main w-100">
        <div class="room-header">
            <div>
                <div class="room-title" id="roomName"><?= htmlspecialchars($room['room_name'] ?: '未命名房間') ?></div>
                <div class="room-code-row mb-2">
                    <span class="room-code-label">房間代碼：</span>
                    <span id="roomCode" style="font-weight:bold;font-size:1.2rem;"><?= htmlspecialchars($room['invite_code']) ?></span>
                    <button id="btnCopyCode" class="btn btn-primary btn-sm ms-2">複製代碼</button>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end">
                <div class="room-count" style="background:#232526;border-radius:8px;padding:6px 18px;font-weight:bold;font-size:1.1rem;color:#fff;">
                    目前人數 <span id="memberCount"><?= $member_count ?></span> / <?= intval($room['max_members']) ?>
                </div>
                <?php if (!empty($room['dungeon_name'])): ?>
                    <div class="room-dungeon-name" style="margin-top:8px;background:#ff2d2d;border-radius:8px;padding:6px 18px;font-size:1.05rem;color:#fff;font-weight:bold;min-width:120px;text-align:center;">
                        當前副本：<?= htmlspecialchars($room['dungeon_name']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="room-body">
            <div class="room-member-title">房間成員</div>
            <div class="member-list" id="memberList"></div>

            <div class="room-setting-title">房間設定</div>
            <?php if ($user_id == $room_owner_id): ?>
                <form method="post" class="room-setting-row">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="privateRoom" name="private_room" <?= $room['is_public'] ? '' : 'checked' ?>>
                        <label class="form-check-label" for="privateRoom">私人房間</label>
                    </div>
                    <input type="text" class="form-control" name="room_password" placeholder="密碼" value="<?= htmlspecialchars($room['room_password'] ?? '') ?>" style="width:180px;" <?= $room['is_public'] ? 'disabled' : '' ?>>
                    <button type="submit" name="update_setting" class="btn btn-setting btn-info">儲存設定</button>
                </form>
                <form method="post" style="margin-top:10px;">
                    <button type="submit" name="disband_room" class="btn btn-danger btn-sm">解散房間</button>
                </form>
            <?php else: ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="privateRoom" disabled <?= $room['is_public'] ? '' : 'checked' ?>>
                    <label class="form-check-label" for="privateRoom">私人房間</label>
                </div>
                <input type="text" class="form-control mb-2" name="room_password" placeholder="密碼" value="<?= htmlspecialchars($room['room_password'] ?? '') ?>" style="width:180px;" disabled>
            <?php endif; ?>
        </div>
        <div class="room-footer">
            <a href="room_list.php" class="btn btn-outline-light">&larr; 返回房間列表</a>
            <button type="button" class="btn btn-battle" onclick="startBattle()">&#9889; 開始遊戲</button>
        </div>
    </div>
</div>
<script>
document.getElementById('btnCopyCode').onclick = function() {
    const code = document.getElementById('roomCode').textContent;
    navigator.clipboard.writeText(code);
    alert('已複製房間代碼: ' + code);
};
document.getElementById('privateRoom')?.addEventListener('change', function() {
    document.querySelector('input[name="room_password"]').disabled = !this.checked;
    if (!this.checked) document.querySelector('input[name="room_password"]').value = '';
});
function startBattle() {
    window.open(
        "battle_multi.php?code=" + encodeURIComponent(document.getElementById('roomCode').textContent),
        "_blank"
    );
}
function renderMembers(data) {
    let html = '';
    data.members.forEach(function(m) {
        html += `<div class="member-card">
            <span class="member-info">` +
            (data.room_owner_id == m.user_id ? `<span class="badge-owner">房主</span>` : '') +
            `${m.username}</span>`;
        // 只有自己是房主且不是自己才會顯示踢除
        if (window.ROOM_IS_OWNER && m.user_id != data.room_owner_id) {
            html += `<form method="post" style="display:inline;">
                <input type="hidden" name="kick_user_id" value="${m.user_id}">
                <button type="submit" class="btn btn-kick btn-sm">踢除</button>
            </form>`;
        }
        html += `</div>`;
    });
    document.getElementById('memberList').innerHTML = html;
    document.getElementById('memberCount').textContent = data.members.length;
}
// 首次渲染
renderMembers({
    members: <?= json_encode($members) ?>,
    room_owner_id: <?= json_encode($room_owner_id) ?>
});
// 自動刷新
setInterval(function() {
    var code = "<?= htmlspecialchars($room['invite_code']) ?>";
    fetch('get_room_members.php?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.members)) {
                renderMembers(data);
            }
        });
}, 3000);
</script>
</body>
</html>
