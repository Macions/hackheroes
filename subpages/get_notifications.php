<?php

error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');


if (isset($_GET['test'])) {
    echo json_encode(['success' => true, 'settings' => [
        'new_tasks_email' => true,
        'new_comments_email' => true,
        'system_email' => false
    ]]);
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

try {
    $stmt = $conn->prepare("SELECT new_tasks_email, new_comments_email, system_email FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Błąd przygotowania zapytania');
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $settings = $result->fetch_assoc();

        $settings['new_tasks_email'] = (bool)$settings['new_tasks_email'];
        $settings['new_comments_email'] = (bool)$settings['new_comments_email'];
        $settings['system_email'] = (bool)$settings['system_email'];
        
        echo json_encode(['success' => true, 'settings' => $settings]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Użytkownik nie istnieje']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd: ' . $e->getMessage()]);
}
?>