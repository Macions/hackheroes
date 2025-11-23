<?php
session_start();
include("global/connection.php");
include("global/log_action.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['project_id'])) {
    $requestId = (int) $_POST['request_id'];
    $projectId = (int) $_POST['project_id'];
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    try {
        // Walidacja powodu odrzucenia
        if (empty($rejectionReason)) {
            throw new Exception("Proszę podać powód odrzucenia zgłoszenia.");
        }
        if (strlen($rejectionReason) < 10) {
            throw new Exception("Powód odrzucenia musi zawierać co najmniej 10 znaków.");
        }
        if (strlen($rejectionReason) > 500) {
            throw new Exception("Powód odrzucenia nie może przekraczać 500 znaków.");
        }

        // Pobranie danych zgłoszenia, w tym user_id osoby zgłaszającej
        $checkStmt = $conn->prepare("
            SELECT pa.user_id, p.founder_id, u.nick, u.email, p.name as project_name
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

        // Sprawdzenie uprawnień (właściciel lub developer)
        if ($application['founder_id'] != $userId) {
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

        // Rozpoczęcie transakcji
        $conn->begin_transaction();

        try {
            // Aktualizacja zgłoszenia z powodem odrzucenia
            $updateStmt = $conn->prepare("
                UPDATE project_applications 
                SET status = 'rejected', processed_at = NOW(), rejection_reason = ?
                WHERE id = ? AND project_id = ?
            ");
            $updateStmt->bind_param("sii", $rejectionReason, $requestId, $projectId);
            $updateStmt->execute();

            if ($updateStmt->affected_rows === 0) {
                throw new Exception("Nie udało się odrzucić zgłoszenia.");
            }
            $updateStmt->close();

            // Dodanie powiadomienia dla użytkownika
            $notificationStmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, type, is_read, related_url, created_at) 
                VALUES (?, ?, ?, 'application_rejected', 0, ?, NOW())
            ");
            $notificationTitle = "Twoje zgłoszenie do projektu zostało odrzucone";
            $notificationMessage = "Twoje zgłoszenie do projektu '{$application['project_name']}' zostało odrzucone.\n\nPowód: {$rejectionReason}";
            $relatedUrl = "project.php?id=" . $projectId;
            $notificationStmt->bind_param("isss", $application['user_id'], $notificationTitle, $notificationMessage, $relatedUrl);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Zatwierdzenie transakcji
            $conn->commit();

            // Logowanie akcji
            $logDetails = "Odrzucono zgłoszenie użytkownika '{$application['nick']}' (ID: {$application['user_id']}) do projektu '{$application['project_name']}' (ID: {$projectId}). Powód: " . substr($rejectionReason, 0, 100) . "...";
            logAction($conn, $userId, $userEmail, "reject_application", $logDetails);

            $_SESSION['success_message'] = "Zgłoszenie użytkownika " . htmlspecialchars($application['nick']) . " zostało odrzucone.";
            header("Location: project.php?id=" . $projectId . "&tab=requests");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errorDetails = "Błąd przy odrzucaniu zgłoszenia ID: {$requestId} do projektu ID: {$projectId}. Powód: " . $e->getMessage();
            logAction($conn, $userId, $userEmail, "reject_application_error", $errorDetails);
            throw $e;
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Błąd: " . $e->getMessage();
        header("Location: project.php?id=" . $projectId . "&tab=requests");
        exit();
    }

} else {
    $_SESSION['error_message'] = "Nieprawidłowe żądanie.";
    header("Location: projects.php");
    exit();
}
?>