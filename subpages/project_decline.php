<?php
session_start();
include("global/connection.php");

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];

// Sprawdź czy otrzymano dane POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['project_id'])) {
    $requestId = (int) $_POST['request_id'];
    $projectId = (int) $_POST['project_id'];

    try {
        // Sprawdź czy użytkownik ma uprawnienia do odrzucania zgłoszeń
        $checkStmt = $conn->prepare("
            SELECT p.founder_id, u.nick 
            FROM project_applications pa 
            JOIN projects p ON pa.project_id = p.id 
            JOIN users u ON pa.user_id = u.id 
            WHERE pa.id = ? AND pa.project_id = ? AND pa.status = 'pending'
        ");
        $checkStmt->bind_param("ii", $requestId, $projectId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Zgłoszenie nie istnieje lub zostało już przetworzone.");
        }

        $application = $result->fetch_assoc();
        $checkStmt->close();

        // Sprawdź czy użytkownik jest właścicielem projektu
        if ($application['founder_id'] != $userId) {
            // Sprawdź czy użytkownik jest developerem w projekcie
            $devCheckStmt = $conn->prepare("
                SELECT role FROM project_team 
                WHERE project_id = ? AND user_id = ? AND role = 'developer'
            ");
            $devCheckStmt->bind_param("ii", $projectId, $userId);
            $devCheckStmt->execute();
            $isDeveloper = $devCheckStmt->get_result()->num_rows > 0;
            $devCheckStmt->close();

            if (!$isDeveloper) {
                throw new Exception("Nie masz uprawnień do odrzucania zgłoszeń w tym projekcie.");
            }
        }

        // Odrzuć zgłoszenie
        $updateStmt = $conn->prepare("
            UPDATE project_applications 
            SET status = 'rejected', processed_at = NOW() 
            WHERE id = ? AND project_id = ?
        ");
        $updateStmt->bind_param("ii", $requestId, $projectId);
        $updateStmt->execute();

        if ($updateStmt->affected_rows === 0) {
            throw new Exception("Nie udało się odrzucić zgłoszenia.");
        }
        $updateStmt->close();

        // Przekieruj z komunikatem sukcesu
        $_SESSION['success_message'] = "Zgłoszenie użytkownika " . htmlspecialchars($application['nick']) . " zostało odrzucone.";
        header("Location: project.php?id=" . $projectId . "&tab=requests");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Błąd: " . $e->getMessage();
        header("Location: project.php?id=" . $projectId);
        exit();
    }
} else {
    $_SESSION['error_message'] = "Nieprawidłowe żądanie.";
    header("Location: projects.php");
    exit();
}
?>