<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// 檢查URL參數
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "錯誤：URL參數錯誤 - 缺少ID或格式不正確";
    exit;
}

$chapter_id = intval($_GET['id']);

// 包含資料庫連接檔案
include_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 獲取章節資訊
    $chapter_query = "SELECT c.*, pcr.is_unlocked, pcr.is_completed 
                     FROM chapters c 
                     LEFT JOIN player_chapter_records pcr ON c.chapter_id = pcr.chapter_id AND pcr.player_id = ?
                     WHERE c.chapter_id = ?";
    $chapter_stmt = $db->prepare($chapter_query);
    $chapter_stmt->bindParam(1, $_SESSION['user_id']);
    $chapter_stmt->bindParam(2, $chapter_id);
    $chapter_stmt->execute();
    
    if ($chapter_stmt->rowCount() == 0) {
        echo "錯誤：找不到指定章節 (ID: $chapter_id)";
        echo "<br>SQL: " . $chapter_query;
        echo "<br>用戶ID: " . $_SESSION['user_id'];
        echo "<br><a href='dashboard.php'>返回主頁</a>";
        exit;
    }
    
    $chapter = $chapter_stmt->fetch(PDO::FETCH_ASSOC);
    
    // 檢查章節是否已解鎖
    if (!$chapter['is_unlocked']) {
        echo "錯誤：此章節尚未解鎖";
        echo "<br><a href='dashboard.php'>返回主頁</a>";
        exit;
    }
    
    // 獲取章節中的關卡及其關聯的怪物
    $levels_query = "SELECT l.*, m.monster_id, m.hp, m.difficulty as monster_difficulty, m.attack_power, 
                    m.exp_reward, m.teaching_point, m.is_boss,
                    (SELECT COUNT(*) FROM player_level_records plr WHERE plr.level_id = l.level_id AND plr.player_id = ? AND plr.success_count > 0) as completed,
                    (SELECT attempt_count FROM player_level_records plr WHERE plr.level_id = l.level_id AND plr.player_id = ?) as attempts,
                    (SELECT success_count FROM player_level_records plr WHERE plr.level_id = l.level_id AND plr.player_id = ?) as successes
                    FROM levels l 
                    JOIN monsters m ON l.monster_id = m.monster_id
                    WHERE l.chapter_id = ?
                    ORDER BY l.level_id";
    $levels_stmt = $db->prepare($levels_query);
    $levels_stmt->bindParam(1, $_SESSION['user_id']);
    $levels_stmt->bindParam(2, $_SESSION['user_id']);
    $levels_stmt->bindParam(3, $_SESSION['user_id']);
    $levels_stmt->bindParam(4, $chapter_id);
    $levels_stmt->execute();
    
    $levelsCount = $levels_stmt->rowCount();
    if ($levelsCount == 0) {
        echo "警告：此章節沒有關卡 (ID: $chapter_id)";
        echo "<br><a href='dashboard.php'>返回主頁</a>";
        exit;
    }
    
    $levels = [];
    while ($level = $levels_stmt->fetch(PDO::FETCH_ASSOC)) {
        $levels[] = $level;
    }
    
    // 找出當前可挑戰的關卡
    $currentLevelIndex = 0;
    $allCompleted = true;
    foreach ($levels as $index => $level) {
        if ($level['completed'] == 0) {
            $currentLevelIndex = $index;
            $allCompleted = false;
            break;
        }
    }
    
    if ($allCompleted && count($levels) > 0) {
        $currentLevelIndex = count($levels) - 1;
    }
    
    $currentLevel = isset($levels[$currentLevelIndex]) ? $levels[$currentLevelIndex] : null;
    
    if (isset($_GET['level_id']) && is_numeric($_GET['level_id'])) {
        $requestedLevelId = intval($_GET['level_id']);
        foreach ($levels as $index => $level) {
            if ($level['level_id'] == $requestedLevelId) {
                $currentLevelIndex = $index;
                $currentLevel = $level;
                break;
            }
        }
    }
} catch (PDOException $e) {
    echo "資料庫錯誤：" . $e->getMessage();
    echo "<br><a href='dashboard.php'>返回主頁</a>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($chapter['chapter_name']) ?> - Python 怪物村</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/chapter.css">
    <link rel="stylesheet" href="assets/css/quest.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="main-container quest-page">
        <!-- 側邊欄 -->
        <div class="sidebar">
            <div class="player-info">
                <img src="assets/images/hunter-avatar.png" alt="獵人頭像" class="player-avatar">
                <h3><?= htmlspecialchars($_SESSION['username']) ?></h3>
                <div class="player-stats">
                    <div class="stat">
                        <span>等級</span>
                        <strong><?= $_SESSION['level'] ?></strong>
                    </div>
                    <div class="stat">
                        <span>攻擊力</span>
                        <strong><?= $_SESSION['attack_power'] ?></strong>
                    </div>
                    <div class="stat">
                        <span>血量</span>
                        <strong><?= $_SESSION['base_hp'] ?></strong>
                    </div>
                </div>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="dashboard.php">主頁</a></li>
                    <li><a href="profile.php">獵人檔案</a></li>
                    <li><a href="achievements.php">成就系統</a></li>
                    <li><a href="game/maze/index.php">秘密任務</a></li>
                    <li><a href="game/lobby/index.php">多人副本</a></li>
                    <li><a href="api/logout.php">登出</a></li>
                </ul>
            </nav>
        </div>

        <!-- 主內容區 -->
        <div class="main-content">
            <div class="quest-header">
                <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> 返回章節列表</a>
                <h1><?= htmlspecialchars($chapter['chapter_name']) ?> (ID: <?= $chapter_id ?>)</h1>
                <div class="chapter-difficulty">
                    <span class="difficulty-label">難度:</span>
                    <?php for ($i = 0; $i < $chapter['difficulty']; $i++): ?>
                        <span class="star">★</span>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="quest-container">
                <!-- 左側怪物資訊 -->
                <?php if ($currentLevel): ?>
                <div class="monster-info-panel">
                    <div class="monster-header">
                        <h2 class="<?= $currentLevel['is_boss'] ? 'boss-monster' : '' ?>">
                            <?= $currentLevel['is_boss'] ? '<i class="fas fa-crown"></i> BOSS關卡' : '普通關卡' ?>
                        </h2>
                        <div class="level-number">關卡 <?= $currentLevel['level_id'] ?></div>
                    </div>
                    
                    <div class="monster-image-container">
                        <?php
                            $monsterImage = "assets/images/monsters/monster-{$currentLevel['monster_id']}.jpg";
                            if (!file_exists($monsterImage)) {
                                $monsterImage = $currentLevel['is_boss'] 
                                    ? "assets/images/monsters/default-boss.jpg"
                                    : "assets/images/monsters/default-monster.jpg";
                            }
                        ?>
                        <img src="<?= $monsterImage ?>" alt="怪物圖片" class="monster-image">
                        <div class="monster-difficulty">
                            <span>怪物難度:</span>
                            <?php for ($i = 0; $i < $currentLevel['monster_difficulty']; $i++): ?>
                                <span class="monster-star">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="monster-stats">
                        <div class="stat-row">
                            <span class="stat-label">生命值</span>
                            <div class="stat-bar hp-bar">
                                <div class="stat-fill" style="width: 100%"></div>
                            </div>
                            <span class="stat-value"><?= $currentLevel['hp'] ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">攻擊力</span>
                            <div class="stat-bar attack-bar">
                                <div class="stat-fill" style="width: <?= min(($currentLevel['attack_power'] / 100) * 100, 100) ?>%"></div>
                            </div>
                            <span class="stat-value"><?= $currentLevel['attack_power'] ?></span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">經驗獎勵</span>
                            <div class="stat-bar exp-bar">
                                <div class="stat-fill" style="width: <?= min(($currentLevel['exp_reward'] / 200) * 100, 100) ?>%"></div>
                            </div>
                            <span class="stat-value"><?= $currentLevel['exp_reward'] ?> EXP</span>
                        </div>
                    </div>
                    
                    <div class="teaching-point-container">
                        <h3>教學重點</h3>
                        <p><?= htmlspecialchars($currentLevel['teaching_point']) ?></p>
                    </div>
                    
                    <div class="wave-info">
                        <h3>關卡波數</h3>
                        <div class="wave-indicators">
                            <?php for ($i = 0; $i < $currentLevel['wave_count']; $i++): ?>
                                <div class="wave-indicator">
                                    <i class="fas fa-fire-alt"></i>
                                    <span>第 <?= $i+1 ?> 波</span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="player-record">
                        <h3>你的戰績</h3>
                        <div class="record-stats">
                            <div>嘗試次數: <span><?= $currentLevel['attempts'] ?: 0 ?></span></div>
                            <div>成功討伐: <span><?= $currentLevel['successes'] ?: 0 ?></span></div>
                            <div>成功率: <span>
                                <?= $currentLevel['attempts'] > 0 
                                    ? round(($currentLevel['successes'] / $currentLevel['attempts']) * 100) . '%' 
                                    : '0%' ?>
                            </span></div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if ($currentLevel['completed'] > 0): ?>
                            <a href="level.php?id=<?= $currentLevel['level_id'] ?>" class="replay-button">重新挑戰</a>
                        <?php else: ?>
                            <a href="level.php?id=<?= $currentLevel['level_id'] ?>" class="start-button">開始挑戰</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="monster-info-panel">
                    <div class="no-level-selected">
                        <p>沒有找到可用關卡</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 右側關卡列表 -->
                <div class="quest-list-panel">
                    <h2>關卡列表</h2>
                    <div class="quest-list-container">
                        <?php 
                        $previousLevelCompleted = true; // 第一關沒有前置條件
                        foreach ($levels as $index => $level): 
                            $isAccessible = false;
                            
                            // 檢查前置條件
                            if ($level['prerequisite_level_id'] === null) {
                                $isAccessible = true;
                            } else {
                                // 檢查前置關卡是否已完成
                                foreach ($levels as $prevLevel) {
                                    if ($prevLevel['level_id'] == $level['prerequisite_level_id'] && $prevLevel['completed'] > 0) {
                                        $isAccessible = true;
                                        break;
                                    }
                                }
                            }
                            
                            $questClass = $level['completed'] > 0 ? 'completed' : ($isAccessible ? 'available' : 'locked');
                            $isSelected = ($index === $currentLevelIndex) ? 'selected' : '';
                        ?>
                            <div class="quest-item <?= $questClass ?> <?= $isSelected ?>" data-level-id="<?= $level['level_id'] ?>">
                                <div class="quest-item-content">
                                    <div class="quest-rank <?= $level['is_boss'] ? 'boss-rank' : '' ?>">
                                        <?= $level['is_boss'] ? '<i class="fas fa-crown"></i>' : $level['level_id'] ?>
                                    </div>
                                    <div class="quest-details">
                                        <h3><?= $level['is_boss'] ? 'BOSS挑戰' : '關卡 ' . $level['level_id'] ?></h3>
                                        <p class="quest-target">目標: <?= htmlspecialchars($level['teaching_point']) ?></p>
                                        <div class="quest-stats">
                                            <span class="quest-stat"><i class="fas fa-heart"></i> <?= $level['hp'] ?></span>
                                            <span class="quest-stat"><i class="fas fa-fist-raised"></i> <?= $level['attack_power'] ?></span>
                                            <span class="quest-stat"><i class="fas fa-star"></i> <?= $level['exp_reward'] ?> EXP</span>
                                        </div>
                                    </div>
                                    <div class="quest-status">
                                        <?php if ($level['completed'] > 0): ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php elseif ($isAccessible): ?>
                                            <i class="fas fa-exclamation-circle"></i>
                                        <?php else: ?>
                                            <i class="fas fa-lock"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($level['completed'] > 0 || $isAccessible): ?>
                                    <a href="?id=<?= $chapter_id ?>&level_id=<?= $level['level_id'] ?>" class="quest-select-button">
                                        查看詳情
                                    </a>
                                <?php else: ?>
                                    <div class="quest-select-button disabled">
                                        需完成前置關卡
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/quest.js"></script>
</body>
</html>

<!-- 添加CSS樣式 -->
<style>
    .chapter-page {
        padding: 20px 0;
    }
    
    .chapter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .chapter-navigation {
        display: flex;
        gap: 10px;
    }
    
    .nav-button {
        padding: 8px 15px;
        background-color: #4a5568;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s;
    }
    
    .nav-button:hover {
        background-color: #2d3748;
    }
    
    .chapter-info {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .level-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .level-item {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .level-item.completed {
        border-left: 5px solid #48bb78; /* 綠色邊框表示已完成 */
    }
    
    .level-item.current {
        border-left: 5px solid #4299e1; /* 藍色邊框表示當前關卡 */
    }
    
    .level-item.locked {
        border-left: 5px solid #a0aec0; /* 灰色邊框表示未解鎖 */
        opacity: 0.7;
    }
    
    .level-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .level-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    
    .level-icon {
        font-size: 24px;
        color: #4a5568;
        margin-right: 15px;
    }
    
    .level-item.completed .level-icon {
        color: #48bb78; /* 綠色圖標表示已完成 */
    }
    
    .level-item.current .level-icon {
        color: #4299e1; /* 藍色圖標表示當前關卡 */
    }
    
    .level-details {
        padding: 15px;
    }
    
    .level-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .boss-tag {
        background-color: #e53e3e;
        color: white;
        font-size: 12px;
        padding: 3px 8px;
        border-radius: 10px;
    }
    
    .boss-level {
        border: 2px solid #e53e3e;
        box-shadow: 0 0 15px rgba(229, 62, 62, 0.3);
    }
    
    .level-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 10px;
        font-size: 14px;
        color: #718096;
    }
    
    .level-desc {
        font-size: 14px;
        color: #4a5568;
    }
    
    .no-levels {
        text-align: center;
        padding: 30px;
        background-color: #f8f9fa;
        border-radius: 10px;
        font-style: italic;
        color: #718096;
    }
</style>
