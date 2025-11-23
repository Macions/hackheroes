<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];
$debugDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = (int) ($_POST['task_id'] ?? 0);
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $debugDetails['task_id'] = $taskId;
    $debugDetails['project_id'] = $projectId;
    $debugDetails['action'] = $action;
    $debugDetails['user_id'] = $userId;

    try {
        // Pobranie zadania
        $checkStmt = $conn->prepare("SELECT assigned_to, status, name FROM tasks WHERE id = ?");
        $checkStmt->bind_param("i", $taskId);
        $checkStmt->execute();
        $task = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        $debugDetails['task_fetched'] = $task;

        if (!$task) {
            throw new Exception("Zadanie nie istnieje.");
        }

        if ($task['assigned_to'] != $userId) {
            throw new Exception("Nie jesteś przypisany do tego zadania.");
        }

        // --- Obsługa uploadu pliku ---
        if (isset($_POST['action']) && $_POST['action'] === 'upload_file') {
            if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = "Błąd przy przesyłaniu pliku!";
                header("Location: task_details.php?task_id=" . $taskId);
                exit();
            }

            $fileName = $_FILES['attachment']['name'];
            $fileTmp = $_FILES['attachment']['tmp_name'];
            $fileSize = $_FILES['attachment']['size'];
            $fileType = $_FILES['attachment']['type'];

            $uploadDir = "uploads/tasks/";
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0777, true);

            $targetPath = $uploadDir . basename($fileName); // lokalizacja na serwerze
            if (!move_uploaded_file($fileTmp, $targetPath)) {
                $_SESSION['error_message'] = "Nie udało się przenieść pliku na serwer!";
                header("Location: task_details.php?task_id=" . $taskId);
                exit();
            }

            // Ścieżka względna względem subpages/task_details.php
            $relativePath = $targetPath;

            $stmt = $conn->prepare("INSERT INTO task_attachments (task_id, user_id, filename, filepath, filesize, filetype, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt)
                die("Błąd SQL: " . $conn->error);

            $stmt->bind_param("isssis", $taskId, $userId, $fileName, $relativePath, $fileSize, $fileType);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success_message'] = "Plik został dodany!";
            header("Location: task_details.php?task_id=" . $taskId);
            exit();
        }



        // --- Notatki ---
        if ($action === 'add_notes') {
            $note = trim($_POST['notes'] ?? '');
            if ($note === '') {
                throw new Exception("Notatka nie może być pusta!");
            }

            $stmt = $conn->prepare("INSERT INTO task_notes (task_id, user_id, note, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $taskId, $userId, $note);
            $stmt->execute();
            $stmt->close();

            $_SESSION['success_message'] = "Notatka została dodana!";
            header("Location: task_details.php?task_id=" . $taskId);
            exit();
        }

        // --- Start/Complete Task ---
        switch ($action) {
            case 'start_task':
                if ($task['status'] === 'in_progress') {
                    throw new Exception("To zadanie już zostało rozpoczęte.");
                }
                $updateStmt = $conn->prepare("UPDATE tasks SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $taskId);
                break;

            case 'complete_task':
                if ($task['status'] === 'done') {
                    throw new Exception("To zadanie jest już zakończone.");
                }
                $updateStmt = $conn->prepare("UPDATE tasks SET status = 'done', completed_at = NOW(), updated_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $taskId);
                break;

            case '':
                // jeśli nic nie wybrano – nic nie rób
                throw new Exception("Nie wybrano żadnej akcji.");
                break;

            default:
                throw new Exception("Nieprawidłowa akcja.");
        }

        if (isset($updateStmt)) {
            $updateStmt->execute();
            $updateStmt->close();

            $_SESSION['success_message'] = "Akcja wykonana pomyślnie!";
            header("Location: task_details.php?task_id=" . $taskId);
            exit();
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Błąd: " . $e->getMessage();
        $_SESSION['error_debug'] = [
            'task_id' => $taskId,
            'project_id' => $projectId,
            'action' => $action,
            'user_id' => $userId,
            'task_status' => $task['status'] ?? null,
            'assigned_to' => $task['assigned_to'] ?? null,
            'debug_details' => $debugDetails
        ];
        $_SESSION['error_exception'] = $e->getMessage();

        header("Location: task_details.php?task_id=" . $taskId);
        exit();
    }
}
?>