<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
$db = new Database();
$pdo = $db->getConnection();
if (!$pdo) {
    echo json_encode(['success'=>false, 'message'=>'資料庫連線失敗']);
    exit;
}

$room_name = trim($_POST['room_name'] ?? '');
$is_private = isset($_POST['private_room']);
$room_password = $is_private ? trim($_POST['room_password'] ?? '') : '';
$max_players = intval($_POST['max_players'] ?? 4);
$dungeon_id = intval($_POST['dungeon_id'] ?? 0);

function generate_invite_code($length = 8) {
    return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $length);
}

$invite_code = generate_invite_code();
$stmt = $pdo->prepare("SELECT 1 FROM teams WHERE invite_code=?");
$stmt->execute([$invite_code]);
while ($stmt->fetchColumn()) {
    $invite_code = generate_invite_code();
    $stmt->execute([$invite_code]);
}

$is_public = $is_private ? 0 : 1;

$stmt = $pdo->prepare("
    INSERT INTO teams (invite_code, max_members, is_public, room_name, dungeon_id, room_password)
    VALUES (?, ?, ?, ?, ?, ?)
");
if (!$stmt->execute([$invite_code, $max_players, $is_public, $room_name, $dungeon_id, $room_password ?: null])) {
    echo json_encode(['success'=>false, 'message'=>'房間建立失敗']);
    exit;
}

$team_id = $pdo->lastInsertId();

if ($user_id > 0) {
    $stmt = $pdo->prepare("INSERT INTO team_members (user_id, team_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $team_id]);
}

echo json_encode([
    'success' => true,
    'invite_code' => $invite_code,
    'max_members' => $max_players
]);
