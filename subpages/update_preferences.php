<?php

include("global/connection.php");
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nieautoryzowany dostęp']);
    exit();
}

$userId = $_SESSION["user_id"];
$preference = $_POST['preference'] ?? '';
$value = $_POST['value'] ?? '';

$allowedPreferences = ['default_role', 'engagement_level'];

if (!in_array($preference, $allowedPreferences)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa preferencja']);
    exit();
}

if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Błąd połączenia z bazą']);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET $preference = ? WHERE id = ?");
$stmt->bind_param("si", $value, $userId);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']);
}
?>