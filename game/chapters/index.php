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

// 獲取所有章節
$chapters = array();
$sql = "SELECT c.*, 
        CASE WHEN pp.is_completed = 1 THEN 1 ELSE 0 END AS user_completed
        FROM chapters c
        LEFT JOIN player_progress pp ON c.chapter_id = pp.chapter_id AND pp.player_id = ?
        WHERE c.is_hidden = 0
        ORDER BY c.chapter_id ASC";

if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if($stmt->execute()) {
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $chapters[] = $row;
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
    <title>關卡練習 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/chapters-bg.jpg') no-repeat center center fixed;
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
        
        .chapter-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .chapter-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .chapter-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chapter-title {
            font-weight: bold;
            font-size: 1.25rem;
            margin: 0;
        }
        
        .difficulty-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }
        
        .difficulty-beginner {
            background-color: #4CAF50;
        }
        
        .difficulty-intermediate {
            background-color: #FF9800;
        }
        
        .difficulty-advanced {
            background-color: #F44336;
        }
        
        .chapter-body {
            padding: 20px;
        }
        
        .chapter-description {
            margin-bottom: 20px;
        }
        
        .chapter-footer {
            padding: 15px 20px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chapter-stages {
            font-size: 0.9rem;
            color: #666;
        }
        
        .btn-enter {
            background: linear-gradient(45deg, #4CAF50, #2E7D32);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-enter:hover {
            background: linear-gradient(45deg, #2E7D32, #1B5E20);
            transform: scale(1.05);
            color: white;
        }
        
        .btn-locked {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .btn-locked:hover {
            background: #6c757d;
            transform: none;
        }
        
        .completion-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background-color: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
            border-radius: 15px;
        }
        
        .locked-overlay i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .back-btn {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-book"></i> 學習關卡
                </h1>
                <a href="../main.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i> 返回主選單
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <?php foreach($chapters as $chapter): ?>
                <div class="col-lg-6">
                    <div class="chapter-card">
                        <?php if($chapter["user_completed"]): ?>
                            <div class="completion-badge">
                                <i class="fas fa-check"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(!$chapter["is_unlocked"] && $chapter["chapter_id"] > 1): ?>
                            <div class="locked-overlay">
                                <i class="fas fa-lock"></i>
                                <p><?php echo htmlspecialchars($chapter["unlock_hint"]); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="chapter-header">
                            <h2 class="chapter-title"><?php echo htmlspecialchars($chapter["chapter_name"]); ?></h2>
                            <span class="difficulty-badge 
                                <?php 
                                    if($chapter["difficulty"] == "初階") echo "difficulty-beginner";
                                    elseif($chapter["difficulty"] == "中階") echo "difficulty-intermediate";
                                    else echo "difficulty-advanced";
                                ?>">
                                <?php echo htmlspecialchars($chapter["difficulty"]); ?>
                            </span>
                        </div>
                        <div class="chapter-body">
                            <p class="chapter-description"><?php echo htmlspecialchars($chapter["summary"]); ?></p>
                        </div>
                        <div class="chapter-footer">
                            <div class="chapter-stages">
                                <i class="fas fa-layer-group me-1"></i> <?php echo $chapter["num_stages"]; ?> 個關卡
                            </div>
                            <a href="<?php echo $chapter["is_unlocked"] ? 'detail.php?id='.$chapter["chapter_id"] : '#'; ?>" 
                               class="btn btn-enter <?php echo !$chapter["is_unlocked"] ? 'btn-locked disabled' : ''; ?>">
                                <?php echo $chapter["is_unlocked"] ? '進入學習 <i class="fas fa-arrow-right ms-1"></i>' : '鎖定 <i class="fas fa-lock ms-1"></i>'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
