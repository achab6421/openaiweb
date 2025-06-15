<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

// 取得副本列表
$stmt = $pdo->prepare("SELECT * FROM dungeons ORDER BY id ASC");
$stmt->execute();
$dungeons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 查詢玩家已解鎖的副本
$unlocked_dungeon_ids = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT dungeon_id FROM player_dungeon_records WHERE player_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $unlocked_dungeon_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>副本列表</title>
    <link rel="stylesheet" href="../../assets/css/dungeon_list.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #1a1a1a;
            color: #ede2d0;
            min-height: 100vh;
            margin: 0;
            font-family: 'Noto Sans TC', 'Microsoft JhengHei', Arial, sans-serif;
        }
        .dungeon-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 38px 38px 0 38px;
        }
        .dungeon-title {
            font-size: 2.1rem;
            font-weight: bold;
            color: #ffe4b5;
            letter-spacing: 2px;
        }
        .btn-back-lobby {
            font-size: 1.05rem;
            padding: 7px 22px;
            border-radius: 10px;
            background: linear-gradient(90deg,#232526 60%,#181210 100%);
            color: #ffe4b5;
            border: 2px solid #ffb84d;
            font-weight: bold;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px #0006;
            transition: background 0.2s, border 0.2s, color 0.2s;
            text-decoration: none;
        }
        .btn-back-lobby:hover {
            background: #2d2113;
            color: #fffbe6;
            border-color: #ffe4b5;
        }
        .dungeon-list-cards {
            max-width: 1400px;
            margin: 38px auto 0 auto;
            display: flex;
            flex-direction: column;
            gap: 38px;
        }
        .dungeon-card-row {
            display: flex;
            justify-content: center;
        }
        .dungeon-card {
            background: rgba(26, 20, 16, 0.93);
            /* 模擬紅色框的長條效果，但不畫紅線 */
            box-shadow: 0 0 0 12px rgba(255,64,0,0.13), 0 8px 32px #000a;
            border-radius: 18px;
            padding: 48px 64px 38px 64px;
            display: flex;
            align-items: flex-start;
            gap: 48px;
            min-width: 900px;
            max-width: 1200px;
            margin: 0 auto;
            transition: box-shadow 0.2s;
        }
        .dungeon-card:hover {
            box-shadow: 0 0 0 18px rgba(255,64,0,0.18), 0 12px 48px #000a;
        }
        .dungeon-card-index {
            font-size: 2.7rem;
            font-weight: bold;
            color: #ffb84d;
            margin-right: 38px;
            min-width: 68px;
            text-align: center;
        }
        .dungeon-card-content {
            flex: 1;
        }
        .dungeon-card-title {
            font-size: 1.7rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 12px;
        }
        .dungeon-card-desc {
            color: #d4c8b8;
            font-size: 1.15rem;
            margin-bottom: 18px;
            line-height: 1.7;
        }
        .dungeon-card-actions {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-top: 12px;
        }
        .btn-enter-dungeon {
            background: #8b0000;
            color: #fff;
            border-radius: 10px;
            font-size: 1.18rem;
            font-weight: bold;
            padding: 12px 38px;
            border: none;
            transition: background 0.2s;
            text-decoration: none;
            box-shadow: 0 2px 8px #0006;
        }
        .btn-enter-dungeon:hover {
            background: #ff4000;
            color: #fffbe6;
        }
        @media (max-width: 1200px) {
            .dungeon-list-cards {
                max-width: 98vw;
            }
            .dungeon-card {
                min-width: 90vw;
                max-width: 98vw;
                padding: 24px 10px;
                gap: 18px;
            }
        }
        @media (max-width: 900px) {
            .dungeon-card {
                flex-direction: column;
                gap: 18px;
                min-width: unset;
                max-width: 98vw;
            }
            .dungeon-card-index {
                font-size: 1.5rem;
                margin-right: 0;
            }
            .dungeon-card-title {
                font-size: 1.2rem;
            }
            .dungeon-card-desc {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dungeon-header-bar">
        <div class="dungeon-title">副本列表</div>
        <a href="index.php" class="btn-back-lobby">
            <i class="fas fa-arrow-left" style="margin-right:7px;"></i>返回大廳
        </a>
    </div>
    <div class="dungeon-list-cards">
        <?php foreach ($dungeons as $idx => $dungeon): 
            // 第一關(索引0)永遠可選，其餘需解鎖
            $isUnlocked = ($idx === 0) || in_array($dungeon['id'], $unlocked_dungeon_ids);
        ?>
            <div class="dungeon-card-row">
                <div class="dungeon-card" style="<?= $isUnlocked ? '' : 'opacity:0.5;filter:blur(2px) grayscale(0.7);pointer-events:none;user-select:none;' ?>">
                    <div class="dungeon-card-index"><?= $idx + 1 ?></div>
                    <div class="dungeon-card-content">
                        <div class="dungeon-card-title"><?= htmlspecialchars($dungeon['name']) ?></div>
                        <div class="dungeon-card-desc"><?= nl2br(htmlspecialchars($dungeon['description'])) ?></div>
                        <?php if ($isUnlocked): ?>
                        <div class="dungeon-card-actions">
                            <a href="create_room.php?dungeon_id=<?= $dungeon['id'] ?>" class="btn-enter-dungeon">
                                進入副本
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="dungeon-card-actions">
                            <span style="color:#ff4000;font-weight:bold;font-size:1.1rem;">尚未解鎖</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
