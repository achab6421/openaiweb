<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}
require_once "config/database.php";
$db = (new Database())->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '') {
    echo json_encode(['success' => false, 'message' => '名稱不能為空']);
    exit;
}
if (mb_strlen($username) > 32) {
    echo json_encode(['success' => false, 'message' => '名稱過長']);
    exit;
}

// 檢查名稱是否重複（排除自己）
$stmt = $db->prepare("SELECT player_id FROM players WHERE username = ? AND player_id != ?");
$stmt->execute([$username, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '名稱已被使用']);
    exit;
}

// 更新資料
if ($password !== '') {
    // 密碼加密
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE players SET username = ?, password = ? WHERE player_id = ?");
    $ok = $stmt->execute([$username, $hashed, $_SESSION['user_id']]);
} else {
    $stmt = $db->prepare("UPDATE players SET username = ? WHERE player_id = ?");
    $ok = $stmt->execute([$username, $_SESSION['user_id']]);
}
if ($ok) {
    $_SESSION['username'] = $username;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '資料庫更新失敗']);
}
