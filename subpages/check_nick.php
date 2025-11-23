<?php

include("global/connection.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



$nick = trim($_REQUEST['nick'] ?? '');

if ($nick === '') {
    echo json_encode(['status' => 'error', 'message' => 'Nie podano nicku']);
    exit;
}


$userId = $_SESSION["user_id"] ?? 0;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE nick = ? AND id != ?");
    $stmt->bind_param("si", $nick, $userId);
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE nick = ?");
    $stmt->bind_param("s", $nick);
}

$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'taken']);
} else {
    echo json_encode(['status' => 'available']);
}

$stmt->close();
$conn->close();
?>