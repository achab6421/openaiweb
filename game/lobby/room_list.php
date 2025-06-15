<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
$username = isset($_SESSION["username"]) ? $_SESSION["username"] : "訪客";

$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT * FROM teams ORDER BY created_at DESC");
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 搜尋選單處理
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$my_room_ids = [];
if ($filter === 'my') {
    // 查詢我已加入的房間 team_id
    $stmt = $pdo->prepare("SELECT team_id FROM team_members WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $my_room_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// 過濾房間
$filtered_rooms = array_filter($rooms, function($room) use ($filter, $my_room_ids) {
    if ($filter === 'public') return $room['is_public'];
    if ($filter === 'private') return !$room['is_public'];
    if ($filter === 'my') return in_array($room['team_id'], $my_room_ids);
    return true;
});

// 分頁設定
$rooms_per_row = 5;
$rows_per_page = 3;
$rooms_per_page = $rooms_per_row * $rows_per_page; // 15
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$total_rooms = count($filtered_rooms);
$total_pages = ceil($total_rooms / $rooms_per_page);
$start = ($page - 1) * $rooms_per_page;
$rooms_page = array_slice(array_values($filtered_rooms), $start, $rooms_per_page);

// 取得所有副本清單
$dungeon_stmt = $pdo->prepare("SELECT id, name FROM dungeons ORDER BY id ASC");
$dungeon_stmt->execute();
$dungeon_list = $dungeon_stmt->fetchAll(PDO::FETCH_ASSOC);
$selected_dungeon_id = isset($_GET['dungeon_id']) ? intval($_GET['dungeon_id']) : 0;

// 篩選副本
if ($selected_dungeon_id) {
    $filtered_rooms = array_filter($filtered_rooms, function($room) use ($selected_dungeon_id) {
        return isset($room['dungeon_id']) && $room['dungeon_id'] == $selected_dungeon_id;
    });
    // 重新分頁
    $total_rooms = count($filtered_rooms);
    $total_pages = ceil($total_rooms / $rooms_per_page);
    $start = ($page - 1) * $rooms_per_page;
    $rooms_page = array_slice(array_values($filtered_rooms), $start, $rooms_per_page);
    // 重新取得副本名稱
    $room_team_ids = array_column($rooms_page, 'team_id');
    $dungeon_names = [];
    if ($room_team_ids) {
        $in = str_repeat('?,', count($room_team_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT t.team_id, d.name AS dungeon_name
            FROM teams t
            LEFT JOIN dungeons d ON t.dungeon_id = d.id
            WHERE t.team_id IN ($in)
        ");
        $stmt->execute($room_team_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $dungeon_names[$row['team_id']] = $row['dungeon_name'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>房間列表</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #8b0000;
            --secondary-color: #3d1c02;
            --dark-bg: #1a1a1a;
            --dark-accent: #2d2d2d;
            --light-accent: #4a3f35;
            --text-light: #d4c8b8;
            --text-dark: #242424;
            --highlight: #ff4000;
            --shadow-color: rgba(0, 0, 0, 0.7);
            --border-color: #534b42;
        }
        body {
            background: var(--dark-bg);
            color: var(--text-light);
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        .room-list-modal {
            background: var(--dark-accent);
            border-radius: 18px;
            padding: 38px 38px 28px 38px;
            max-width: 1200px;
            box-shadow: 0 8px 32px var(--shadow-color);
            margin: 40px auto;
        }
        .room-list-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 28px;
            color: var(--text-light);
        }
        .room-card-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .room-card-row {
            display: flex;
            gap: 24px;
            justify-content: flex-start;
        }
        .room-card {
            background: var(--light-accent);
            border-radius: 16px;
            box-shadow: 0 4px 16px var(--shadow-color);
            padding: 32px 32px 24px 32px;
            min-width: 180px;
            max-width: 220px;
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 0;
        }
        .room-card .room-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-light);
            margin-bottom: 12px;
            text-align: center;
            text-shadow: 0 2px 8px #0008;
        }
        .badge-public {
            background: var(--primary-color);
            color: var(--text-light);
            font-size: 0.98em;
            padding: 4px 16px;
            border-radius: 12px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .badge-private {
            background: var(--highlight);
            color: var(--text-light);
            font-size: 0.98em;
            padding: 4px 16px;
            border-radius: 12px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .badge-private i {
            margin-left: 4px;
        }
        .room-card .room-count {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .room-card .btn-join,
        .modal-footer .btn-join {
            background: var(--primary-color);
            color: #fff !important;
            border-radius: 8px;
            min-width: 120px;
            font-size: 1.1rem;
            font-weight: bold;
            margin-top: 10px;
        }
        .room-card .btn-join:active,
        .modal-footer .btn-join:active {
            filter: brightness(0.95);
        }
        .room-card .text-danger {
            color: var(--highlight);
            margin-top: 10px;
            font-size: 1.1rem;
        }
        @media (max-width: 1200px) {
            .room-list-modal {
                max-width: 98vw;
                padding: 18px 8px 18px 8px;
            }
            .room-card-list {
                gap: 12px;
            }
            .room-card-row {
                gap: 12px;
            }
            .room-card {
                min-width: 120px;
                max-width: 100vw;
                padding: 18px 8px 18px 8px;
            }
        }
        @media (max-width: 900px) {
            .room-card-row {
                flex-wrap: wrap;
            }
            .room-card {
                min-width: 40vw;
                max-width: 48vw;
            }
        }
        @media (max-width: 700px) {
            .room-card-row {
                flex-direction: column;
                gap: 12px;
            }
            .room-card {
                min-width: 90vw;
                max-width: 98vw;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
    <div class="room-list-modal w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="room-list-title">房間列表</div>
            <div class="d-flex align-items-center gap-3">
                <form method="get" class="d-flex align-items-center gap-2 mb-0">
                    <label class="me-2 text-light">副本：</label>
                    <select name="dungeon_id" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="0">全部副本</option>
                        <?php foreach ($dungeon_list as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $selected_dungeon_id == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <!-- 保留原有 filter 分類 -->
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                </form>
                <a href="create_room.php" class="btn btn-success" style="min-width:120px;">＋ 建立新房間</a>
            </div>
        </div>
        <!-- 搜尋選單 -->
        <form method="get" class="mb-4 d-flex flex-wrap align-items-center gap-2">
            <label class="me-2 text-light">顯示：</label>
            <select name="filter" class="form-select w-auto" onchange="this.form.submit()">
                <option value="all" <?php if($filter==='all')echo'selected';?>>全部</option>
                <option value="public" <?php if($filter==='public')echo'selected';?>>公開房間</option>
                <option value="private" <?php if($filter==='private')echo'selected';?>>私人房間</option>
                <option value="my" <?php if($filter==='my')echo'selected';?>>我的房間</option>
            </select>
            <?php if ($selected_dungeon_id): ?>
                <input type="hidden" name="dungeon_id" value="<?= $selected_dungeon_id ?>">
            <?php endif; ?>
        </form>
        <div class="room-card-list">
            <?php if (empty($rooms_page)): ?>
                <div class="text-center text-secondary w-100">目前沒有房間</div>
            <?php else: ?>
                <?php
                $chunks = array_chunk($rooms_page, $rooms_per_row);
                foreach ($chunks as $row_rooms):
                ?>
                <div class="room-card-row">
                    <?php foreach ($row_rooms as $room): ?>
                        <?php
                        // 查詢目前人數
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM team_members WHERE team_id = ?");
                        $stmt->execute([$room['team_id']]);
                        $member_count = $stmt->fetchColumn();
                        // 是否已在房間
                        $in_room = false;
                        if ($user_id) {
                            $stmt = $pdo->prepare("SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ?");
                            $stmt->execute([$room['team_id'], $user_id]);
                            $in_room = $stmt->fetchColumn() ? true : false;
                        }
                        $dungeon_name = isset($dungeon_names[$room['team_id']]) ? $dungeon_names[$room['team_id']] : '';
                        ?>
                        <div class="room-card">
                            <div class="room-name"><?php echo htmlspecialchars($room['room_name'] ?? '未命名'); ?></div>
                            <?php if ($dungeon_name): ?>
                                <div class="room-dungeon-name" style="color:#ffb84d;font-weight:bold;margin-bottom:8px;">
                                    副本：<?php echo htmlspecialchars($dungeon_name); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($room['is_public']): ?>
                                <span class="badge-public">公開房間</span>
                            <?php else: ?>
                                <span class="badge-private">私人房間 <i class="bi bi-lock-fill"></i></span>
                            <?php endif; ?>
                            <div class="room-count">人數：<b><?php echo $member_count; ?> / <?php echo intval($room['max_members']); ?></b></div>
                            <?php if ($in_room): ?>
                                <a href="room.php?code=<?php echo urlencode($room['invite_code']); ?>" class="btn btn-join">回到房間</a>
                            <?php elseif ($member_count < $room['max_members']): ?>
                                <?php if ($room['is_public']): ?>
                                    <a href="room.php?code=<?php echo urlencode($room['invite_code']); ?>" class="btn btn-join">加入房間</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-join" onclick="showPwdModal('<?php echo htmlspecialchars($room['invite_code']); ?>')">加入房間</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-danger">已滿</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <!-- 分頁按鈕 -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page-1; ?>">&laquo; 上一頁</a>
                    </li>
                <?php endif; ?>
                <?php for ($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $page+1; ?>">下一頁 &raquo;</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="d-flex justify-content-between mt-4">
        </div>
    </div>
</div>

<!-- 密碼輸入 Modal -->
<div class="modal fade" id="pwdModal" tabindex="-1" aria-labelledby="pwdModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" id="pwdForm">
      <div class="modal-content bg-dark text-light">
        <div class="modal-header">
          <h5 class="modal-title" id="pwdModalLabel">輸入房間密碼</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="invite_code" id="modalInviteCode">
          <div class="mb-3">
            <label for="modalRoomPassword" class="form-label">房間密碼</label>
            <input type="text" class="form-control" id="modalRoomPassword" name="room_password" required>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-center">
          <button type="submit" class="btn btn-join" style="min-width:100px;">加入</button>
          <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">取消</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showPwdModal(invite_code) {
    document.getElementById('modalInviteCode').value = invite_code;
    document.getElementById('modalRoomPassword').value = '';
    var modal = new bootstrap.Modal(document.getElementById('pwdModal'));
    modal.show();
}
document.getElementById('pwdForm').onsubmit = function(e) {
    e.preventDefault();
    var code = document.getElementById('modalInviteCode').value;
    var pwd = document.getElementById('modalRoomPassword').value;
    // 送出到 join_room.php，直接帶密碼，不需再輸入房間代碼
    var form = document.createElement('form');
    form.method = 'post';
    form.action = 'join_room.php?code=' + encodeURIComponent(code);
    var inputPwd = document.createElement('input');
    inputPwd.type = 'hidden';
    inputPwd.name = 'room_password';
    inputPwd.value = pwd;
    form.appendChild(inputPwd);
    document.body.appendChild(form);
    form.submit();
};
</script>
</body>
</html>
