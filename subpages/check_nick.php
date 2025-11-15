<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$hostname = "localhost";
$username = "root";
$password = "";
$database = "teencollab";

$conn = new mysqli($hostname, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Błąd połączenia z bazą']));
}

$nick = trim($_GET['nick'] ?? '');

if ($nick === '') {
    echo json_encode(['status' => 'error', 'message' => 'Nie podano nicku']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE nick=?");
$stmt->bind_param("s", $nick);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'taken']);
} else {
    echo json_encode(['status' => 'available']);
}

$stmt->close();
$conn->close();
