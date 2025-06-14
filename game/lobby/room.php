<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";
$invite_code = isset($_GET['code']) ? $_GET['code'] : '';

$db = new Database();
$pdo = $db->getConnection();

if (!$pdo) {
    die('資料庫連線失敗');
}

// 取得房間資訊
$stmt = $pdo->prepare("SELECT t.*, d.name AS dungeon_name FROM teams t LEFT JOIN dungeons d ON t.dungeon_id = d.id WHERE t.invite_code = ?");
$stmt->execute([$invite_code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

// 取得當前關卡名稱（假設有 current_level_id 欄位，且 levels 表有 name 欄位）
$current_level_name = '';
if (!empty($room['current_level_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM levels WHERE level_id = ?");
    $stmt->execute([$room['current_level_id']]);
    $level = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($level) {
        $current_level_name = $level['name'];
    }
}

if (!$room) {
    echo '<div class="container py-5 text-center text-light"><h2>房間不存在或已被刪除</h2><a href="index.php" class="btn btn-secondary mt-3">返回大廳</a></div>';
    exit;
}

// 取得房間成員
$stmt = $pdo->prepare("SELECT tm.user_id, p.username FROM team_members tm JOIN players p ON tm.user_id = p.player_id WHERE tm.team_id = ? ORDER BY tm.joined_at ASC, tm.member_id ASC");
$stmt->execute([$room['team_id']]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 房主（預設第一位成員）
$room_owner_id = $members[0]['user_id'] ?? null;

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
        // 先移除新房主，再重新插入（確保順序）
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
        header("Location: index.php");
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

// 重新取得房間資訊與成員
$stmt = $pdo->prepare("SELECT t.*, d.name AS dungeon_name FROM teams t LEFT JOIN dungeons d ON t.dungeon_id = d.id WHERE t.invite_code = ?");
$stmt->execute([$invite_code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT tm.user_id, p.username FROM team_members tm JOIN players p ON tm.user_id = p.player_id WHERE tm.team_id = ? ORDER BY tm.joined_at ASC, tm.member_id ASC");
$stmt->execute([$room['team_id']]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
$room_owner_id = $members[0]['user_id'] ?? null;
$member_count = count($members);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($room['room_name'] ?: '未命名房間'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #111;
            color: #fff;
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        .room-main {
            background: #232526;
            border-radius: 18px;
            box-shadow: 0 8px 32px #0007;
            max-width: 600px;
            min-width: 350px;
            margin: 40px auto;
            padding: 0;
            position: relative;
        }
        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 38px 38px 0 38px;
        }
        .room-title {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 8px;
        }
        .room-code-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .room-code-label {
            color: #bfc9d1;
            font-weight: 500;
        }
        .room-people {
            background: #181a1b;
            border-radius: 8px;
            padding: 6px 18px;
            font-weight: bold;
            color: #bfc9d1;
            font-size: 1.1rem;
        }
        .room-body {
            padding: 0 38px;
        }
        .room-member-title, .room-setting-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-top: 32px;
            margin-bottom: 10px;
            color: #fff;
        }
        .member-list {
            background: #363738;
            border-radius: 8px;
            padding: 10px 0;
            margin-bottom: 18px;
        }
        .member-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 24px;
            color: #fff;
        }
        .member-card:not(:last-child) {
            border-bottom: 1px solid #444;
        }
        .member-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .badge-owner {
            background: #2257e7;
            color: #fff;
            margin-right: 8px;
            font-size: 0.95em;
            padding: 2px 8px;
            border-radius: 6px;
        }
        .btn-kick {
            background: #e74c3c;
            color: #fff;
            border-radius: 6px;
            border: none;
            padding: 4px 18px;
            font-size: 1rem;
        }
        .btn-kick:hover {
            background: #c0392b;
        }
        .btn-transfer {
            background: #4a90e2;
            color: #fff;
            border-radius: 6px;
            border: none;
            padding: 4px 18px;
            margin-left: 8px;
            font-size: 1rem;
        }
        .btn-transfer:hover {
            background: #2257e7;
        }
        .room-setting-row {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-setting {
            margin-left: 10px;
        }
        .room-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 28px 38px 28px 38px;
        }
        .btn-battle {
            background: #2257e7;
            color: #fff;
            border-radius: 8px;
            min-width: 120px;
            font-weight: bold;
        }
        .btn-battle:active {
            filter: brightness(0.95);
        }
        @media (max-width: 600px) {
            .room-main {
                padding: 0;
                min-width: unset;
                max-width: 98vw;
            }
            .room-header, .room-body, .room-footer {
                padding: 18px 8px 0 8px;
            }
            .room-title {
                font-size: 1.3rem;
            }
            .room-people {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
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
    window.location.href = "battle_multi.php?code=" + encodeURIComponent(document.getElementById('roomCode').textContent);
}
</script>
