<?php
// 用於AJAX動態取得房間成員
session_start();
require_once __DIR__ . '/../../config/database.php';

$invite_code = isset($_GET['code']) ? $_GET['code'] : '';
if (!$invite_code) {
    echo json_encode(['success' => false, 'message' => '缺少房間代碼']);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT t.team_id FROM teams t WHERE t.invite_code = ?");
$stmt->execute([$invite_code]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$room) {
    echo json_encode(['success' => false, 'message' => '房間不存在']);
    exit;
}

$stmt = $pdo->prepare("SELECT tm.user_id, p.username FROM team_members tm JOIN players p ON tm.user_id = p.player_id WHERE tm.team_id = ? ORDER BY tm.member_id ASC, tm.joined_at ASC");
$stmt->execute([$room['team_id']]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$room_owner_id = $members[0]['user_id'] ?? null;

echo json_encode([
    'success' => true,
    'members' => $members,
    'room_owner_id' => $room_owner_id
]);
