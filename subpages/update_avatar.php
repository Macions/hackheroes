<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany']);
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {

    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Błąd przesyłania pliku: ' . $_FILES['avatar']['error']]);
        exit();
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024;

    $fileType = $_FILES['avatar']['type'];
    $fileSize = $_FILES['avatar']['size'];

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Dozwolone formaty: JPG, PNG, GIF']);
        exit();
    }

    if ($fileSize > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => 'Maksymalny rozmiar pliku to 5MB']);
        exit();
    }

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Konkurs/photos/avatars/';
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $error = error_get_last();
            echo json_encode(['success' => false, 'message' => 'Nie można utworzyć katalogu: ' . $error['message']]);
            exit();
        }
    }

    $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {

        $avatarUrl = '../photos/avatars/' . $fileName;

        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $conn->error]);
            exit();
        }

        $stmt->bind_param("si", $avatarUrl, $userId);

        if ($stmt->execute()) {
            
            $checkStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $userId);
            $checkStmt->execute();
            $checkStmt->bind_result($dbAvatar);
            $checkStmt->fetch();
            $checkStmt->close();
            
            if ($dbAvatar && !str_contains($dbAvatar, 'sample_person.png')) {
                $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . '/Konkurs/' . $dbAvatar;
                if (file_exists($oldFilePath) && $oldFilePath !== $filePath) {
                    unlink($oldFilePath);
                }
            }
            

            logAction($conn, $userId, $userEmail, 'avatar_change', 'Zdjęcie profilowe zostało zmienione');
            
            echo json_encode([
                'success' => true,
                'avatarUrl' => $avatarUrl,
                'message' => 'Avatar został zmieniony pomyślnie',
                'refresh' => true
            ]);

        } else {
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $stmt->error]);
        }

        $stmt->close();

    } else {
        $error = error_get_last();
        echo json_encode(['success' => false, 'message' => 'Błąd zapisywania pliku: ' . $error['message']]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Brak pliku avatara']);
}

$conn->close();


function logAction($conn, $userId, $email, $action, $details = '') {
    try {

        $tableCheck = $conn->query("SELECT 1 FROM logs LIMIT 1");
        if ($tableCheck === false) {
            return;
        }
        $tableCheck->close();
    } catch (Exception $e) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $userAgent = strlen($agent) > 255 ? substr($agent, 0, 255) : $agent;

    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, email, action, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($stmt === false) {
        error_log("Błąd przygotowania zapytania logowania: " . $conn->error);
        return;
    }

    $stmt->bind_param("isssss", $userId, $email, $action, $details, $ip, $userAgent);
    
    if (!$stmt->execute()) {
        error_log("Błąd wykonania zapytania logowania: " . $stmt->error);
    }
    
    $stmt->close();
}
?>