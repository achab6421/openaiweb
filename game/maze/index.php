<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 引入數據庫連接文件
require_once "../../config/database_game.php";

$user_id = $_SESSION["user_id"];
$current_level = $_SESSION["current_level"];

// 關閉連接
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>迷宮尋寶 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/maze-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            padding-bottom: 50px;
        }
        
        .header {
            background-color: rgba(255, 255, 255, 0.85);
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            margin-bottom: 40px;
        }
        
        .page-title {
            font-weight: bold;
            color: #D81B60;
            display: inline-flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .maze-container {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 40px;
            position: relative;
        }
        
        .maze-intro {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .maze-intro h2 {
            font-weight: bold;
            color: #D81B60;
            margin-bottom: 15px;
        }
        
        .maze-intro p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .maze-levels {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        
        .maze-level {
            width: 180px;
            height: 180px;
            background: linear-gradient(45deg, #D81B60, #F06292);
            color: white;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .maze-level:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .maze-level.locked {
            background: linear-gradient(45deg, #9E9E9E, #BDBDBD);
            cursor: not-allowed;
        }
        
        .level-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 5px;
            z-index: 2;
        }
        
        .level-label {
            font-size: 1rem;
            z-index: 2;
        }
        
        .maze-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../assets/images/maze-pattern.png');
            background-size: cover;
            opacity: 0.1;
        }
        
        .level-lock {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            z-index: 2;
        }
        
        .level-required {
            position: absolute;
            bottom: 10px;
            font-size: 0.8rem;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 3px 10px;
            border-radius: 10px;
            z-index: 2;
        }
        
        .maze-info {
            margin-top: 40px;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .maze-info h3 {
            font-weight: bold;
            color: #D81B60;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .info-item {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .info-item i {
            color: #D81B60;
            margin-right: 10px;
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-dungeon"></i> 迷宮尋寶
                </h1>
                <a href="../main.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i> 返回主選單
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="maze-container">
            <div class="maze-intro">
                <h2><i class="fas fa-treasure-chest me-2"></i>Python 程式迷宮</h2>
                <p>探索充滿謎題與挑戰的程式迷宮，解開謎題、擊敗怪物、獲取寶藏！每個迷宮都有不同的難度和主題，挑戰自我，提升你的 Python 程式設計能力！</p>
            </div>
            
            <div class="maze-levels">
                <div class="maze-level <?php echo $current_level < 2 ? 'locked' : ''; ?>" onclick="<?php if($current_level >= 2): ?>randomMaze(1)<?php endif; ?>">
                    <div class="maze-pattern"></div>
                    <?php if($current_level < 2): ?>
                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                        <div class="level-required">需要等級 2</div>
                    <?php endif; ?>
                    <div class="level-number">1</div>
                    <div class="level-label">基礎迷宮</div>
                </div>
                
                <div class="maze-level <?php echo $current_level < 4 ? 'locked' : ''; ?>" onclick="<?php if($current_level >= 4): ?>randomMaze(2)<?php endif; ?>">
                    <div class="maze-pattern"></div>
                    <?php if($current_level < 4): ?>
                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                        <div class="level-required">需要等級 4</div>
                    <?php endif; ?>
                    <div class="level-number">2</div>
                    <div class="level-label">函數迷宮</div>
                </div>
                
                <div class="maze-level <?php echo $current_level < 6 ? 'locked' : ''; ?>" onclick="<?php if($current_level >= 6): ?>randomMaze(3)<?php endif; ?>">
                    <div class="maze-pattern"></div>
                    <?php if($current_level < 6): ?>
                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                        <div class="level-required">需要等級 6</div>
                    <?php endif; ?>
                    <div class="level-number">3</div>
                    <div class="level-label">數據迷宮</div>
                </div>
                
                <div class="maze-level <?php echo $current_level < 8 ? 'locked' : ''; ?>" onclick="<?php if($current_level >= 8): ?>randomMaze(4)<?php endif; ?>">
                    <div class="maze-pattern"></div>
                    <?php if($current_level < 8): ?>
                        <div class="level-lock"><i class="fas fa-lock"></i></div>
                        <div class="level-required">需要等級 8</div>
                    <?php endif; ?>
                    <div class="level-number">4</div>
                    <div class="level-label">檔案迷宮</div>
                </div>
                
                <div class="maze-level locked">
                    <div class="maze-pattern"></div>
                    <div class="level-lock"><i class="fas fa-lock"></i></div>
                    <div class="level-required">敬請期待</div>
                    <div class="level-number">5</div>
                    <div class="level-label">進階迷宮</div>
                </div>
            </div>
            
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function randomMaze(level) {
            // 0: choose, 1: sorting
            var pages = [
                'maze_level_choose.php?level=' + level,
                'maze_level_Sorting.php?level=' + level
            ];
            var idx = Math.floor(Math.random() * pages.length);
            window.location.href = pages[idx];
        }
    </script>
</body>
</html>
