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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['project_id'])) {
    $requestId = (int) $_POST['request_id'];
    $projectId = (int) $_POST['project_id'];

    try {
        $checkStmt = $conn->prepare("
            SELECT p.founder_id, pa.user_id, pa.desired_role, pa.motivation, u.nick, p.name as project_name
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

        // Sprawdź uprawnienia
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
                throw new Exception("Nie masz uprawnień do akceptowania zgłoszeń w tym projekcie.");
            }
        }

        // Rozpocznij transakcję
        $conn->begin_transaction();

        try {
            // Aktualizuj status zgłoszenia
            $updateStmt = $conn->prepare("
                UPDATE project_applications 
                SET status = 'accepted', processed_at = NOW() 
                WHERE id = ? AND project_id = ?
            ");
            $updateStmt->bind_param("ii", $requestId, $projectId);
            $updateStmt->execute();

            if ($updateStmt->affected_rows === 0) {
                throw new Exception("Nie udało się zaktualizować zgłoszenia.");
            }
            $updateStmt->close();

            // Dodaj użytkownika do zespołu
            $role = !empty($application['desired_role']) ? $application['desired_role'] : 'member';

            $insertStmt = $conn->prepare("
                INSERT INTO project_team (project_id, user_id, role, joined_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE role = VALUES(role)
            ");
            $insertStmt->bind_param("iis", $projectId, $application['user_id'], $role);
            $insertStmt->execute();

            if ($insertStmt->affected_rows === 0) {
                throw new Exception("Nie udało się dodać użytkownika do zespołu.");
            }
            $insertStmt->close();

            // Zatwierdź transakcję
            $conn->commit();

            // ✅ DODAJ REKORD DO LOGS
            $logDetails = "Zaakceptowano zgłoszenie użytkownika '{$application['nick']}' (ID: {$application['user_id']}) do projektu '{$application['project_name']}' (ID: {$projectId}), rola: '{$role}'";
            logAction($conn, $userId, $userEmail, "accept_application", $logDetails);

            $_SESSION['success_message'] = "Pomyślnie zaakceptowano zgłoszenie użytkownika " . htmlspecialchars($application['nick']) . ".";
            header("Location: project.php?id=" . $projectId . "&tab=requests");
            exit();

        } catch (Exception $e) {
            // Wycofaj transakcję w przypadku błędu
            $conn->rollback();

            // ✅ DODAJ REKORD BŁĘDU DO LOGS
            $errorDetails = "Błąd przy akceptowaniu zgłoszenia ID: {$requestId} do projektu ID: {$projectId}. Powód: " . $e->getMessage();
            logAction($conn, $userId, $userEmail, "accept_application_error", $errorDetails);

            throw $e;
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Błąd: " . $e->getMessage();
        header("Location: project.php?id=" . $projectId);
        exit();
    }
} else {
    // ✅ DODAJ REKORD BŁĘDU DO LOGS - nieprawidłowe żądanie
    $errorDetails = "Nieprawidłowe żądanie akceptacji zgłoszenia. Brak wymaganych parametrów.";
    logAction($conn, $userId, $userEmail, "invalid_request_error", $errorDetails);

    $_SESSION['error_message'] = "Nieprawidłowe żądanie.";
    header("Location: projects.php");
    exit();
}
?>