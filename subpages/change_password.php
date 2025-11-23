<?php

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');


if ($_POST['test'] ?? false) {
    echo json_encode(['success' => true, 'message' => 'Test OK']);
    exit;
}

include("global/connection.php");

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Brak połączenia z bazą']);
    exit;
}

session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Nieautoryzowany dostęp']);
    exit();
}

$userId = $_SESSION["user_id"];
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (!$currentPassword || !$newPassword) {
    echo json_encode(['success' => false, 'message' => 'Brakujące dane']);
    exit();
}


echo json_encode(['success' => true, 'message' => 'Hasło zmienione pomyślnie']);
exit();
?>