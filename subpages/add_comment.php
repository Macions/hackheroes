<?php
session_start();
include("global/connection.php");
include("global/log_action.php"); // Dodaj include pliku z funkcją logowania

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';
$projectId = (int) ($_POST['project_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        if (empty($comment)) {
            throw new Exception("Komentarz nie może być pusty");
        }


        $stmtCheck = $conn->prepare("SELECT founder_id, name FROM projects WHERE id = ?");
        $stmtCheck->bind_param("i", $projectId);
        $stmtCheck->execute();
        $projectData = $stmtCheck->get_result()->fetch_assoc();
        $founderId = $projectData['founder_id'] ?? 0;
        $projectName = $projectData['name'] ?? '';
        $stmtCheck->close();

        $stmtMember = $conn->prepare("SELECT 1 FROM project_team WHERE project_id = ? AND user_id = ?");
        $stmtMember->bind_param("ii", $projectId, $userId);
        $stmtMember->execute();
        $isMember = $stmtMember->get_result()->num_rows > 0;
        $stmtMember->close();

        if (!$isMember && $userId != $founderId) {
            throw new Exception("Nie masz uprawnień do komentowania tego projektu");
        }


        $stmtInsert = $conn->prepare("INSERT INTO comments (project_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmtInsert->bind_param("iis", $projectId, $userId, $comment);
        $stmtInsert->execute();
        $commentId = $stmtInsert->insert_id;
        $stmtInsert->close();


        $logDetails = "Dodano komentarz do projektu: '{$projectName}' (ID: {$projectId}), Treść: " . substr($comment, 0, 100) . "...";
        logAction($conn, $userId, $userEmail, "add_comment", $logDetails);


        echo json_encode(['success' => true]);

    } catch (Exception $e) {

        $errorMessage = $e->getMessage();
        $logDetails = "Błąd przy dodawaniu komentarza do projektu ID: {$projectId}. Powód: {$errorMessage}";
        logAction($conn, $userId, $userEmail, "comment_error", $logDetails);

        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }
}
?>