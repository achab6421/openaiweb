<?php
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// 檢查URL參數
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$level_id = intval($_GET['id']);

// 包含資料庫連接檔案
include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// 獲取關卡資訊及怪物資訊
$level_query = "SELECT l.*, c.chapter_id, c.chapter_name, m.hp, m.difficulty as monster_difficulty, 
                m.attack_power, m.exp_reward, m.teaching_point, m.is_boss,
                (SELECT COUNT(*) FROM player_level_records plr WHERE plr.level_id = l.level_id AND plr.player_id = ? AND plr.success_count > 0) as completed
                FROM levels l 
                JOIN chapters c ON l.chapter_id = c.chapter_id
                JOIN monsters m ON l.monster_id = m.monster_id
                WHERE l.level_id = ?";
$level_stmt = $db->prepare($level_query);
$level_stmt->bindParam(1, $_SESSION['user_id']);
$level_stmt->bindParam(2, $level_id);
$level_stmt->execute();

if ($level_stmt->rowCount() == 0) {
    header('Location: dashboard.php');
    exit;
}

$level = $level_stmt->fetch(PDO::FETCH_ASSOC);

// 檢查章節是否解鎖
$chapter_query = "SELECT is_unlocked FROM player_chapter_records WHERE player_id = ? AND chapter_id = ?";
$chapter_stmt = $db->prepare($chapter_query);
$chapter_stmt->bindParam(1, $_SESSION['user_id']);
$chapter_stmt->bindParam(2, $level['chapter_id']);
$chapter_stmt->execute();

if ($chapter_stmt->rowCount() > 0) {
    $chapter = $chapter_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$chapter['is_unlocked']) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
}

// 獲取玩家關卡記錄
$record_query = "SELECT * FROM player_level_records WHERE player_id = ? AND level_id = ?";
$record_stmt = $db->prepare($record_query);
$record_stmt->bindParam(1, $_SESSION['user_id']);
$record_stmt->bindParam(2, $level_id);
$record_stmt->execute();

$attempt_count = 0;
$success_count = 0;

if ($record_stmt->rowCount() > 0) {
    $record = $record_stmt->fetch(PDO::FETCH_ASSOC);
    $attempt_count = $record['attempt_count'];
    $success_count = $record['success_count'];
} else {
    // 創建新的記錄
    $insert_query = "INSERT INTO player_level_records (player_id, level_id, attempt_count, success_count) VALUES (?, ?, 0, 0)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->bindParam(1, $_SESSION['user_id']);
    $insert_stmt->bindParam(2, $level_id);
    $insert_stmt->execute();
}

// 獲取關卡教學內容
$tutorial_query = "SELECT * FROM level_tutorials WHERE level_id = ? ORDER BY order_index";
$tutorial_stmt = $db->prepare($tutorial_query);
$tutorial_stmt->bindParam(1, $level_id);
$tutorial_stmt->execute();

// 根據關卡和怪物信息計算玩家和怪物的戰斗屬性
$player_max_hp = $_SESSION['base_hp'];
$player_attack = $_SESSION['attack_power'];
$monster_max_hp = $level['hp'];
$monster_attack = $level['attack_power'];
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>關卡 <?= $level_id ?> - Python 怪物村</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/battle.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.1/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.1/theme/dracula.min.css">
</head>
<body>
    <div class="battle-container">
        <!-- 上方狀態區 -->
        <div class="battle-header">
            <div class="header-info">
                <a href="chapter.php?id=<?= $level['chapter_id'] ?>" class="back-button"><i class="fas fa-arrow-left"></i> 返回章節</a>
                <h1><?= htmlspecialchars($level['chapter_name']) ?> - 關卡 <?= $level_id ?></h1>
            </div>
            <div class="player-stats">
                <div class="player-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="stats-row">
                    <div class="stat-item">LV. <?= $_SESSION['level'] ?></div>
                    <div class="stat-item">ATK: <?= $player_attack ?></div>
                    <div class="stat-item">HP: <span id="player-hp"><?= $player_max_hp ?></span>/<?= $player_max_hp ?></div>
                </div>
            </div>
        </div>

        <!-- 主戰鬥區 -->
        <div class="battle-content">
            <!-- 左側程式編輯區 -->
            <div class="code-section">
                <div class="problem-container " style="height: 200px;">
                    <h2>挑戰題目</h2>
                    <div id="problem-description" class="problem-description">
                        <div class="loading">正在載入題目...</div>
                    </div>
                </div>

                <div class="code-editor-container" style="height: 100px;">
                    <h3>Python 程式碼</h3>
                    <textarea id="code-editor"></textarea>
                    <div class="editor-buttons">
                        <button id="test-code" class="test-button">測試代碼</button>
                        <button id="submit-code" class="submit-button">提交答案</button>
                    </div>
                </div>

                <div class="output-container" style="height: 220px; margin-bottom: 100px;">
                    <h3>執行結果</h3>
                    <div id="output-display" class="output-display"></div>
                </div>
            </div>

            <!-- 右側戰鬥畫面區 -->
            <div class="battle-section">
                <div class="battle-scene" style="height: 10px;">
                    <!-- 怪物區 -->
                    <div class="monsters-area" style="height: 30%;">
                        <div class="monster-unit" id="monster1">
                            <?php
                                $monsterImage = "assets/images/monsters/monster-{$level['monster_id']}.png";
                                if (!file_exists($monsterImage)) {
                                    $monsterImage = $level['is_boss'] 
                                        ? "assets/images/monsters/default-boss.png"
                                        : "assets/images/monsters/default-monster.png";
                                }
                            ?>
                            <div class="monster-name">草原林 <?= $level['is_boss'] ? 'BOSS' : 'A' ?></div>
                            <div class="monster-sprite">
                                <img src="<?= $monsterImage ?>" alt="怪物" class="monster-image">
                                <div class="monster-effects"></div>
                            </div>
                            <div class="monster-hp">
                                <div class="hp-text">HP <span class="current-hp"><?= $monster_max_hp ?></span>/<?= $monster_max_hp ?></div>
                                <div class="hp-bar">
                                    <div class="hp-fill" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($level['wave_count'] > 1): ?>
                        <div class="monster-unit" id="monster2">
                            <div class="monster-name">草原林 B</div>
                            <div class="monster-sprite">
                                <img src="<?= $monsterImage ?>" alt="怪物" class="monster-image">
                                <div class="monster-effects"></div>
                            </div>
                            <div class="monster-hp">
                                <div class="hp-text">HP <span class="current-hp"><?= $monster_max_hp ?></span>/<?= $monster_max_hp ?></div>
                                <div class="hp-bar">
                                    <div class="hp-fill" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 角色區 -->
                    <div class="characters-area">
                        <div class="character-unit">
                            <div class="character-sprite">
                                <img src="assets/images/characters/character-1.png" alt="角色" class="character-image">
                            </div>
                            <div class="character-info">
                                <div class="character-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <div class="character-stats">
                                    <div class="stat">HP <span class="hp-value"><?= $player_max_hp ?></span></div>
                                    <div class="stat">LV<span class="mp-value"></span> <?= $_SESSION['level'] ?><span></div>
                                    <div class="stat">ATK<span class="tp-value"> <?= $player_attack ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="battle-message">
                    <div class="message-content" id="battle-message-content">
                        戰鬥開始！請編寫程式碼以擊敗怪物！
                    </div>
                </div>
                
                <div class="battle-tutorial" style="height: 30%;">
                    <h3 class="tutorial-title"><i class="fas fa-book"></i> 學習指南</h3>
                    <div class="tutorial-content">
                        <?php if ($tutorial_stmt->rowCount() > 0): ?>
                            <div class="tutorial-tabs">
                                <?php 
                                $tabIndex = 1;
                                $tutorial_stmt->execute(); // 重新執行以重置指針
                                while ($tutorial = $tutorial_stmt->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <button class="tutorial-tab <?= $tabIndex == 1 ? 'active' : '' ?>" data-tab="tab-<?= $tabIndex ?>">
                                        <?= htmlspecialchars($tutorial['title']) ?>
                                    </button>
                                <?php 
                                    $tabIndex++;
                                endwhile; 
                                ?>
                            </div>
                            <div class="tutorial-panels">
                                <?php 
                                $panelIndex = 1;
                                $tutorial_stmt->execute(); // 再次重新執行
                                while ($tutorial = $tutorial_stmt->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <div class="tutorial-panel <?= $panelIndex == 1 ? 'active' : '' ?>" id="tab-<?= $panelIndex ?>">
                                        <div class="tutorial-text">
                                            <?= nl2br(htmlspecialchars($tutorial['content'])) ?>
                                        </div>
                                        <?php if ($tutorial['code_example']): ?>
                                            <div class="code-example">
                                                <pre><code class="language-python"><?= htmlspecialchars($tutorial['code_example']) ?></code></pre>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php 
                                    $panelIndex++;
                                endwhile; 
                                ?>
                            </div>
                        <?php else: ?>
                            <p>本關卡沒有提供教學內容。請根據題目要求編寫程式碼。</p>
                            <p>教學重點：<?= htmlspecialchars($level['teaching_point']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 戰鬥結果彈窗 -->
    <div class="battle-modal" id="result-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="result-title">戰鬥結果</h2>
            </div>
            <div class="modal-body">
                <div id="result-message"></div>
                <div class="result-stats">
                    <div class="stat-row">
                        <span>經驗值獲得：</span>
                        <span id="exp-gain">0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="retry-button" class="battle-button">重試</button>
                <a href="chapter.php?id=<?= $level['chapter_id'] ?>" class="battle-button">返回章節</a>
            </div>
        </div>
    </div>

    <!-- JavaScript 庫 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.1/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.1/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.1/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/languages/python.min.js"></script>
    
    <script>
        // 關卡和怪物數據
        const levelData = {
            levelId: <?= $level_id ?>,
            chapterId: <?= $level['chapter_id'] ?>,
            teachingPoint: "<?= htmlspecialchars($level['teaching_point']) ?>",
            monsterHp: <?= $monster_max_hp ?>,
            monsterAttack: <?= $monster_attack ?>,
            playerHp: <?= $player_max_hp ?>,
            playerAttack: <?= $player_attack ?>,
            waveCount: <?= $level['wave_count'] ?>,
            expReward: <?= $level['exp_reward'] ?>,
            isBoss: <?= $level['is_boss'] ? 'true' : 'false' ?>,
        };
    </script>
    
    <script src="assets/js/battle.js"></script>
</body>
</html>
