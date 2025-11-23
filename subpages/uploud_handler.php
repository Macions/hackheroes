<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    die("Nieautoryzowany dostęp.");
}

$currentUserId = $_SESSION["user_id"];
$taskId = $_POST['task_id'] ?? 0;


$checkStmt = $conn->prepare("SELECT assigned_to, project_id FROM tasks WHERE id = ?");
$checkStmt->bind_param("i", $taskId);
$checkStmt->execute();
$task = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$task || ($task['assigned_to'] != $currentUserId)) {
    die("Brak uprawnień do dodawania załączników.");
}


$uploadDir = "../uploads/tasks/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


if (isset($_FILES['attachments'])) {
    foreach ($_FILES['attachments']['name'] as $key => $name) {
        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['attachments']['tmp_name'][$key];
            $fileSize = $_FILES['attachments']['size'][$key];
            $fileType = $_FILES['attachments']['type'][$key];
            

            $fileExt = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $name);
            $filePath = $uploadDir . $fileName;
            

            if (move_uploaded_file($tmpName, $filePath)) {

                $insertStmt = $conn->prepare("
                    INSERT INTO task_attachments (task_id, user_id, filename, filepath, filesize, filetype) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $relativePath = "tasks/" . $fileName;
                $insertStmt->bind_param("iissis", $taskId, $currentUserId, $name, $relativePath, $fileSize, $fileType);
                $insertStmt->execute();
                $insertStmt->close();
            }
        }
    }
}

header("Location: task_details.php?task_id=$taskId");
exit;
?>