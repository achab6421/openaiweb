<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

$current_level = $_SESSION['level'];

?>


<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['username']) ?> - Python 程式迷宮</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/chapter.css">
    <link rel="stylesheet" href="../../assets/css/quest.css">
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
                    <li><a href="api/logout.php">登出</a></li>
                </ul>
            </nav>
        </div>

        <!-- 主內容區 -->
        <div class="main-content">
            <div class="quest-header">
                <a href="dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i> 返回章節列表</a>
            </div>

            <div class="quest-container">
                <div class="monster-info-panel">
                    <div class="maze-container">
                        <div class="maze-intro">
                            <h2><i class="fas fa-treasure-chest me-2"></i>Python 程式迷宮</h2>
                            <p>探索充滿謎題與挑戰的程式迷宮，解開謎題、擊敗怪物、獲取寶藏！每個迷宮都有不同的難度和主題，挑戰自我，提升你的 Python 程式設計能力！</p>
                        </div>

                        <div class="row  maze-levels">
                            <div class="col-4">
                                <div class="maze-level <?php echo $current_level < 2 ? 'locked' : ''; ?>" onclick="randomMaze(1, this)">
                                    <div class="maze-pattern"></div>
                                    <?php if ($current_level < 2): ?>
                                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                                        <div class="level-required">需要等級 2</div>
                                    <?php endif; ?>
                                    <div class="level-number">1</div>
                                    <div class="level-label">基礎迷宮</div>
                                </div>
                            </div>

                            <div class="col">
                                <div class="maze-level <?php echo $current_level < 4 ? 'locked' : ''; ?>" onclick="randomMaze(2, this)">
                                    <div class="maze-pattern"></div>
                                    <?php if ($current_level < 4): ?>
                                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                                        <div class="level-required">需要等級 4</div>
                                    <?php endif; ?>
                                    <div class="level-number">2</div>
                                    <div class="level-label">函數迷宮</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="maze-level <?php echo $current_level < 6 ? 'locked' : ''; ?>" onclick="randomMaze(3, this)">
                                    <div class="maze-pattern"></div>
                                    <?php if ($current_level < 6): ?>
                                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                                        <div class="level-required">需要等級 6</div>
                                    <?php endif; ?>
                                    <div class="level-number">3</div>
                                    <div class="level-label">數據迷宮</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="maze-level <?php echo $current_level < 8 ? 'locked' : ''; ?>" onclick="randomMaze(4, this)">
                                    <div class="maze-pattern"></div>
                                    <?php if ($current_level < 8): ?>
                                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                                        <div class="level-required">需要等級 8</div>
                                    <?php endif; ?>
                                    <div class="level-number">4</div>
                                    <div class="level-label">檔案迷宮</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="maze-level locked" onclick="randomMaze(5, this)">
                                    <div class="maze-pattern"></div>
                                    <div class="level-lock"><i class="fas fa-lock"></i></div>
                                    <div class="level-required">敬請期待</div>
                                    <div class="level-number">5</div>
                                    <div class="level-label">進階迷宮</div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="quest-list-panel">
                    <div class="maze-info">
                        <h3>迷宮探索指南</h3>
                        <div class="info-item">
                            <i class="fas fa-lightbulb"></i>
                            <span>解決程式問題以前進迷宮路徑</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-dragon"></i>
                            <span>擊敗怪物獲得貴重物品和經驗值</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-key"></i>
                            <span>收集鑰匙開啟隱藏房間</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-gem"></i>
                            <span>找到寶箱獲得特殊能力增益</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-trophy"></i>
                            <span>完成迷宮獲得獨特獎勵和成就</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/quest.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js">
    </script>
    <script>
        function randomMaze(level, el) {
            // 若有 locked class，則不執行並提示
            if (el.classList.contains('locked')) {
                let msg = el.querySelector('.level-required')?.innerText || '此迷宮尚未解鎖！';
                alert(msg); // 增加 alert 提示
                return false; // 明確回傳 false
            }

            var pages = [
                'maze_level_choose.php?level=' + level,
                'maze_level_Sorting.php?level=' + level
            ];
            var idx = Math.floor(Math.random() * pages.length);
            window.location.href = pages[idx];

            return true; // 執行成功時回傳 true
        }
    </script>
</body>

</html>