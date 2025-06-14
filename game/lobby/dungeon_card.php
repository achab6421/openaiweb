<?php
$isUnlocked = is_dungeon_unlocked($idx, $dungeon, $unlocked_dungeon_ids);
$isCompleted = false;
$totalLevels = intval($dungeon['levels']);
$completedLevels = 0;
$progressPercentage = $totalLevels > 0 ? ($completedLevels / $totalLevels) * 100 : 0;
$cardClass = $isUnlocked ? '' : 'locked';
$row = floor($idx / 2);
$col = $idx % 2;
$cardId = "dungeon-card-{$row}-{$col}";
if ($idx === 4) $cardId = "dungeon-card-2-0";

echo '<div class="dungeon-card ' . $cardClass . '" id="' . $cardId . '">';
echo '<div class="dungeon-number">' . $dungeon['id'] . '</div>';
echo '<div class="dungeon-name">' . htmlspecialchars($dungeon['name']) . '</div>';
echo '<div class="dungeon-levels">關卡數：' . $totalLevels . '</div>';
echo '<div class="dungeon-difficulty">難度: ';
$diff = 1;
if ($dungeon['difficulty'] === '普通') $diff = 2;
if ($dungeon['difficulty'] === '困難') $diff = 3;
if ($dungeon['difficulty'] === '地獄') $diff = 5;
for ($i = 0; $i < $diff; $i++) echo '<span class="star">★</span>';
echo '</div>';
if (!empty($dungeon['theme_summary'])) {
    echo '<div class="dungeon-theme">主題：' . htmlspecialchars($dungeon['theme_summary']) . '</div>';
}
if (!empty($dungeon['description'])) {
    echo '<div class="dungeon-desc">' . nl2br(htmlspecialchars($dungeon['description'])) . '</div>';
}
echo '<div class="progress-bar-container">';
echo '<div class="progress-bar" style="width: ' . $progressPercentage . '%;"></div>';
echo '</div>';
echo '<div class="progress-text">' . $completedLevels . ' / ' . $totalLevels . ' 關卡完成</div>';
echo '<div class="dungeon-status">';
if ($isCompleted) {
    echo '<i class="fas fa-check-circle dungeon-status-icon"></i> 已完成!';
} else if ($isUnlocked) {
    echo '<i class="fas fa-play-circle dungeon-status-icon"></i> 可挑戰';
} else {
    echo '<i class="fas fa-lock dungeon-status-icon"></i> 未解鎖';
}
echo '</div>';
if ($isUnlocked) {
    echo '<a href="create_room.php?dungeon_id=' . $dungeon['id'] . '" class="btn btn-enter">建立組隊房間</a>';
} else {
    echo '<button class="locked-button" disabled>需要解鎖</button>';
}
echo '</div>';
