<?php
session_start();
require_once "../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'not logged in']);
    exit;
}

$user_id = $_SESSION["user_id"];
if (!isset($user_id) || empty($user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'no user id']);
    exit;
}

// 使用 PDO 連線
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'database connection error']);
    exit;
}

// 隨機加 5~10 點
$add_attack = rand(5, 10);
$add_defense = rand(5, 10);

// 更新 players 表
$sql = "UPDATE players SET attack_power = attack_power + ?, base_hp = base_hp + ? WHERE player_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'prepare failed']);
    exit;
}
$stmt->execute([$add_attack, $add_defense, $user_id]);

echo json_encode(['attack_power' => $add_attack, 'base_hp' => $add_defense]);
