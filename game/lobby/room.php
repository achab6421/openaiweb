<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
// ✅ 修正：從 GET 或 POST 皆可取得 invite_code
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

// 加入房間處理（限不是房主也不在房間的人）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['kick_user_id']) && !isset($_POST['transfer_owner_id']) && !isset($_POST['disband_room']) && !isset($_POST['update_setting']) && !isset($_POST['action'])) {
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

// 取得關卡名稱
$current_level_name = '';
if (!empty($room['current_level_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM levels WHERE level_id = ?");
    $stmt->execute([$room['current_level_id']]);
    $level = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($level) {
        $current_level_name = $level['name'];
    }
}

// 取得成員資料
$stmt = $pdo->prepare("SELECT tm.user_id, p.username FROM team_members tm JOIN players p ON tm.user_id = p.player_id WHERE tm.team_id = ? ORDER BY tm.joined_at ASC, tm.member_id ASC");
$stmt->execute([$room['team_id']]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$room_owner_id = $members[0]['user_id'] ?? null;
$member_count = count($members);

// 處理踢人
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kick_user_id'])) {
    if ($user_id == $room_owner_id && $_POST['kick_user_id'] != $room_owner_id) {
        $kick_id = intval($_POST['kick_user_id']);
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$room['team_id'], $kick_id]);
        header("Location: room.php?code=" . urlencode($invite_code));
        exit;
    }
}

// 處理轉讓房主
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_owner_id'])) {
    if ($user_id == $room_owner_id) {
        $new_owner_id = intval($_POST['transfer_owner_id']);
        $stmt = $pdo->prepare("DELETE FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$room['team_id'], $new_owner_id]);
        $stmt = $pdo->prepare("INSERT INTO team_members (user_id, team_id, joined_at) VALUES (?, ?, NOW())");
        $stmt->execute([$new_owner_id, $room['team_id']]);
        header("Location: room.php?code=" . urlencode($invite_code));
        exit;
    }
}

// 處理解散房間
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disband_room'])) {
    if ($user_id == $room_owner_id) {
        $stmt = $pdo->prepare("DELETE FROM teams WHERE team_id = ?");
        $stmt->execute([$room['team_id']]);
        header("Location: room_list.php");
        exit;
    }
}

// 處理房間設定
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_setting'])) {
    if ($user_id == $room_owner_id) {
        $is_private = isset($_POST['private_room']) ? 1 : 0;
        $room_password = $is_private ? trim($_POST['room_password'] ?? '') : '';
        $stmt = $pdo->prepare("UPDATE teams SET is_public = ?, room_password = ? WHERE team_id = ?");
        $stmt->execute([$is_private ? 0 : 1, $room_password, $room['team_id']]);
        header("Location: room.php?code=" . urlencode($invite_code));
        exit;
    }
}

// ✅ 處理開始遊戲（使用原始 invite_code 變數）
if (
    (isset($_POST['action']) && $_POST['action'] === 'start') ||
    (isset($_GET['action']) && $_GET['action'] === 'start')
) {
    header("Location: battle_multi.php?code=" . urlencode($invite_code));
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($room['room_name'] ?: '未命名房間'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/room.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.ROOM_USER_ID = <?= json_encode($user_id) ?>;
        window.ROOM_MAX_MEMBERS = <?= json_encode(intval($room['max_members'])) ?>;
    </script>
    <script src="../../assets/js/room.js"></script>
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
        <!-- Header: 左上角/右上角 -->
        <div class="room-header">
            <div>
                <div class="room-title" id="roomName"><?php echo htmlspecialchars($room['room_name'] ?: '未命名房間'); ?></div>
                <div class="room-code-row mb-2">
                    <span class="room-code-label">房間代碼：</span>
                    <span id="roomCode" style="font-weight:bold;font-size:1.2rem;"><?php echo htmlspecialchars($room['invite_code']); ?></span>
                    <button id="btnCopyCode" class="btn btn-primary btn-sm ms-2">複製代碼</button>
                </div>
            </div>
            <div class="d-flex flex-column align-items-end">
                <div class="room-count" style="background:#232526;border-radius:8px;padding:6px 18px;font-weight:bold;font-size:1.1rem;color:#fff;">
                    目前人數 <?php echo $member_count; ?> / <?php echo intval($room['max_members']); ?>
                </div>
                <?php if (!empty($room['dungeon_name'])): ?>
                    <div class="room-dungeon-name" style="margin-top:8px;background:#ff2d2d;border-radius:8px;padding:6px 18px;font-size:1.05rem;color:#fff;font-weight:bold;min-width:120px;text-align:center;">
                        當前副本：<?php echo htmlspecialchars($room['dungeon_name']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Body: 中間/下方 -->
        <div class="room-body">
            <div class="room-member-title">房間成員</div>
            <div class="member-list" id="memberList">
                <?php foreach ($members as $m): ?>
                    <div class="member-card">
                        <span class="member-info">
                            <?php if ($room_owner_id == $m['user_id']): ?>
                                <span class="badge-owner">房主</span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($m['username']); ?>
                        </span>
                        <?php if ($room_owner_id == $user_id && $m['user_id'] != $room_owner_id): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="kick_user_id" value="<?php echo $m['user_id']; ?>">
                                <button type="submit" class="btn btn-kick btn-sm">踢除</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="transfer_owner_id" value="<?php echo $m['user_id']; ?>">
                                <button type="submit" class="btn btn-transfer btn-sm">轉讓房主</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="room-setting-title">房間設定</div>
            <?php if ($user_id == $room_owner_id): ?>
                <form method="post" class="room-setting-row">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="privateRoom" name="private_room" <?php echo $room['is_public'] ? '' : 'checked'; ?>>
                        <label class="form-check-label" for="privateRoom">私人房間</label>
                    </div>
                    <input type="text" class="form-control" name="room_password" placeholder="密碼" value="<?php echo htmlspecialchars($room['room_password'] ?? ''); ?>" style="width:180px;" <?php echo $room['is_public'] ? 'disabled' : ''; ?>>
                    <button type="submit" name="update_setting" class="btn btn-setting btn-info">儲存設定</button>
                </form>
            <?php else: ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="privateRoom" disabled <?php echo $room['is_public'] ? '' : 'checked'; ?>>
                    <label class="form-check-label" for="privateRoom">私人房間</label>
                </div>
                <input type="text" class="form-control mb-2" name="room_password" placeholder="密碼" value="<?php echo htmlspecialchars($room['room_password'] ?? ''); ?>" style="width:180px;" disabled>
            <?php endif; ?>
        </div>
        <!-- Footer: 左下角/右下角 -->
        <div class="room-footer">
            <a href="room_list.php" class="btn btn-outline-light">&larr; 返回房間列表</a>
            <button type="button" class="btn btn-battle" onclick="startBattle()">&#9889; 開始遊戲</button>
        </div>
    </div>
</div>
</body>
</html>
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
// 房間密碼複製
const pwdBox = document.getElementById('roomPasswordBox');
if (pwdBox) {
    pwdBox.onclick = function() {
        const pwd = document.getElementById('roomPasswordText').textContent;
        navigator.clipboard.writeText(pwd);
        const copied = document.getElementById('pwdCopied');
        if (copied) {
            copied.style.display = 'block';
            setTimeout(() => { copied.style.display = 'none'; }, 1200);
        }
    }
}
function startBattle() {
    window.open(
        "battle_multi.php?code=" + encodeURIComponent(document.getElementById('roomCode').textContent),
        "_blank"
    );
}
// 自動刷新房間成員列表
setInterval(function() {
    var code = "<?php echo htmlspecialchars($room['invite_code']); ?>";
    fetch('get_room_members.php?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.members)) {
                let html = '';
                data.members.forEach(function(m, idx) {
                    html += `<div class="member-card">
                        <span class="member-info">` +
                        (data.room_owner_id == m.user_id ? `<span class="badge-owner">房主</span>` : '') +
                        `${m.username}</span>`;
                    // 只有自己是房主時才顯示踢除/轉讓按鈕
                    <?php if ($user_id == $room_owner_id): ?>
                    if (m.user_id != data.room_owner_id) {
                        html += `<form method="post" style="display:inline;">
                            <input type="hidden" name="kick_user_id" value="${m.user_id}">
                            <button type="submit" class="btn btn-kick btn-sm">踢除</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="transfer_owner_id" value="${m.user_id}">
                            <button type="submit" class="btn btn-transfer btn-sm">轉讓房主</button>
                        </form>`;
                    }
                    <?php endif; ?>
                    html += `</div>`;
                });
                document.getElementById('memberList').innerHTML = html;
                // 更新人數顯示
                if (document.querySelector('.room-count')) {
                    document.querySelector('.room-count').innerHTML =
                        `目前人數 ${data.members.length} / <?php echo intval($room['max_members']); ?>`;
                }
            }
        });
}, 3000); // 每3秒刷新一次
</script>
</body>
</html>
                </div>
                <?php if (!empty($room['dungeon_name'])): ?>
                    <div class="room-dungeon-name" style="margin-top:8px;background:#ff2d2d;border-radius:8px;padding:6px 18px;font-size:1.05rem;color:#fff;font-weight:bold;min-width:120px;text-align:center;">
                        當前副本：<?php echo htmlspecialchars($room['dungeon_name']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Body: 中間/下方 -->
        <div class="room-body">
            <div class="room-member-title">房間成員</div>
            <div class="member-list" id="memberList">
                <?php foreach ($members as $m): ?>
                    <div class="member-card">
                        <span class="member-info">
                            <?php if ($room_owner_id == $m['user_id']): ?>
                                <span class="badge-owner">房主</span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($m['username']); ?>
                        </span>
                        <?php if ($room_owner_id == $user_id && $m['user_id'] != $room_owner_id): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="kick_user_id" value="<?php echo $m['user_id']; ?>">
                                <button type="submit" class="btn btn-kick btn-sm">踢除</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="transfer_owner_id" value="<?php echo $m['user_id']; ?>">
                                <button type="submit" class="btn btn-transfer btn-sm">轉讓房主</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="room-setting-title">房間設定</div>
            <?php if ($user_id == $room_owner_id): ?>
                <form method="post" class="room-setting-row">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="privateRoom" name="private_room" <?php echo $room['is_public'] ? '' : 'checked'; ?>>
                        <label class="form-check-label" for="privateRoom">私人房間</label>
                    </div>
                    <input type="text" class="form-control" name="room_password" placeholder="密碼" value="<?php echo htmlspecialchars($room['room_password'] ?? ''); ?>" style="width:180px;" <?php echo $room['is_public'] ? 'disabled' : ''; ?>>
                    <button type="submit" name="update_setting" class="btn btn-setting btn-info">儲存設定</button>
                </form>
            <?php else: ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="privateRoom" disabled <?php echo $room['is_public'] ? '' : 'checked'; ?>>
                    <label class="form-check-label" for="privateRoom">私人房間</label>
                </div>
                <input type="text" class="form-control mb-2" name="room_password" placeholder="密碼" value="<?php echo htmlspecialchars($room['room_password'] ?? ''); ?>" style="width:180px;" disabled>
            <?php endif; ?>
        </div>
        <!-- Footer: 左下角/右下角 -->
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
// 房間密碼複製
const pwdBox = document.getElementById('roomPasswordBox');
if (pwdBox) {
    pwdBox.onclick = function() {
        const pwd = document.getElementById('roomPasswordText').textContent;
        navigator.clipboard.writeText(pwd);
        const copied = document.getElementById('pwdCopied');
        if (copied) {
            copied.style.display = 'block';
            setTimeout(() => { copied.style.display = 'none'; }, 1200);
        }
    }
}
function startBattle() {
    window.open(
        "battle_multi.php?code=" + encodeURIComponent(document.getElementById('roomCode').textContent),
        "_blank"
    );
}
// 自動刷新房間成員列表
setInterval(function() {
    var code = "<?php echo htmlspecialchars($room['invite_code']); ?>";
    fetch('get_room_members.php?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.members)) {
                let html = '';
                data.members.forEach(function(m, idx) {
                    html += `<div class="member-card">
                        <span class="member-info">` +
                        (data.room_owner_id == m.user_id ? `<span class="badge-owner">房主</span>` : '') +
                        `${m.username}</span>`;
                    // 只有自己是房主時才顯示踢除/轉讓按鈕
                    <?php if ($user_id == $room_owner_id): ?>
                    if (m.user_id != data.room_owner_id) {
                        html += `<form method="post" style="display:inline;">
                            <input type="hidden" name="kick_user_id" value="${m.user_id}">
                            <button type="submit" class="btn btn-kick btn-sm">踢除</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="transfer_owner_id" value="${m.user_id}">
                            <button type="submit" class="btn btn-transfer btn-sm">轉讓房主</button>
                        </form>`;
                    }
                    <?php endif; ?>
                    html += `</div>`;
                });
                document.getElementById('memberList').innerHTML = html;
                // 更新人數顯示
                if (document.querySelector('.room-count')) {
                    document.querySelector('.room-count').innerHTML =
                        `目前人數 ${data.members.length} / <?php echo intval($room['max_members']); ?>`;
                }
            }
        });
}, 3000); // 每3秒刷新一次
</script>
