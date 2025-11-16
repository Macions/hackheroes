<?php
// get_preferences.php
include("global/connection.php");
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nieautoryzowany dostęp']);
    exit();
}

$userId = $_SESSION["user_id"];

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Błąd połączenia z bazą']);
    exit();
}

$stmt = $conn->prepare("SELECT default_role, engagement_level FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $preferences = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'preferences' => $preferences]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Użytkownik nie istnieje']);
}
?>