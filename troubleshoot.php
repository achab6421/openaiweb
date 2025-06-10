<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查用戶是否已登入
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// 包含資料庫連接檔案
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// 獲取章節資料
$chapters = [];
$playerChapters = [];

try {
    // 獲取所有章節
    $chapters_query = "SELECT * FROM chapters ORDER BY chapter_id";
    $chapters_stmt = $db->prepare($chapters_query);
    $chapters_stmt->execute();
    
    while ($row = $chapters_stmt->fetch(PDO::FETCH_ASSOC)) {
        $chapters[] = $row;
    }
    
    // 如果已登入，獲取玩家章節記錄
    if ($isLoggedIn) {
        $player_chapters_query = "SELECT * FROM player_chapter_records WHERE player_id = ?";
        $player_chapters_stmt = $db->prepare($player_chapters_query);
        $player_chapters_stmt->bindParam(1, $_SESSION['user_id']);
        $player_chapters_stmt->execute();
        
        while ($row = $player_chapters_stmt->fetch(PDO::FETCH_ASSOC)) {
            $playerChapters[$row['chapter_id']] = $row;
        }
    }
    
    // 獲取所有關卡
    $levels_query = "SELECT * FROM levels ORDER BY chapter_id, level_id";
    $levels_stmt = $db->prepare($levels_query);
    $levels_stmt->execute();
    
    $levels = [];
    while ($row = $levels_stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($levels[$row['chapter_id']])) {
            $levels[$row['chapter_id']] = [];
        }
        $levels[$row['chapter_id']][] = $row;
    }
    
} catch (PDOException $e) {
    echo "資料庫錯誤：" . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統診斷 - Python 怪物村</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        h1 {
            color: #333;
            border-bottom: 2px solid #555;
            padding-bottom: 10px;
        }
        
        .section {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #444;
            margin-top: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        th {
            background-color: #f2f2f2;
            color: #333;
        }
        
        .status {
            font-weight: bold;
        }
        
        .success {
            color: green;
        }
        
        .warning {
            color: orange;
        }
        
        .error {
            color: red;
        }
        
        .info {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Python 怪物村 - 系統診斷工具</h1>
    
    <div class="section">
        <h2>用戶登入狀態</h2>
        <?php if ($isLoggedIn): ?>
            <p class="status success">已登入</p>
            <table>
                <tr>
                    <th>用戶ID</th>
                    <td><?= $_SESSION['user_id'] ?></td>
                </tr>
                <tr>
                    <th>用戶名</th>
                    <td><?= htmlspecialchars($_SESSION['username']) ?></td>
                </tr>
                <tr>
                    <th>等級</th>
                    <td><?= $_SESSION['level'] ?></td>
                </tr>
                <tr>
                    <th>攻擊力</th>
                    <td><?= $_SESSION['attack_power'] ?></td>
                </tr>
                <tr>
                    <th>血量</th>
                    <td><?= $_SESSION['base_hp'] ?></td>
                </tr>
            </table>
        <?php else: ?>
            <p class="status error">未登入</p>
            <p>請先<a href="index.php">登入</a>後再進行診斷。</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>章節資料 (共 <?= count($chapters) ?> 個)</h2>
        <?php if (empty($chapters)): ?>
            <p class="status error">找不到章節資料，請檢查資料庫。</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>章節名稱</th>
                    <th>難度</th>
                    <th>關卡數</th>
                    <th>是否開放</th>
                    <th>玩家解鎖</th>
                    <th>玩家完成</th>
                    <th>操作</th>
                </tr>
                <?php foreach ($chapters as $chapter): ?>
                    <tr>
                        <td><?= $chapter['chapter_id'] ?></td>
                        <td><?= htmlspecialchars($chapter['chapter_name']) ?></td>
                        <td><?= $chapter['difficulty'] ?></td>
                        <td><?= $chapter['level_count'] ?></td>
                        <td><?= $chapter['is_open'] ? '是' : '否' ?></td>
                        <td>
                            <?php if ($isLoggedIn && isset($playerChapters[$chapter['chapter_id']])): ?>
                                <?= $playerChapters[$chapter['chapter_id']]['is_unlocked'] ? 
                                    '<span class="status success">是</span>' : 
                                    '<span class="status error">否</span>' ?>
                            <?php else: ?>
                                <span class="status warning">無記錄</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isLoggedIn && isset($playerChapters[$chapter['chapter_id']])): ?>
                                <?= $playerChapters[$chapter['chapter_id']]['is_completed'] ? '是' : '否' ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isLoggedIn): ?>
                                <a href="chapter.php?id=<?= $chapter['chapter_id'] ?>">嘗試進入</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>關卡資料</h2>
        <?php if (empty($levels)): ?>
            <p class="status error">找不到關卡資料，請檢查資料庫。</p>
        <?php else: ?>
            <?php foreach ($chapters as $chapter): ?>
                <h3>章節 <?= $chapter['chapter_id'] ?>: <?= htmlspecialchars($chapter['chapter_name']) ?></h3>
                <?php if (isset($levels[$chapter['chapter_id']]) && !empty($levels[$chapter['chapter_id']])): ?>
                    <table>
                        <tr>
                            <th>關卡ID</th>
                            <th>對應怪物ID</th>
                            <th>前置關卡</th>
                            <th>波數</th>
                        </tr>
                        <?php foreach ($levels[$chapter['chapter_id']] as $level): ?>
                            <tr>
                                <td><?= $level['level_id'] ?></td>
                                <td><?= $level['monster_id'] ?></td>
                                <td><?= $level['prerequisite_level_id'] ?: '無' ?></td>
                                <td><?= $level['wave_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <p class="status warning">此章節暫無關卡</p>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>章節解鎖修復</h2>
        <?php if ($isLoggedIn): ?>
            <form method="post" action="fix_chapters.php">
                <p>如果您無法進入已解鎖的章節，可能是玩家章節記錄有問題。點擊下方按鈕嘗試修復。</p>
                <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                <button type="submit" style="padding: 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">
                    執行章節記錄修復
                </button>
            </form>
        <?php else: ?>
            <p>請先登入後再使用此功能。</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>系統信息</h2>
        <table>
            <tr>
                <th>PHP版本</th>
                <td><?= phpversion() ?></td>
            </tr>
            <tr>
                <th>資料庫連接</th>
                <td><?= $db ? '<span class="status success">成功</span>' : '<span class="status error">失敗</span>' ?></td>
            </tr>
            <tr>
                <th>Session信息</th>
                <td>
                    Session ID: <?= session_id() ?><br>
                    Session 狀態: <?= session_status() == PHP_SESSION_ACTIVE ? '活動中' : '未啟動' ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="info">
        <p><a href="dashboard.php">返回主頁</a> | <a href="index.php">返回登入頁</a></p>
    </div>
</body>
</html>
