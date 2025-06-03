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

// 取得用戶資訊
$user_id = $_SESSION["id"];
$sql = "SELECT username, current_level, attack_power, defense_power, experience_points FROM users WHERE id = ?";

if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $stmt->store_result();
        if($stmt->num_rows == 1) {
            $stmt->bind_result($username, $current_level, $attack_power, $defense_power, $exp_points);
            $stmt->fetch();
            
            // 更新會話變量
            $_SESSION["username"] = $username;
            $_SESSION["current_level"] = $current_level;
            $_SESSION["attack_power"] = $attack_power;
            $_SESSION["defense_power"] = $defense_power;
            $_SESSION["exp_points"] = $exp_points;
        }
    }
    $stmt->close();
}

// 獲取已完成的關卡數
$completed_chapters = 0;
$sql = "SELECT COUNT(*) FROM player_progress WHERE player_id = ? AND is_completed = 1";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $stmt->bind_result($completed_chapters);
        $stmt->fetch();
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
    <title>主選單 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('assets/images/main-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            padding-bottom: 50px;
        }
        
        .header {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #4CAF50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
        }
        
        .stats-badge {
            background-color: #2E7D32;
            color: white;
            font-size: 0.8rem;
            padding: 3px 10px;
            border-radius: 10px;
            margin-right: 10px;
            display: inline-flex;
            align-items: center;
        }
        
        .stats-badge i {
            margin-right: 5px;
        }
        
        .main-content {
            padding: 40px 0;
        }
        
        .menu-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .menu-card-header {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            position: relative;
            overflow: hidden;
        }
        
        .menu-card-body {
            padding: 25px;
            text-align: center;
        }
        
        .menu-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #2E7D32;
        }
        
        .menu-btn {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-top: 15px;
        }
        
        .menu-btn:hover {
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .progress-container {
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .progress {
            height: 20px;
            border-radius: 10px;
        }
        
        .logout-btn {
            color: #d32f2f;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            color: #b71c1c;
            text-decoration: none;
        }
        
        .badge-level {
            background-color: #ff9800;
            color: white;
            margin-left: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="user-info">
                        <div class="avatar">
                            <?php echo substr($_SESSION["username"], 0, 1); ?>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION["username"]); ?> <span class="badge badge-level">Lv.<?php echo $_SESSION["current_level"]; ?></span></h5>
                            <div class="mt-1">
                                <span class="stats-badge"><i class="fas fa-fist-raised"></i> 攻擊力: <?php echo $_SESSION["attack_power"]; ?></span>
                                <span class="stats-badge"><i class="fas fa-shield-alt"></i> 防禦力: <?php echo $_SESSION["defense_power"]; ?></span>
                                <span class="stats-badge"><i class="fas fa-star"></i> 經驗值: <?php echo isset($_SESSION["exp_points"]) ? $_SESSION["exp_points"] : 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt me-1"></i> 登出</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container main-content">
        <div class="progress-container">
            <h4><i class="fas fa-tasks me-2"></i>你的冒險進度</h4>
            <div class="progress mt-3">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($completed_chapters / 9) * 100; ?>%" aria-valuenow="<?php echo $completed_chapters; ?>" aria-valuemin="0" aria-valuemax="9">
                    <?php echo $completed_chapters; ?>/9 關卡
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="menu-card">
                    <div class="menu-card-header">
                        <h3><i class="fas fa-code me-2"></i> 關卡練習</h3>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h4>程式關卡挑戰</h4>
                        <p>跟隨教程一步步學習 Python，透過解題闖關來累積經驗值！</p>
                        <a href="chapters/index.php" class="menu-btn">進入關卡 <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="menu-card">
                    <div class="menu-card-header">
                        <h3><i class="fas fa-globe me-2"></i> 世界探索</h3>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        <h4>探索程式世界</h4>
                        <p>探索 Python 怪物村的各個區域，解鎖隱藏任務和特殊獎勵！</p>
                        <a href="world/index.php" class="menu-btn">開始探索 <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="menu-card">
                    <div class="menu-card-header">
                        <h3><i class="fas fa-dungeon me-2"></i> 迷宮尋寶</h3>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-icon">
                            <i class="fas fa-treasure-chest"></i>
                        </div>
                        <h4>程式迷宮冒險</h4>
                        <p>進入充滿謎題與挑戰的迷宮，收集珍貴的程式寶藏和技能！</p>
                        <a href="maze/index.php" class="menu-btn">進入迷宮 <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
