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

$user_id = $_SESSION["id"];
$username = $_SESSION["username"];
$current_level = $_SESSION["current_level"];

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

// 獲取用戶已解鎖的隱藏關卡
$unlocked_stages = array();
$sql = "SELECT h.id, h.stage_name, h.unlock_effect, p.is_unlocked, p.effect_applied 
        FROM hidden_stages h 
        LEFT JOIN player_hidden_stages p ON h.id = p.hidden_stage_id AND p.player_id = ?
        WHERE h.is_active = 1";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $unlocked_stages[] = $row;
        }
    }
    $stmt->close();
}

// 獲取用戶尋寶次數
$treasure_hunts = 0;
$sql = "SELECT treasure_hunts FROM users WHERE id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()) {
            $treasure_hunts = $row["treasure_hunts"] ?? 0;
        }
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
    <title>世界探索 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/world-map-bg.jpg') no-repeat center center fixed;
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
            color: #4a6fa5;
            display: inline-flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .map-container {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .world-map {
            background: url('../assets/images/map-overlay.png') no-repeat center center;
            background-size: contain;
            height: 500px;
            position: relative;
            margin-bottom: 20px;
        }
        
        .location {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            cursor: not-allowed;
            transition: all 0.3s ease;
            border: 3px solid #ccc;
        }
        
        .location.unlocked {
            background-color: rgba(76, 175, 80, 0.1);
            border-color: #4CAF50;
            cursor: pointer;
            animation: pulse 2s infinite;
        }
        
        .location:hover {
            transform: translateY(-5px);
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.5);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(76, 175, 80, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
            }
        }
        
        .location-icon {
            font-size: 1.8rem;
            color: #4a6fa5;
        }
        
        .location.unlocked .location-icon {
            color: #4CAF50;
        }
        
        .location-1 {
            top: 30%;
            left: 20%;
        }
        
        .location-2 {
            top: 60%;
            left: 40%;
        }
        
        .location-3 {
            top: 20%;
            left: 60%;
        }
        
        .location-4 {
            top: 70%;
            left: 70%;
        }
        
        .world-info {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .stat-icon {
            font-size: 1.8rem;
            color: #4a6fa5;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4a6fa5;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
        }
        
        .hidden-treasures {
            margin-top: 25px;
        }
        
        .treasure-item {
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .treasure-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4CAF50;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            color: white;
        }
        
        .treasure-icon.treasure-locked {
            background-color: #9e9e9e;
        }
        
        .treasure-name {
            font-weight: bold;
            color: #444;
        }
        
        .treasure-effect {
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-map-marked-alt"></i> 世界探索
                </h1>
                <a href="../main.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i> 返回主選單
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="map-container">
                    <h2 class="mb-4"><i class="fas fa-globe me-2"></i>Python 爬蟲探險地圖</h2>
                    <p class="mb-4">完成章節關卡來解鎖不同的地區，練習爬蟲技術尋找隱藏的寶藏！探索這個充滿程式秘密的世界！</p>
                    
                    <div class="world-map">
                    
                        <!-- 地點1：數據森林 -->
                        <div class="location location-1 unlocked" onclick="location.href='treasure_hunt.php?location=1'">
                            <div class="location-icon">
                                <i class="fas fa-tree"></i>
                            </div>
                        </div>
                        
                        <!-- 地點2：字典城市 -->
                        <div class="location location-2 <?php echo $completed_chapters >= 3 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             title="<?php echo $completed_chapters >= 3 ? '字典城市 - 已解鎖' : '解鎖條件：完成 3 個章節'; ?>"
                             <?php if($completed_chapters >= 3): ?>onclick="location.href='treasure_hunt.php?location=2'"<?php endif; ?>>
                            <div class="location-icon">
                                <i class="fas fa-city"></i>
                            </div>
                        </div>
                        
                        <!-- 地點3：模組山脈 -->
                        <div class="location location-3 <?php echo $completed_chapters >= 7 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             <?php if($completed_chapters >= 7): ?>onclick="location.href='treasure_hunt.php?location=3'"<?php endif; ?>>
                            <div class="location-icon">
                                <i class="fas fa-mountain"></i>
                            </div>
                        </div>
                        
                        <!-- 地點4：異常洞窟 -->
                        <div class="location location-4 <?php echo $completed_chapters >= 9 ? 'unlocked' : ''; ?>"
                             data-bs-toggle="tooltip" data-bs-placement="top" 
                             title="<?php echo $completed_chapters >= 9 ? '異常洞窟 - 已解鎖' : '解鎖條件：完成 9 個章節'; ?>"
                             <?php if($completed_chapters >= 9): ?>onclick="location.href='treasure_hunt.php?location=4'"<?php endif; ?>>
                            <div class="location-icon">
                                <i class="fas fa-dungeon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="world-info">
                    <h3 class="mb-4"><i class="fas fa-map-marked-alt me-2"></i>世界狀態</h3>
                    
                    <div class="world-stats">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $completed_chapters; ?>/9</div>
                                    <div class="stat-label">已完成章節</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-map"></i>
                                    </div>
                                    <div class="stat-value"><?php echo ($completed_chapters >= 3) + ($completed_chapters >= 5) + ($completed_chapters >= 7) + ($completed_chapters >= 9); ?>/4</div>
                                    <div class="stat-label">已解鎖區域</div>
                                </div>
                            </div>
                            <div class="col-6 mt-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-gem"></i>
                                    </div>
                                    <div class="stat-value"><?php echo count($unlocked_stages); ?></div>
                                    <div class="stat-label">發現寶藏</div>
                                </div>
                            </div>
                            <div class="col-6 mt-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="stat-value"><?php echo $treasure_hunts; ?></div>
                                    <div class="stat-label">尋寶次數</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hidden-treasures mt-4">
                        <h4 class="mb-3"><i class="fas fa-gem me-2"></i>隱藏寶藏</h4>
                        
                        <?php if(count($unlocked_stages) > 0): ?>
                            <?php foreach($unlocked_stages as $stage): ?>
                                <div class="treasure-item">
                                    <div class="treasure-icon <?php echo !isset($stage['is_unlocked']) || !$stage['is_unlocked'] ? 'treasure-locked' : ''; ?>">
                                        <i class="<?php echo !isset($stage['is_unlocked']) || !$stage['is_unlocked'] ? 'fas fa-lock' : 'fas fa-trophy'; ?>"></i>
                                    </div>
                                    <div class="treasure-info">
                                        <div class="treasure-name"><?php echo htmlspecialchars($stage['stage_name']); ?></div>
                                        <div class="treasure-effect">
                                            <?php
                                                if(isset($stage['is_unlocked']) && $stage['is_unlocked']) {
                                                    echo '獎勵：' . htmlspecialchars($stage['unlock_effect']);
                                                    if($stage['effect_applied']) {
                                                        echo ' <span class="badge bg-success">已獲得</span>';
                                                    }
                                                } else {
                                                    echo '繼續探索世界以解鎖這個寶藏';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle me-2"></i>尚未發現任何隱藏寶藏！繼續完成關卡並探索世界。
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="hidden_quest.php" class="btn btn-success w-100">
                                <i class="fas fa-search me-2"></i>尋找隱藏任務
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 初始化工具提示
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>