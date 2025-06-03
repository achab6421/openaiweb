<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 檢查是否有提供章節 ID
if(!isset($_GET["id"]) || empty($_GET["id"])) {
    header("location: index.php");
    exit;
}

$chapter_id = intval($_GET["id"]);

// 引入數據庫連接文件
require_once "../../config/database_game.php";

$user_id = $_SESSION["id"];

// 獲取章節信息
$chapter = null;
$sql = "SELECT * FROM chapters WHERE chapter_id = ? AND is_hidden = 0";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $chapter_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($result->num_rows == 1) {
            $chapter = $result->fetch_assoc();
        } else {
            header("location: index.php");
            exit;
        }
    }
    $stmt->close();
}

// 確認章節是否已解鎖
if(!$chapter["is_unlocked"]) {
    header("location: index.php");
    exit;
}

// 獲取章節中的題目
$questions = array();
$sql = "SELECT * FROM questions WHERE chapter_id = ? ORDER BY question_id ASC";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $chapter_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
    }
    $stmt->close();
}

// 獲取章節相關的怪物
$monsters = array();
$sql = "SELECT * FROM monsters WHERE stage_id = ? ORDER BY monster_id ASC";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $chapter_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $monsters[] = $row;
        }
    }
    $stmt->close();
}

// 檢查玩家的章節進度
$progress = null;
$sql = "SELECT * FROM player_progress WHERE player_id = ? AND chapter_id = ?";
if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $user_id, $chapter_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        if($result->num_rows == 1) {
            $progress = $result->fetch_assoc();
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
    <title><?php echo htmlspecialchars($chapter["chapter_name"]); ?> - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/chapter-detail-bg.jpg') no-repeat center center fixed;
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
            margin-bottom: 40px;
        }
        
        .page-title {
            font-weight: bold;
            color: #2E7D32;
            display: inline-flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .chapter-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }
        
        .chapter-banner {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .chapter-banner h2 {
            font-weight: bold;
            font-size: 2rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .chapter-banner p {
            font-size: 1.1rem;
            margin-bottom: 0;
            max-width: 600px;
            position: relative;
            z-index: 2;
        }
        
        .banner-pattern {
            position: absolute;
            top: 0;
            right: 0;
            width: 40%;
            height: 100%;
            background: url('../assets/images/pattern.png');
            background-size: cover;
            opacity: 0.1;
        }
        
        .chapter-content {
            padding: 30px;
        }
        
        .section-title {
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        .monster-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .monster-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .monster-image {
            width: 100%;
            height: 150px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #2E7D32;
        }
        
        .monster-info {
            padding: 15px;
        }
        
        .monster-name {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2E7D32;
        }
        
        .monster-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .monster-stat {
            background-color: #f5f5f5;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        
        .monster-stat i {
            margin-right: 5px;
            color: #2E7D32;
        }
        
        .question-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .question-title {
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .question-badge {
            background-color: #2E7D32;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        .question-content {
            margin-bottom: 15px;
        }
        
        .battle-btn {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .battle-btn:hover {
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
            transform: scale(1.05);
            color: white;
        }
        
        .progress-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .progress-title {
            font-weight: bold;
            color: #2E7D32;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .progress-bar {
            height: 20px;
            border-radius: 10px;
        }
        
        .stars-container {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }
        
        .star {
            font-size: 2rem;
            color: #E0E0E0;
            margin: 0 5px;
        }
        
        .star.active {
            color: #FFC107;
        }
        
        .chapter-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .action-btn {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
            transform: scale(1.05);
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-book-open"></i> 章節學習
                </h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> 返回關卡列表
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="chapter-container">
            <div class="chapter-banner">
                <div class="banner-pattern"></div>
                <h2><?php echo htmlspecialchars($chapter["chapter_name"]); ?></h2>
                <p><?php echo htmlspecialchars($chapter["summary"]); ?></p>
            </div>
            
            <div class="chapter-content">
                <?php if(!empty($progress) && $progress["is_completed"]): ?>
                    <div class="progress-card">
                        <div class="progress-title">
                            <i class="fas fa-trophy me-2"></i>恭喜！您已完成本章節
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">100%</div>
                        </div>
                        <div class="stars-container">
                            <?php for($i = 1; $i <= 3; $i++): ?>
                                <div class="star <?php echo ($progress["stars_earned"] >= $i) ? 'active' : ''; ?>">
                                    <i class="fas fa-star"></i>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="progress-card">
                        <div class="progress-title">
                            <i class="fas fa-tasks me-2"></i>章節進度
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo !empty($progress) ? '50' : '0'; ?>%" aria-valuenow="<?php echo !empty($progress) ? '50' : '0'; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo !empty($progress) ? '進行中' : '未開始'; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            
                <h3 class="section-title">
                    <i class="fas fa-dragon"></i> 本章怪物
                </h3>
                
                <div class="row g-4">
                    <?php if(count($monsters) > 0): ?>
                        <?php foreach($monsters as $monster): ?>
                            <div class="col-md-4">
                                <div class="monster-card">
                                    <div class="monster-image">
                                        <?php if(!empty($monster["image_path"])): ?>
                                            <img src="<?php echo htmlspecialchars($monster["image_path"]); ?>" alt="<?php echo htmlspecialchars($monster["monster_name"]); ?>" class="img-fluid">
                                        <?php else: ?>
                                            <i class="fas fa-dragon"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="monster-info">
                                        <h4 class="monster-name"><?php echo htmlspecialchars($monster["monster_name"]); ?></h4>
                                        <div class="monster-stats">
                                            <div class="monster-stat">
                                                <i class="fas fa-heart"></i> HP: <?php echo $monster["max_hp"]; ?>
                                            </div>
                                            <div class="monster-stat">
                                                <i class="fas fa-fist-raised"></i> 攻擊: <?php echo $monster["attack_power"]; ?>
                                            </div>
                                            <div class="monster-stat">
                                                <i class="fas fa-shield-alt"></i> 難度: <?php echo htmlspecialchars($monster["difficulty"]); ?>
                                            </div>
                                        </div>
                                        <a href="battle.php?monster_id=<?php echo $monster["monster_id"]; ?>" class="battle-btn">
                                            <i class="fas fa-swords me-1"></i> 開始挑戰
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle me-2"></i>本章節暫無怪物資料。
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h3 class="section-title mt-5">
                    <i class="fas fa-question-circle"></i> 學習題目
                </h3>
                
                <?php if(count($questions) > 0): ?>
                    <?php foreach($questions as $question): ?>
                        <div class="question-card">
                            <div class="question-title">
                                <?php echo htmlspecialchars($question["title"]); ?>
                                <span class="question-badge">
                                    <?php echo htmlspecialchars($question["question_type"]); ?>
                                </span>
                            </div>
                            <div class="question-content">
                                <?php echo nl2br(htmlspecialchars($question["content"])); ?>
                            </div>
                            <a href="question.php?id=<?php echo $question["question_id"]; ?>" class="btn btn-primary">
                                <i class="fas fa-code me-1"></i> 解答題目
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        <i class="fas fa-info-circle me-2"></i>本章節暫無題目資料。
                    </div>
                <?php endif; ?>
                
                <div class="chapter-actions">
                    <?php if(!empty($progress) && $progress["is_completed"]): ?>
                        <a href="index.php" class="action-btn">
                            <i class="fas fa-check-circle me-2"></i>章節已完成 - 返回列表
                        </a>
                    <?php else: ?>
                        <a href="challenge.php?chapter_id=<?php echo $chapter["chapter_id"]; ?>" class="action-btn">
                            <i class="fas fa-play-circle me-2"></i>開始完整挑戰
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
