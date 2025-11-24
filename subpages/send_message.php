<?php
session_start();
include("global/connection.php");

header('Content-Type: application/json');

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany, aby wysyłać wiadomości']);
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Pobierz dane z JSON
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception("Nieprawidłowy format danych");
        }

        $recipientId = intval($input['recipient_id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $content = trim($input['content'] ?? '');
        $projectId = intval($input['project_id'] ?? 0);

        // Walidacja danych
        if ($recipientId <= 0) {
            throw new Exception("Nieprawidłowy odbiorca");
        }

        if (empty($title)) {
            throw new Exception("Tytuł wiadomości jest wymagany");
        }

        if (empty($content)) {
            throw new Exception("Treść wiadomości jest wymagana");
        }

        if ($projectId <= 0) {
            throw new Exception("Nieprawidłowy projekt");
        }

        // Sprawdź czy nadawca jest właścicielem projektu
        $checkOwnerStmt = $conn->prepare("SELECT founder_id FROM projects WHERE id = ?");
        $checkOwnerStmt->bind_param("i", $projectId);
        $checkOwnerStmt->execute();
        $checkOwnerStmt->bind_result($founderId);

        if (!$checkOwnerStmt->fetch()) {
            $checkOwnerStmt->close();
            throw new Exception("Projekt nie istnieje");
        }
        $checkOwnerStmt->close();

        if ($founderId != $userId) {
            throw new Exception("Tylko właściciel projektu może wysyłać wiadomości do członków");
        }

        // Sprawdź czy odbiorca jest członkiem projektu
        $checkMemberStmt = $conn->prepare("
            SELECT user_id FROM project_team 
            WHERE project_id = ? AND user_id = ?
        ");
        $checkMemberStmt->bind_param("ii", $projectId, $recipientId);
        $checkMemberStmt->execute();
        $checkMemberStmt->store_result();

        if ($checkMemberStmt->num_rows === 0) {
            $checkMemberStmt->close();
            throw new Exception("Wybrany użytkownik nie jest członkiem tego projektu");
        }
        $checkMemberStmt->close();

        // Sprawdź czy odbiorca istnieje
        $checkUserStmt = $conn->prepare("SELECT nick, email FROM users WHERE id = ?");
        $checkUserStmt->bind_param("i", $recipientId);
        $checkUserStmt->execute();
        $checkUserStmt->bind_result($recipientNick, $recipientEmail);

        if (!$checkUserStmt->fetch()) {
            $checkUserStmt->close();
            throw new Exception("Odbiorca nie istnieje");
        }
        $checkUserStmt->close();

        // Pobierz nazwę projektu
        $projectStmt = $conn->prepare("SELECT name FROM projects WHERE id = ?");
        $projectStmt->bind_param("i", $projectId);
        $projectStmt->execute();
        $projectStmt->bind_result($projectName);
        $projectStmt->fetch();
        $projectStmt->close();

        // Wstaw wiadomość do bazy danych (jeśli masz tabelę messages)
        $messageId = null;
        if (tableExists($conn, 'messages')) {
            $insertStmt = $conn->prepare("
                INSERT INTO messages (sender_id, recipient_id, title, content, project_id, sent_at, is_read)
                VALUES (?, ?, ?, ?, ?, NOW(), 0)
            ");

            if ($insertStmt && $insertStmt->bind_param("iissi", $userId, $recipientId, $title, $content, $projectId)) {
                if ($insertStmt->execute()) {
                    $messageId = $conn->insert_id;
                }
                $insertStmt->close();
            }
        }

        // TWORZENIE POWIADOMIENIA W TABELI notifications
        $notificationStmt = $conn->prepare("
            INSERT INTO notifications (user_id, project_id, title, message, type, is_read, related_url, created_at)
            VALUES (?, ?, ?, ?, ?, 0, ?, NOW())
        ");

        if ($notificationStmt === false) {
            throw new Exception("Błąd przygotowania zapytania powiadomienia: " . $conn->error);
        }

        // Przygotuj dane powiadomienia
        $notificationTitle = "Nowa wiadomość od właściciela projektu";
        $notificationMessage = "Otrzymałeś wiadomość w projekcie \"$projectName\": $title";
        $notificationType = "message";
        $relatedUrl = "project.php?id=" . $projectId . "&tab=messages";

        if (!$notificationStmt->bind_param(
            "iissss",
            $recipientId,
            $projectId,
            $notificationTitle,
            $notificationMessage,
            $notificationType,
            $relatedUrl
        )) {
            throw new Exception("Błąd powiązania parametrów powiadomienia: " . $notificationStmt->error);
        }

        if (!$notificationStmt->execute()) {
            throw new Exception("Błąd zapisu powiadomienia: " . $notificationStmt->error);
        }

        $notificationId = $conn->insert_id;
        $notificationStmt->close();

        // Logowanie akcji wysłania wiadomości
        if (function_exists('logAction')) {
            include("global/log_action.php");
            logAction(
                $conn,
                $userId,
                $userEmail,
                "message_sent",
                "To: $recipientNick, Project: $projectName, MessageID: " . ($messageId ?? 'none') . ", NotificationID: $notificationId"
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Wiadomość została wysłana pomyślnie!',
            'messageId' => $messageId,
            'notificationId' => $notificationId
        ]);
    } catch (Exception $e) {
        error_log("Błąd wysyłania wiadomości - User: $userId, Error: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Nieprawidłowa metoda żądania'
    ]);
}

// Funkcja sprawdzająca czy tabela istnieje
function tableExists($conn, $table)
{
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

