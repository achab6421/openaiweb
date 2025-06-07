<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// 引入數據庫連接文件
require_once "../config/database_game.php";

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$current_level = $_SESSION["current_level"];

// 取得用戶信息
$attack_power = 10;
$defense_power = 5;
$experience_points = 0;

$sql = "SELECT attack_power, defense_power, experience_points FROM users WHERE id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()) {
            $attack_power = $row["attack_power"];
            $defense_power = $row["defense_power"];
            $experience_points = $row["experience_points"];
        }
    }
    $stmt->close();
}

// 獲取用戶已完成的章節數
$completed_chapters = 0;
$sql = "SELECT COUNT(*) as completed FROM player_progress WHERE player_id = ? AND is_completed = 1";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $completed_chapters = $row["completed"];
    }
    $stmt->close();
}

// 關閉連接
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Python 怪物村：AI 助教教你寫程式打怪獸！</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('assets/images/main-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            overflow-x: hidden;
        }
        
        .overlay {
            background-color: rgba(0, 0, 0, 0.6);
            height: 100vh;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }
        
        .header {
            padding: 20px 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            margin-bottom: 40px;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #fff;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            color: #ffc107;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c5ce7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
            border: 2px solid #fff;
        }
        
        .user-name {
            font-weight: bold;
        }
        
        .user-level {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .menu-container {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;
        }
        
        .menu-title {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .menu-title h2 {
            font-weight: bold;
            color: #ffc107;
            margin-bottom: 10px;
        }
        
        .menu-title p {
            opacity: 0.8;
            font-size: 1.1rem;
        }
        
        .menu-item {
            background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .menu-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            background: linear-gradient(145deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
        }
        
        .menu-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ffc107;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover .menu-icon {
            transform: scale(1.2);
        }
        
        .menu-item h3 {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .menu-item p {
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        .stats-container {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .stat-box {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-icon {
            font-size: 2rem;
            color: #ffc107;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            opacity: 0.8;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .progress-bar {
            background-color: #ffc107;
        }
        
        .footer {
            background-color: rgba(0, 0, 0, 0.5);
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    
    <header class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="logo">
                        <i class="fas fa-dragon"></i>
                        Python 怪物村
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="user-info">
                        <div class="avatar">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                        <div>
                            <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                            <div class="user-level">Level <?php echo $current_level; ?></div>
                        </div>
                        <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">
                            <i class="fas fa-sign-out-alt me-1"></i> 登出
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="menu-container">
            <div class="menu-title">
                <h2><i class="fas fa-gamepad me-2"></i>冒險選單</h2>
                <p>探索、學習、挑戰，成為最強的Python冒險家！</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="menu-item" onclick="location.href='chapters/index.php'">
                        <div class="menu-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>關卡練習</h3>
                        <p>學習Python語法，挑戰關卡怪物，增強你的程式能力！</p>
                        <button class="btn btn-warning">
                            <i class="fas fa-play me-1"></i> 開始冒險
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="menu-item" onclick="location.href='world/index.php'">
                        <div class="menu-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h3>世界探索</h3>
                        <p>探索充滿謎題的世界，運用爬蟲技術發現隱藏寶藏！</p>
                        <button class="btn btn-warning">
                            <i class="fas fa-compass me-1"></i> 開始探索
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="menu-item" onclick="location.href='maze/index.php'">
                        <div class="menu-icon">
                            <i class="fas fa-dungeon"></i>
                        </div>
                        <h3>迷宮尋寶</h3>
                        <p>解開程式謎題，在迷宮中戰勝怪物，尋找稀有寶物！</p>
                        <button class="btn btn-warning">
                            <i class="fas fa-key me-1"></i> 進入迷宮
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="stats-container">
                    <h3 class="mb-4"><i class="fas fa-chart-line me-2"></i>冒險進度</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-value"><?php echo $completed_chapters; ?>/9</div>
                                <div class="stat-label">已完成章節</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-icon">
                                    <i class="fas fa-fist-raised"></i>
                                </div>
                                <div class="stat-value"><?php echo $attack_power; ?></div>
                                <div class="stat-label">攻擊力</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="stat-value"><?php echo $defense_power; ?></div>
                                <div class="stat-label">防禦力</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div class="stat-value"><?php echo $experience_points; ?></div>
                                <div class="stat-label">經驗值</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>等級進度</h5>
                        <?php
                            $next_level = $current_level + 1;
                            $exp_needed = $next_level * 100;
                            $exp_progress = min(100, ($experience_points / $exp_needed) * 100);
                        ?>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $exp_progress; ?>%" aria-valuenow="<?php echo $experience_points; ?>" aria-valuemin="0" aria-valuemax="<?php echo $exp_needed; ?>"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small>等級 <?php echo $current_level; ?></small>
                            <small><?php echo $experience_points; ?>/<?php echo $exp_needed; ?> EXP</small>
                            <small>等級 <?php echo $next_level; ?></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="stats-container">
                    <h3 class="mb-4"><i class="fas fa-newspaper me-2"></i>最新消息</h3>
                    
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <strong>新功能上線！</strong>
                                <p class="mb-0">爬蟲探索功能已加入世界探索中，練習你的Python爬蟲技能！</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div>
                                <strong>完成章節獲得探索機會</strong>
                                <p class="mb-0">每完成一個章節可以解鎖新的探索區域，尋找隱藏寶藏！</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <div class="d-flex">
                            <div class="me-2">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div>
                                <strong>挑戰活動進行中</strong>
                                <p class="mb-0">利用Python爬蟲技能找出隱藏的關鍵字，獲得特殊獎勵！</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <p>Python 怪物村：AI 助教教你寫程式打怪獸！ &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
