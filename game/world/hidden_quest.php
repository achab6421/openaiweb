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
$message = '';
$success = false;

// 處理表單提交
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["answer"])) {
    $answer = trim($_POST["answer"]);
    
    // 檢查答案是否匹配任何隱藏關卡
    $sql = "SELECT h.* FROM hidden_stages h 
            LEFT JOIN player_hidden_stages p ON h.id = p.hidden_stage_id AND p.player_id = ? 
            WHERE h.expected_answer = ? AND h.is_active = 1 AND (p.is_unlocked IS NULL OR p.is_unlocked = 0)";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $answer);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $hidden_stage = $result->fetch_assoc();
            $success = true;
            
            // 檢查記錄是否存在
            $sql_check = "SELECT id FROM player_hidden_stages WHERE player_id = ? AND hidden_stage_id = ?";
            if($check_stmt = $conn->prepare($sql_check)) {
                $check_stmt->bind_param("ii", $user_id, $hidden_stage["id"]);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if($check_result->num_rows > 0) {
                    // 更新現有記錄
                    $sql_update = "UPDATE player_hidden_stages SET is_unlocked = 1, unlocked_at = NOW() 
                                  WHERE player_id = ? AND hidden_stage_id = ?";
                    if($update_stmt = $conn->prepare($sql_update)) {
                        $update_stmt->bind_param("ii", $user_id, $hidden_stage["id"]);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                } else {
                    // 創建新記錄
                    $sql_insert = "INSERT INTO player_hidden_stages (player_id, hidden_stage_id, is_unlocked, unlocked_at) 
                                 VALUES (?, ?, 1, NOW())";
                    if($insert_stmt = $conn->prepare($sql_insert)) {
                        $insert_stmt->bind_param("ii", $user_id, $hidden_stage["id"]);
                        $insert_stmt->execute();
                        $insert_stmt->close();
                    }
                }
                $check_stmt->close();
            }
            
            $message = "恭喜！你解鎖了隱藏關卡「{$hidden_stage['stage_name']}」！";
            $message .= "<br>獎勵效果：{$hidden_stage['unlock_effect']}";
            
            // 根據獎勵效果增加用戶屬性
            if(strpos($hidden_stage['unlock_effect'], '攻擊力') !== false) {
                // 解析數值
                preg_match('/\+(\d+)/', $hidden_stage['unlock_effect'], $matches);
                if(isset($matches[1])) {
                    $value = (int)$matches[1];
                    $sql_update = "UPDATE users SET attack_power = attack_power + ? WHERE id = ?";
                    if($attr_stmt = $conn->prepare($sql_update)) {
                        $attr_stmt->bind_param("ii", $value, $user_id);
                        $attr_stmt->execute();
                        $attr_stmt->close();
                    }
                }
            } elseif(strpos($hidden_stage['unlock_effect'], '防禦力') !== false) {
                // 解析數值
                preg_match('/\+(\d+)/', $hidden_stage['unlock_effect'], $matches);
                if(isset($matches[1])) {
                    $value = (int)$matches[1];
                    $sql_update = "UPDATE users SET defense_power = defense_power + ? WHERE id = ?";
                    if($attr_stmt = $conn->prepare($sql_update)) {
                        $attr_stmt->bind_param("ii", $value, $user_id);
                        $attr_stmt->execute();
                        $attr_stmt->close();
                    }
                }
            }
            
            // 更新獎勵已套用狀態
            $sql_applied = "UPDATE player_hidden_stages SET effect_applied = 1 
                          WHERE player_id = ? AND hidden_stage_id = ?";
            if($applied_stmt = $conn->prepare($sql_applied)) {
                $applied_stmt->bind_param("ii", $user_id, $hidden_stage["id"]);
                $applied_stmt->execute();
                $applied_stmt->close();
            }
        } else {
            $message = "答案不正確，或此隱藏關卡已被你解鎖過了。請繼續探索世界！";
        }
        $stmt->close();
    }
}

// 獲取已解鎖的提示
$hints = array();
$sql = "SELECT h.trigger_hint, c.chapter_name FROM hidden_stages h
        JOIN chapters c ON h.chapter_id = c.chapter_id
        LEFT JOIN player_hidden_stages p ON h.id = p.hidden_stage_id AND p.player_id = ?
        WHERE h.is_active = 1 AND (p.is_unlocked IS NULL OR p.is_unlocked = 0)
        ORDER BY c.chapter_id";

if($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $hints[] = $row;
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
    <title>隱藏任務 - Python 怪物村</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        body {
            background: url('../assets/images/hidden-quest-bg.jpg') no-repeat center center fixed;
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
            color: #673ab7;
            display: inline-flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 10px;
            font-size: 1.8rem;
        }
        
        .quest-container {
            background-color: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .quest-intro {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .quest-intro h2 {
            font-weight: bold;
            color: #673ab7;
            margin-bottom: 15px;
        }
        
        .quest-intro p {
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .hints-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .hint-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
        }
        
        .hint-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .hint-location {
            font-weight: bold;
            color: #673ab7;
            margin-bottom: 5px;
        }
        
        .hint-text {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #673ab7;
        }
        
        .answer-form {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .success-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #673ab7;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-scroll"></i> 隱藏任務
                </h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> 返回世界地圖
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if($success): ?>
            <div class="quest-container">
                <div class="success-container">
                    <div class="success-icon">
                        <i class="fas fa-unlock"></i>
                    </div>
                    <h2 class="mb-4">任務完成！</h2>
                    <div class="alert alert-success mb-4">
                        <?php echo $message; ?>
                    </div>
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-map-marked-alt me-2"></i>返回世界地圖
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="quest-container">
                <div class="quest-intro">
                    <h2><i class="fas fa-scroll me-2"></i>隱藏任務中心</h2>
                    <p>在世界各處都隱藏著秘密任務和寶藏。收集線索，解密謎題，獲取特殊獎勵！</p>
                </div>
                
                <?php if(!empty($message)): ?>
                    <div class="alert alert-warning mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="hints-container">
                    <h3 class="mb-4"><i class="fas fa-lightbulb me-2"></i>已收集的線索</h3>
                    
                    <?php if(count($hints) > 0): ?>
                        <?php foreach($hints as $hint): ?>
                            <div class="hint-item">
                                <div class="hint-location">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($hint["chapter_name"]); ?>
                                </div>
                                <div class="hint-text">
                                    "<?php echo htmlspecialchars($hint["trigger_hint"]); ?>"
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>你尚未收集到任何線索。探索世界、完成關卡來獲取隱藏線索！
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="answer-form">
                    <h3 class="mb-3"><i class="fas fa-key me-2"></i>輸入密碼解鎖</h3>
                    <form method="post">
                        <div class="mb-3">
                            <label for="answer" class="form-label">你找到的密碼：</label>
                            <input type="text" class="form-control" id="answer" name="answer" placeholder="輸入你發現的秘密密碼" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-unlock-alt me-2"></i>解鎖隱藏任務
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
