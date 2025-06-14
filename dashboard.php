<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] === false) {
    header('Location: login.php');
    exit;
}

// 包含資料庫連接檔案
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 獲取經驗值信息
    $currentExp = 0;
    $expToNextLevel = 0;
    $expPercentage = 0;
    
    // 檢查 experience 列是否存在
    $checkColumnQuery = "SHOW COLUMNS FROM players LIKE 'experience'";
    $checkColumnStmt = $db->query($checkColumnQuery);
    if ($checkColumnStmt && $checkColumnStmt->rowCount() > 0) {
        // 獲取當前經驗值
        $expQuery = "SELECT experience FROM players WHERE player_id = ?";
        $expStmt = $db->prepare($expQuery);
        $expStmt->execute([$_SESSION['user_id']]);
        $expData = $expStmt->fetch(PDO::FETCH_ASSOC);
        $currentExp = isset($expData['experience']) ? intval($expData['experience']) : 0;
        
        // 檢查 player_level_experience 表是否存在
        $checkTableQuery = "SHOW TABLES LIKE 'player_level_experience'";
        $checkTableStmt = $db->query($checkTableQuery);
        if ($checkTableStmt && $checkTableStmt->rowCount() > 0) {
            // 獲取升級所需經驗值
            $nextLevelQuery = "SELECT required_exp FROM player_level_experience WHERE level = ? + 1";
            $nextLevelStmt = $db->prepare($nextLevelQuery);
            $nextLevelStmt->execute([$_SESSION['level']]);
            $nextLevelData = $nextLevelStmt->fetch(PDO::FETCH_ASSOC);
            $expToNextLevel = isset($nextLevelData['required_exp']) ? intval($nextLevelData['required_exp']) : (($_SESSION['level'] + 1) * 100);
        } else {
            // 表不存在，使用默認公式
            $expToNextLevel = ($_SESSION['level'] + 1) * 100;
        }
        
        // 計算進度百分比
        $expPercentage = $expToNextLevel > 0 ? min(100, ($currentExp / $expToNextLevel) * 100) : 0;
    }
    
    // 獲取玩家的章節資料
    $chapters_query = "SELECT c.*, pcr.is_unlocked, pcr.is_completed 
                      FROM chapters c 
                      LEFT JOIN player_chapter_records pcr ON c.chapter_id = pcr.chapter_id AND pcr.player_id = ?
                      WHERE c.is_hidden = FALSE
                      ORDER BY c.chapter_id";
    $chapters_stmt = $db->prepare($chapters_query);
    $chapters_stmt->bindParam(1, $_SESSION['user_id']);
    $chapters_stmt->execute();

    // 獲取玩家關卡記錄統計
    $level_stats_query = "SELECT c.chapter_id, COUNT(DISTINCT l.level_id) AS total_levels,
                         COUNT(DISTINCT CASE WHEN plr.success_count > 0 THEN l.level_id ELSE NULL END) AS completed_levels
                         FROM chapters c
                         JOIN levels l ON c.chapter_id = l.chapter_id
                         LEFT JOIN player_level_records plr ON l.level_id = plr.level_id AND plr.player_id = ?
                         GROUP BY c.chapter_id";
    $level_stats_stmt = $db->prepare($level_stats_query);
    $level_stats_stmt->bindParam(1, $_SESSION['user_id']);
    $level_stats_stmt->execute();

    // 將關卡統計資料存入陣列
    $level_stats = array();
    while ($row = $level_stats_stmt->fetch(PDO::FETCH_ASSOC)) {
        $level_stats[$row['chapter_id']] = array(
            'total' => $row['total_levels'],
            'completed' => $row['completed_levels']
        );
    }

    // 獲取所有章節
    $chapters = array();
    while ($chapter = $chapters_stmt->fetch(PDO::FETCH_ASSOC)) {
        $chapters[] = $chapter;
    }
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>儀表板 - Python 怪物村</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- 添加經驗條樣式 -->
    <style>
    .exp-progress {
        margin-top: 10px;
        width: 100%;
    }
    
    .exp-bar-container {
        height: 5px;
        background-color: rgba(0, 0, 0, 0.3);
        border-radius: 3px;
        overflow: hidden;
        margin: 4px 0;
    }
    
    .exp-bar {
        height: 100%;
        background: linear-gradient(to right, #4caf50, #8bc34a);
        width: 0%;
        transition: width 0.8s ease;
    }
    
    .exp-info {
        display: flex;
        justify-content: center;
        font-size: 12px;
    }
    
    .exp-values {
        color: #ccc;
        font-size: 11px;
    }
    </style>
</head>
<body>
    <div class="main-container">
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
                
                <!-- 添加經驗值進度條 -->
                <div class="exp-progress">
                    <div class="exp-bar-container">
                        <div class="exp-bar" style="width: <?php echo $expPercentage; ?>%"></div>
                    </div>
                    <div class="exp-info">
                        <span class="exp-values"><?php echo $currentExp; ?>/<?php echo $expToNextLevel; ?></span>
                    </div>
                </div>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="dashboard.php" class="active">主頁</a></li>
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
            <header class="dashboard-header">
                <h1>歡迎回到 Python 怪物村，<?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                <p>選擇一個章節開始你的狩獵旅程！</p>
            </header>

            <div class="chapters-grid">
                <!-- SVG路徑連接 -->
                <div class="chapter-path">
                    <svg viewBox="0 0 1000 800" xmlns="http://www.w3.org/2000/svg">
                        <!-- 路徑會在JS中動態生成 -->
                    </svg>
                </div>
                
                <?php
                $chaptersCount = count($chapters);
                $rowCount = ceil($chaptersCount / 2); // 每行最多2個章節
                
                for ($row = 0; $row < $rowCount; $row++) {
                    echo '<div class="chapter-row">';
                    
                    for ($col = 0; $col < 2; $col++) {
                        $index = $row * 2 + $col;
                        if ($index < $chaptersCount) {
                            $chapter = $chapters[$index];
                            $chapterId = $chapter['chapter_id'];
                            $isUnlocked = $chapter['is_unlocked'] ? 'unlocked' : 'locked';
                            $isCompleted = $chapter['is_completed'] ? 'completed' : 'in-progress';
                            
                            // 計算完成進度
                            $totalLevels = isset($level_stats[$chapterId]) ? $level_stats[$chapterId]['total'] : 0;
                            $completedLevels = isset($level_stats[$chapterId]) ? $level_stats[$chapterId]['completed'] : 0;
                            $progressPercentage = $totalLevels > 0 ? ($completedLevels / $totalLevels) * 100 : 0;
                            
                            // 章節狀態圖標
                            $statusIcon = $chapter['is_completed'] ? 
                                '<i class="fas fa-check-circle chapter-status-icon"></i>' : 
                                ($chapter['is_unlocked'] ? 
                                    '<i class="fas fa-play-circle chapter-status-icon"></i>' : 
                                    '<i class="fas fa-lock chapter-status-icon"></i>');
                            
                            // 章節圖片路徑
                            $imagePath = "assets/images/chapters/chapter-{$chapterId}.jpg";
                            // 如果實際圖片不存在，使用預設圖片
                            if (!file_exists($imagePath)) {
                                $imagePath = "assets/images/chapters/default-chapter.jpg";
                            }
                            
                            echo '<div class="chapter-card ' . $isUnlocked . '">';
                            
                            // 為鎖定章節添加鎖定圖標
                            if (!$chapter['is_unlocked']) {
                                echo '<i class="fas fa-lock locked-icon"></i>';
                            }
                            
                            echo '<div class="chapter-number">' . $chapterId . '</div>';
                            echo '<div class="chapter-content">';
                            echo '<h2>' . htmlspecialchars($chapter['chapter_name']) . '</h2>';
                            echo '<div class="chapter-image" style="background-image: url(\'' . $imagePath . '\');">';
                            echo '<div class="level-count">關卡數: ' . $chapter['level_count'] . '</div>';
                            echo '<div class="chapter-difficulty">難度: ';
                            for ($i = 0; $i < $chapter['difficulty']; $i++) {
                                echo '<span class="star">★</span>';
                            }
                            echo '</div></div>';
                            echo '<p class="chapter-summary">' . htmlspecialchars($chapter['summary']) . '</p>';
                            
                            // 進度條
                            if ($chapter['is_unlocked']) {
                                echo '<div class="progress-bar-container">';
                                echo '<div class="progress-bar" style="width: ' . $progressPercentage . '%;"></div>';
                                echo '</div>';
                                echo '<div class="progress-text">' . $completedLevels . ' / ' . $totalLevels . ' 關卡完成</div>';
                            }
                            
                            echo '<div class="chapter-status ' . $isCompleted . '">' . $statusIcon . ' ';
                            echo $chapter['is_completed'] ? '已完成!' : ($chapter['is_unlocked'] ? '可挑戰' : '未解鎖');
                            echo '</div>';
                            
                            
                            if ($chapter['is_unlocked']) {
                                echo '<a href="chapter.php?id=' . $chapterId . '" class="chapter-button">進入挑戰</a>';
                            } else {
                                echo '<button class="chapter-button locked-button" disabled>需要解鎖</button>';
                            }
                            echo '</div>';
                            
                            echo '</div>';
                        }
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- 添加經驗條動畫腳本 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 經驗值進度條動畫
        const expBar = document.querySelector('.exp-bar');
        if (expBar) {
            const targetWidth = expBar.style.width;
            expBar.style.width = '0%';
            
            setTimeout(() => {
                expBar.style.width = targetWidth;
            }, 300);
        }
    });
    </script>
</body>
</html>

<?php
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">資料庫連線錯誤：' . $e->getMessage() . '</div>';
}
?>
