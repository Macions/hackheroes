<?php
session_start();
include("global/connection.php");

// Sprawdź, czy użytkownik jest zalogowany
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false]);
    exit;
}

$userId = $_SESSION['user_id'];
$projectId = $_POST['project_id'] ?? 0;
$comment = trim($_POST['comment'] ?? '');

if (!$projectId || !$comment) {
    echo json_encode(['success' => false]);
    exit;
}

// Wstaw komentarz do bazy
$stmt = $conn->prepare("INSERT INTO comments (user_id, project_id, comment, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $userId, $projectId, $comment);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
