<?php
// 初始化會話
session_start();

// 檢查用戶是否已登入
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 檢查是否提供了位置參數
if(!isset($_GET["location"]) || empty($_GET["location"])) {
    header("location: index.php");
    exit;
}

// 引入設定檔與助手
require_once "../../config/settings.php";
require_once "../../includes/openai_helper.php";

$user_id = $_SESSION["id"];
$location_id = intval($_GET["location"]);

// 檢查位置是否有效
if(!array_key_exists($location_id, CRAWLER_LOCATIONS)) {
    header("location: index.php");
    exit;
}

// 當前地點信息
$location = CRAWLER_LOCATIONS[$location_id];

// 檢查網頁內容和隱藏答案是否存在
if(!isset($_SESSION["treasure_html_{$location_id}"]) || !isset($_SESSION["treasure_answer_{$location_id}"])) {
    header("location: treasure_hunt.php?location=" . $location_id);
    exit;
}

// 記錄使用提示
if(isset($_SESSION["treasure_challenge_id_{$location_id}"]) && $_SESSION["treasure_challenge_id_{$location_id}"] > 0) {
    require_once "../../config/database_game.php";
    
    $challenge_id = $_SESSION["treasure_challenge_id_{$location_id}"];
    
    // 更新使用提示狀態
    $sql = "UPDATE crawler_challenge_logs SET hint_used = 1 WHERE player_id = ? AND challenge_id = ?";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $user_id, $challenge_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $conn->close();
}

$fake_page_content = $_SESSION["treasure_html_{$location_id}"];
$hidden_answer = $_SESSION["treasure_answer_{$location_id}"];

// 生成提示
$hint_text = generateHint($fake_page_content, $hidden_answer, $location["difficulty"]);
$loading = false;
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI助教提示 - <?php echo htmlspecialchars($location["name"]); ?> - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/ai-hint-bg.jpg') no-repeat center center fixed;
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
            color: #7b68ee;
            display: inline-flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .hint-container {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .hint-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .hint-header h2 {
            font-weight: bold;
            color: #7b68ee;
            margin-bottom: 10px;
        }
        
        .ai-icon {
            font-size: 4rem;
            color: #7b68ee;
            margin-bottom: 20px;
        }
        
        .loading-spinner {
            width: 3rem;
            height: 3rem;
            color: #7b68ee;
        }
        
        .hint-content {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            white-space: pre-line;
        }
        
        .hint-footer {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-robot"></i> AI助教提示
                </h1>
                <a href="treasure_hunt.php?location=<?php echo $location_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> 返回尋寶
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="hint-container">
            <div class="hint-header">
                <div class="ai-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <h2>Python爬蟲助教</h2>
                <p class="lead">分析<?php echo htmlspecialchars($location["name"]); ?>的HTML，為您提供尋寶提示</p>
            </div>
            
            <?php if($loading): ?>
                <div class="d-flex justify-content-center">
                    <div class="spinner-border loading-spinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <p class="text-center mt-3">正在思考中，請稍候...</p>
                
                <script>
                    // 自動刷新頁面以獲取結果
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                </script>
            <?php else: ?>
                <div class="hint-content">
                    <?php echo nl2br(htmlspecialchars($hint_text)); ?>
                </div>
                
                <div class="hint-footer">
                    <a href="treasure_hunt.php?location=<?php echo $location_id; ?>" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>繼續尋寶
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
