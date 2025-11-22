<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = (int)$_POST['project_id'];
    $comment = trim($_POST['comment']);
    
    try {
        // Sprawdź czy użytkownik jest członkiem projektu
        $checkStmt = $conn->prepare("
            SELECT pt.role 
            FROM project_team pt 
            WHERE pt.project_id = ? AND pt.user_id = ?
        ");
        $checkStmt->bind_param("ii", $projectId, $userId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows === 0) {
            throw new Exception("Nie jesteś członkiem tego projektu.");
        }
        $checkStmt->close();
        
        if (empty($comment)) {
            throw new Exception("Komentarz nie może być pusty.");
        }
        
        // Dodaj komentarz
        $insertStmt = $conn->prepare("
            INSERT INTO comments (project_id, user_id, comment, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $insertStmt->bind_param("iis", $projectId, $userId, $comment);
        $insertStmt->execute();
        
        if ($insertStmt->affected_rows === 0) {
            throw new Exception("Nie udało się dodać komentarza.");
        }
        
        $insertStmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Komentarz dodany pomyślnie.']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>