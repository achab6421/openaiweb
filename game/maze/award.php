<?php
session_start();
require_once "../../config/database_game.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(403);
    exit;
}

$user_id = $_SESSION["id"];
if (!isset($user_id) || empty($user_id)) {
    http_response_code(400);
    exit;
}

// 隨機加 5~10 點
$add_attack = rand(5, 10);
$add_defense = rand(5, 10);

// 更新 users 表
$sql = "UPDATE users SET attack_power = attack_power + ?, defense_power = defense_power + ? WHERE id = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("iii", $add_attack, $add_defense, $user_id);
$stmt->execute();
$stmt->close();
$link->close();

echo json_encode(['attack_power' => $add_attack, 'defense_power' => $add_defense]);
