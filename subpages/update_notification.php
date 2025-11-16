<?php
// update_notification.php
include("global/connection.php");
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false]);
    exit();
}

$userId = $_SESSION["user_id"];
$setting = $_POST['setting'] ?? '';
$value = $_POST['value'] ?? '';

$allowedSettings = ['new_tasks_email', 'new_comments_email', 'system_email'];

if (!in_array($setting, $allowedSettings)) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET $setting = ? WHERE id = ?");
$stmt->bind_param("ii", $value, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>