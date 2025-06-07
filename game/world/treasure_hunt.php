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

// 引入資料庫連接文件
require_once "../../config/database_game.php";
require_once "../../config/settings.php";
require_once "../../includes/openai_helper.php";
require_once "../../includes/temp_folder_setup.php";

$user_id = $_SESSION["id"];
$location_id = intval($_GET["location"]);

// 檢查位置是否有效
if(!array_key_exists($location_id, CRAWLER_LOCATIONS)) {
    header("location: index.php");
    exit;
}

// 檢查用戶已完成的章節數
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

// 檢查用戶是否可以訪問這個位置
if($completed_chapters < CRAWLER_LOCATIONS[$location_id]['required_level']) {
    header("location: index.php");
    exit;
}

// 當前地點信息
$location = CRAWLER_LOCATIONS[$location_id];

// 處理表單提交
$message = '';
$success = false;
$fake_page_content = '';
$hidden_answer = '';
$submitted_answer = '';

// 如果沒有進行過尋寶或重置，則生成新網頁
if(!isset($_SESSION["treasure_html_{$location_id}"]) || isset($_POST["reset"])) {
    // 檢查數據庫中是否已經存在該位置的挑戰
    $sql = "SELECT id, html_content, hidden_answer FROM crawler_challenges WHERE location_id = ? ORDER BY created_at DESC LIMIT 1";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $challenge = $result->fetch_assoc();
            if(!empty($challenge['html_content']) && !empty($challenge['hidden_answer'])) {
                $fake_page_content = $challenge['html_content'];
                $hidden_answer = $challenge['hidden_answer'];
                $challenge_id = $challenge['id'];
            } else {
                // 生成假網頁與隱藏答案
                $webpage_data = generateFakeWebpage($location["theme"], $location["difficulty"]);
                $fake_page_content = $webpage_data["html"];
                $hidden_answer = $webpage_data["answer"];
                
                // 更新數據庫
                $sql_update = "UPDATE crawler_challenges SET html_content = ?, hidden_answer = ? WHERE id = ?";
                if($update_stmt = $conn->prepare($sql_update)) {
                    $update_stmt->bind_param("ssi", $fake_page_content, $hidden_answer, $challenge_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        } else {
            // 生成假網頁與隱藏答案
            $webpage_data = generateFakeWebpage($location["theme"], $location["difficulty"]);
            $fake_page_content = $webpage_data["html"];
            $hidden_answer = $webpage_data["answer"];
            
            // 插入到數據庫
            $sql_insert = "INSERT INTO crawler_challenges (location_id, theme, difficulty, html_content, hidden_answer) VALUES (?, ?, ?, ?, ?)";
            if($insert_stmt = $conn->prepare($sql_insert)) {
                $insert_stmt->bind_param("issss", $location_id, $location["theme"], $location["difficulty"], $fake_page_content, $hidden_answer);
                $insert_stmt->execute();
                $challenge_id = $insert_stmt->insert_id;
                $insert_stmt->close();
            }
        }
        $stmt->close();
    }
    
    // 將假網頁和答案保存到會話
    $_SESSION["treasure_html_{$location_id}"] = $fake_page_content;
    $_SESSION["treasure_answer_{$location_id}"] = $hidden_answer;
    $_SESSION["treasure_challenge_id_{$location_id}"] = $challenge_id ?? 0;
}

// 獲取會話中的網頁內容和答案
$fake_page_content = $_SESSION["treasure_html_{$location_id}"];
$hidden_answer = $_SESSION["treasure_answer_{$location_id}"];
$challenge_id = $_SESSION["treasure_challenge_id_{$location_id}"] ?? 0;

// 如果表單被提交
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["answer"])) {
    $start_time = $_SESSION["treasure_start_time_{$location_id}"] ?? time();
    $end_time = time();
    $time_spent = $end_time - $start_time;
    
    $submitted_answer = trim($_POST["answer"]);
    
    // 記錄嘗試
    $attempt_count = 1;
    $sql_check = "SELECT id, attempt_count FROM crawler_challenge_logs WHERE player_id = ? AND challenge_id = ?";
    if($check_stmt = $conn->prepare($sql_check)) {
        $check_stmt->bind_param("ii", $user_id, $challenge_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $log_row = $check_result->fetch_assoc();
            $log_id = $log_row['id'];
            $attempt_count = $log_row['attempt_count'] + 1;
            
            $sql_update = "UPDATE crawler_challenge_logs SET attempt_count = ? WHERE id = ?";
            if($update_stmt = $conn->prepare($sql_update)) {
                $update_stmt->bind_param("ii", $attempt_count, $log_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        } else {
            $sql_insert = "INSERT INTO crawler_challenge_logs (player_id, challenge_id, attempt_count) VALUES (?, ?, 1)";
            if($insert_stmt = $conn->prepare($sql_insert)) {
                $insert_stmt->bind_param("ii", $user_id, $challenge_id);
                $insert_stmt->execute();
                $log_id = $insert_stmt->insert_id;
                $insert_stmt->close();
            }
        }
        $check_stmt->close();
    }
    
    // 檢查答案是否正確
    if($submitted_answer == $hidden_answer) {
        // 答案正確
        $success = true;
        $message = "恭喜！你找到了正確的答案！";
        
        // 更新挑戰記錄
        $sql_solve = "UPDATE crawler_challenge_logs SET is_solved = 1, time_spent = ?, solved_at = NOW() WHERE player_id = ? AND challenge_id = ?";
        if($solve_stmt = $conn->prepare($sql_solve)) {
            $solve_stmt->bind_param("iii", $time_spent, $user_id, $challenge_id);
            $solve_stmt->execute();
            $solve_stmt->close();
        }
        
        // 增加用戶尋寶次數
        $sql = "UPDATE users SET treasure_hunts = IFNULL(treasure_hunts, 0) + 1 WHERE id = ?";
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // 隨機獲取獎勵
        $rewards = [
            "attack_power" => ["name" => "攻擊力", "value" => rand(5, 20)],
            "defense_power" => ["name" => "防禦力", "value" => rand(5, 20)],
            "experience_points" => ["name" => "經驗值", "value" => rand(50, 200)]
        ];
        
        $reward_type = array_rand($rewards);
        $reward = $rewards[$reward_type];
        
        // 增加用戶屬性
        $sql = "UPDATE users SET {$reward_type} = IFNULL({$reward_type}, 0) + ? WHERE id = ?";
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $reward["value"], $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $message .= "<br>你獲得了 +{$reward["value"]} {$reward["name"]}！";
        
        // 一定概率解鎖隱藏關卡
        $unlock_chance = 0.2; // 20%機率
        if(mt_rand(1, 100) <= $unlock_chance * 100) {
            // 獲取隨機未解鎖的隱藏關卡
            $sql = "SELECT h.id FROM hidden_stages h 
                    LEFT JOIN player_hidden_stages p ON h.id = p.hidden_stage_id AND p.player_id = ? 
                    WHERE h.is_active = 1 AND (p.is_unlocked IS NULL OR p.is_unlocked = 0)
                    ORDER BY RAND() LIMIT 1";
            
            if($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                if($stmt->execute()) {
                    $result = $stmt->get_result();
                    if($row = $result->fetch_assoc()) {
                        $hidden_stage_id = $row["id"];
                        
                        // 檢查記錄是否存在
                        $sql_check = "SELECT id FROM player_hidden_stages WHERE player_id = ? AND hidden_stage_id = ?";
                        if($check_stmt = $conn->prepare($sql_check)) {
                            $check_stmt->bind_param("ii", $user_id, $hidden_stage_id);
                            $check_stmt->execute();
                            $check_result = $check_stmt->get_result();
                            
                            if($check_result->num_rows > 0) {
                                // 更新現有記錄
                                $sql_update = "UPDATE player_hidden_stages SET is_unlocked = 1, unlocked_at = NOW() 
                                            WHERE player_id = ? AND hidden_stage_id = ?";
                                if($update_stmt = $conn->prepare($sql_update)) {
                                    $update_stmt->bind_param("ii", $user_id, $hidden_stage_id);
                                    $update_stmt->execute();
                                    $update_stmt->close();
                                }
                            } else {
                                // 創建新記錄
                                $sql_insert = "INSERT INTO player_hidden_stages (player_id, hidden_stage_id, is_unlocked, unlocked_at) 
                                            VALUES (?, ?, 1, NOW())";
                                if($insert_stmt = $conn->prepare($sql_insert)) {
                                    $insert_stmt->bind_param("ii", $user_id, $hidden_stage_id);
                                    $insert_stmt->execute();
                                    $insert_stmt->close();
                                }
                            }
                            $check_stmt->close();
                        }
                        
                        // 獲取隱藏關卡名稱
                        $sql_name = "SELECT stage_name FROM hidden_stages WHERE id = ?";
                        if($name_stmt = $conn->prepare($sql_name)) {
                            $name_stmt->bind_param("i", $hidden_stage_id);
                            $name_stmt->execute();
                            $name_result = $name_stmt->get_result();
                            if($name_row = $name_result->fetch_assoc()) {
                                $message .= "<br><strong>驚喜！</strong> 你發現了隱藏關卡「{$name_row["stage_name"]}」！";
                            }
                            $name_stmt->close();
                        }
                    }
                }
                $stmt->close();
            }
        }
    } else {
        // 答案錯誤
        $message = "答案不正確，請再試一次！";
    }
}

// 記錄開始時間
if(!isset($_SESSION["treasure_start_time_{$location_id}"])) {
    $_SESSION["treasure_start_time_{$location_id}"] = time();
}

// 關閉連接
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($location["name"]); ?> 尋寶 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/treasure-bg.jpg') no-repeat center center fixed;
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
            color: #ff9800;
            display: inline-flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .treasure-container {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            margin-bottom: 40px;
        }
        
        .treasure-intro {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .treasure-intro h2 {
            font-weight: bold;
            color: #ff9800;
            margin-bottom: 15px;
        }
        
        .treasure-intro p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .crawl-instructions {
            background-color: white;
            border-left: 4px solid #ff9800;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .instruction-title {
            font-weight: bold;
            color: #ff9800;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .tabs {
            margin-bottom: 20px;
        }
        
        .tab-content {
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .webpage-preview {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            height: 600px;
            overflow-y: auto;
            background-color: white;
        }
        
        .code-area {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            height: 600px;
            overflow-y: auto;
            background-color: #2d2d2d;
            color: #f8f8f2;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            white-space: pre-wrap;
        }
        
        .code-area::-webkit-scrollbar {
            width: 10px;
        }
        
        .code-area::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .code-area::-webkit-scrollbar-thumb {
            background: #888;
        }
        
        .code-area::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        .answer-form {
            margin-top: 30px;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .answer-label {
            font-weight: bold;
            color: #ff9800;
            margin-bottom: 10px;
        }
        
        .nav-tabs .nav-item .nav-link {
            color: #6c757d;
        }
        
        .nav-tabs .nav-item .nav-link.active {
            color: #ff9800;
            font-weight: bold;
        }
        
        .success-treasure {
            text-align: center;
            padding: 40px 20px;
        }
        
        .treasure-icon {
            font-size: 5rem;
            color: #ff9800;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-30px);
            }
            60% {
                transform: translateY(-15px);
            }
        }
        
        .treasure-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .example-code {
            background-color: #f5f5f5;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin-bottom: 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
        }
        
        .hint-alert {
            margin-top: 20px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-search"></i> <?php echo htmlspecialchars($location["name"]); ?> 尋寶
                </h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> 返回世界地圖
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if($success): ?>
        <div class="treasure-container">
            <div class="success-treasure">
                <div class="treasure-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="treasure-message">
                    <?php echo $message; ?>
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="d-grid gap-2">
                            <form method="post">
                                <button type="submit" name="reset" class="btn btn-warning btn-lg">
                                    <i class="fas fa-redo me-2"></i>再次尋寶
                                </button>
                            </form>
                            <a href="index.php" class="btn btn-secondary btn-lg mt-2">
                                <i class="fas fa-map-marked-alt me-2"></i>返回世界地圖
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="treasure-container">
            <div class="treasure-intro">
                <h2><i class="fas fa-spider me-2"></i><?php echo htmlspecialchars($location["name"]); ?> 爬蟲挑戰</h2>
                <p>利用你的Python爬蟲技能，分析下方的網頁並找出隱藏其中的秘密答案！隱藏答案格式為 ans_XXXXXX，練習使用各種爬蟲技術進行尋找。</p>
            </div>
            
            <div class="crawl-instructions">
                <h3 class="instruction-title"><i class="fas fa-info-circle me-2"></i>爬蟲提示</h3>
                <p>
                    答案可能隱藏在HTML的任何地方 - 可能在注釋中、屬性裡、甚至被加密或編碼。使用以下Python工具來協助你：
                </p>
                
                <div class="example-code">
<pre>import requests
from bs4 import BeautifulSoup
import re  # 正則表達式

# 獲取網頁內容
url = "網頁URL"
response = requests.get(url)
html = response.text

# 使用BeautifulSoup解析
soup = BeautifulSoup(html, 'html.parser')

# 尋找特定元素
elements = soup.find_all('div', class_='特定類名')

# 查找包含特定模式的文本
pattern = r'ans_[A-Za-z0-9]+'
matches = re.findall(pattern, html)
print(matches)</pre>
                </div>
                
                <div class="alert alert-warning hint-alert">
                    <i class="fas fa-lightbulb me-2"></i>提示：難度為「<?php echo htmlspecialchars($location["difficulty"]); ?>」，答案可能<?php echo $location["difficulty"] == "初階" ? "明顯可見" : ($location["difficulty"] == "中階" ? "稍微隱藏" : "深度隱藏或加密"); ?>。
                </div>
            </div>
            
            <ul class="nav nav-tabs tabs" id="crawlTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-controls="preview" aria-selected="true">
                        <i class="fas fa-eye me-1"></i> 網頁預覽
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="source-tab" data-bs-toggle="tab" data-bs-target="#source" type="button" role="tab" aria-controls="source" aria-selected="false">
                        <i class="fas fa-code me-1"></i> HTML 原始碼
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="crawlTabsContent">
                <div class="tab-pane fade show active" id="preview" role="tabpanel" aria-labelledby="preview-tab">
                    <div class="webpage-preview">
                        <?php echo $fake_page_content; ?>
                    </div>
                </div>
                <div class="tab-pane fade" id="source" role="tabpanel" aria-labelledby="source-tab">
                    <div class="code-area"><code><?php echo htmlspecialchars($fake_page_content); ?></code></div>
                </div>
            </div>
            
            <form class="answer-form" method="post">
                <div class="mb-3">
                    <label for="answer" class="form-label answer-label">輸入您找到的寶藏答案：</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="answer" name="answer" placeholder="例如：ans_ABC123" required>
                        <button class="btn btn-warning" type="submit">
                            <i class="fas fa-check-circle me-1"></i> 提交答案
                        </button>
                    </div>
                    <?php if(!empty($message) && !$success): ?>
                        <div class="form-text text-danger mt-2"><?php echo $message; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" name="reset" class="btn btn-outline-secondary">
                        <i class="fas fa-redo me-1"></i> 重新生成網頁
                    </button>
                    <a href="hint.php?location=<?php echo $location_id; ?>" class="btn btn-outline-info">
                        <i class="fas fa-lightbulb me-1"></i> 請求AI助教提示
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
