<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    
    <link rel="stylesheet" href="../../assets/css/dungeon_list.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="main-content" style="position:relative;">
   
    <div class="dungeons-grid" id="dungeonsGrid" style="position:relative;">
        <svg class="dungeon-path-svg" id="dungeonPathSvg"></svg>
        <?php
        // 1 2
        // 3 4
        //   5
        $dungeonsCount = count($dungeons);
        $row1 = array_slice($dungeons, 0, 2); // 1,2
        $row2 = array_slice($dungeons, 2, 2); // 3,4
        $row3 = array_slice($dungeons, 4, 1); // 5

        if (!function_exists('is_dungeon_unlocked')) {
            function is_dungeon_unlocked($idx, $dungeon, $unlocked_dungeon_ids) {
                if ($idx === 0) return true;
                return in_array($dungeon['id'], $unlocked_dungeon_ids);
            }
        }

        // 第一排
        echo '<div class="dungeon-row" data-row="0">';
        foreach ($row1 as $idx => $dungeon) {
            include 'dungeon_card.php';
        }
        echo '</div>';

        // 第二排
        echo '<div class="dungeon-row" data-row="1">';
        foreach ($row2 as $idx => $dungeon) {
            $idx += 2; // 調整索引
            include 'dungeon_card.php';
        }
        echo '</div>';

        // 第三排（置中）
        if (isset($row3[0])) {
            $dungeon = $row3[0];
            echo '<div class="dungeon-row" data-row="2">';
            $idx = 4;
            include 'dungeon_card.php';
            echo '</div>';
        }
        ?>
    </div>
</div>

</body>
</html>
