<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 檢查用戶是否已登入
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// 檢查是否有POST請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: troubleshoot.php');
    exit;
}

// 確認用戶ID是否匹配
if (!isset($_POST['user_id']) || intval($_POST['user_id']) !== intval($_SESSION['user_id'])) {
    die("用戶ID不匹配，操作已取消");
}

// 包含資料庫連接檔案
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];

try {
    // 開始事務
    $db->beginTransaction();
    
    // 1. 獲取所有章節
    $chapters_query = "SELECT * FROM chapters ORDER BY chapter_id";
    $chapters_stmt = $db->prepare($chapters_query);
    $chapters_stmt->execute();
    $chapters = $chapters_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. 獲取當前玩家章節記錄
    $player_chapters_query = "SELECT * FROM player_chapter_records WHERE player_id = ?";
    $player_chapters_stmt = $db->prepare($player_chapters_query);
    $player_chapters_stmt->bindParam(1, $userId);
    $player_chapters_stmt->execute();
    
    $existingRecords = [];
    while ($row = $player_chapters_stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingRecords[$row['chapter_id']] = $row;
    }
    
    $results = [
        'created' => 0,
        'updated' => 0,
        'errors' => [],
    ];
    
    // 3. 確保所有章節都有記錄，第一章解鎖
    foreach ($chapters as $chapter) {
        $chapterId = $chapter['chapter_id'];
        
        // 檢查是否已存在記錄
        if (isset($existingRecords[$chapterId])) {
            // 如果是第一章，確保設置為解鎖
            if ($chapterId == 1) {
                $update_query = "UPDATE player_chapter_records SET is_unlocked = 1 WHERE player_id = ? AND chapter_id = ?";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(1, $userId);
                $update_stmt->bindParam(2, $chapterId);
                $update_stmt->execute();
                $results['updated']++;
            }
        } else {
            // 創建新記錄
            $is_unlocked = ($chapterId == 1) ? 1 : 0; // 第一章解鎖，其他章節鎖定
            
            $insert_query = "INSERT INTO player_chapter_records (player_id, chapter_id, is_unlocked, is_completed) VALUES (?, ?, ?, 0)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(1, $userId);
            $insert_stmt->bindParam(2, $chapterId);
            $insert_stmt->bindParam(3, $is_unlocked);
            $insert_stmt->execute();
            $results['created']++;
        }
    }
    
    // 提交事務
    $db->commit();
    
    // 返回結果
    $_SESSION['fix_results'] = $results;
    header('Location: troubleshoot.php?fixed=1');
    
} catch (PDOException $e) {
    // 回滾事務
    $db->rollBack();
    die("操作失敗：" . $e->getMessage());
}
?>
