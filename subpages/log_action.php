<?php
// log_action.php z rozszerzonym debugowaniem
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include("global/connection.php");
session_start();

// Zapisuj wszystko do pliku debug
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " === START ===\n", FILE_APPEND);
file_put_contents('debug_log.txt', "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug_log.txt', "SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    file_put_contents('debug_log.txt', "BŁĄD: Nie zalogowany\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nieautoryzowany']);
    exit();
}

$userId = $_SESSION["user_id"];
$email = $_POST['email'] ?? '';
$action = $_POST['action'] ?? '';
$details = $_POST['details'] ?? '';

file_put_contents('debug_log.txt', "Dane: user_id=$userId, email=$email, action=$action, details=$details\n", FILE_APPEND);

if (!$conn) {
    file_put_contents('debug_log.txt', "BŁĄD: Brak połączenia z bazą\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd bazy']);
    exit();
}

// Sprawdź czy tabela istnieje
$tableCheck = $conn->query("SHOW TABLES LIKE 'logs'");
if ($tableCheck->num_rows === 0) {
    file_put_contents('debug_log.txt', "BŁĄD: Tabela logs nie istnieje\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Tabela logs nie istnieje']);
    exit();
}

try {
    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    file_put_contents('debug_log.txt', "IP: $ipAddress, UserAgent: $userAgent\n", FILE_APPEND);
    
    $stmt = $conn->prepare("INSERT INTO logs (user_id, email, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception('Błąd prepare: ' . $conn->error);
    }
    
    $stmt->bind_param("isssss", $userId, $email, $action, $details, $ipAddress, $userAgent);
    
    if ($stmt->execute()) {
        $lastId = $conn->insert_id;
        file_put_contents('debug_log.txt', "SUKCES: Zapisano log ID: $lastId\n", FILE_APPEND);
        echo json_encode(['success' => true, 'log_id' => $lastId]);
    } else {
        throw new Exception('Błąd execute: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    file_put_contents('debug_log.txt', "BŁĄD: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

file_put_contents('debug_log.txt', "=== KONEC ===\n\n", FILE_APPEND);
?>