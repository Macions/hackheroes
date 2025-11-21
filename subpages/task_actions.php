<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = (int) $_POST['task_id'];
    $projectId = (int) $_POST['project_id'];
    $action = $_POST['action'];

    try {
        // Sprawdź uprawnienia
        $checkStmt = $conn->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
        $checkStmt->bind_param("i", $taskId);
        $checkStmt->execute();
        $task = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$task) {
            throw new Exception("Zadanie nie istnieje.");
        }

        if ($task['assigned_to'] != $userId) {
            throw new Exception("Nie jesteś przypisany do tego zadania.");
        }

        // Wykonaj akcję
        switch ($action) {
            case 'start_task':
                $updateStmt = $conn->prepare("UPDATE tasks SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $taskId);
                break;

            case 'complete_task':
                $updateStmt = $conn->prepare("UPDATE tasks SET status = 'done', completed_at = NOW(), updated_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $taskId);
                break;

            default:
                throw new Exception("Nieprawidłowa akcja.");
        }

        $updateStmt->execute();
        $updateStmt->close();

        $_SESSION['success_message'] = "Akcja wykonana pomyślnie!";
        header("Location: task_details.php?task_id=" . $taskId);
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = "Błąd: " . $e->getMessage();
        header("Location: task_details.php?task_id=" . $taskId);
        exit();
    }
}
?>