<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany']);
    exit();
}

$userId = $_SESSION["user_id"];
$projectId = $_POST['project_id'] ?? null;
$motivation = $_POST['motivation'] ?? '';
$role = $_POST['role'] ?? '';
$availability = $_POST['availability'] ?? '';

if (!$projectId) {
    echo json_encode(['success' => false, 'message' => 'Brak ID projektu']);
    exit();
}

try {
    // Sprawdź czy projekt istnieje i czy przyjmuje zgłoszenia
    $stmt = $conn->prepare("SELECT allow_applications, auto_accept FROM projects WHERE id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    
    if (!$project) {
        throw new Exception("Projekt nie istnieje");
    }
    
    if (!$project['allow_applications']) {
        throw new Exception("Ten projekt nie przyjmuje zgłoszeń");
    }
    
    $stmt->close();
    
    // Sprawdź czy użytkownik już złożył zgłoszenie
    $checkStmt = $conn->prepare("SELECT id FROM project_applications WHERE project_id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $projectId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception("Już złożyłeś zgłoszenie do tego projektu");
    }
    $checkStmt->close();
    
    // Zapisz zgłoszenie
    $insertStmt = $conn->prepare("
        INSERT INTO project_applications (project_id, user_id, motivation, desired_role, availability, status, applied_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $insertStmt->bind_param("iisss", $projectId, $userId, $motivation, $role, $availability);
    $insertStmt->execute();
    $insertStmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Zgłoszenie zostało wysłane pomyślnie'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>