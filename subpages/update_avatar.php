<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Nie jesteś zalogowany']);
    exit();
}

$userId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {

    if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Błąd przesyłania pliku']);
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

    // Ścieżka zapisu
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Konkurs/photos/avatars/';

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {

        // ✅ ZMIENIONE: Dodaj /Konkurs/ na początku ścieżki
        $avatarUrl = '/Konkurs/photos/avatars/' . $fileName;

        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $avatarUrl, $userId);

        if ($stmt->execute()) {

            $oldStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
            $oldStmt->bind_param("i", $userId);
            $oldStmt->execute();
            $oldStmt->bind_result($oldAvatar);
            $oldStmt->fetch();
            $oldStmt->close();

            if (
                $oldAvatar &&
                !str_contains($oldAvatar, 'sample_person.png') &&
                file_exists($_SERVER['DOCUMENT_ROOT'] . $oldAvatar)
            ) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $oldAvatar);
            }

            logAction($conn, $userId, $_SESSION['user_email'], 'avatar_change');

            echo json_encode([
                'success' => true,
                'avatarUrl' => $avatarUrl, // ✅ Teraz z /Konkurs/ na początku
                'message' => 'Avatar został zmieniony pomyślnie'
            ]);

        } else {
            unlink($filePath);
            echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $stmt->error]);
        }

        $stmt->close();

    } else {
        echo json_encode(['success' => false, 'message' => 'Błąd zapisywania pliku']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Brak pliku avatara']);
}

$conn->close();

function logAction($conn, $userId, $email, $action)
{
    try {
        $tableCheck = $conn->query("SELECT 1 FROM logs LIMIT 1");
        if ($tableCheck === false)
            return;
        $tableCheck->close();
    } catch (Exception $e) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, email, action, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, '', ?, ?, NOW())
    ");

    if ($stmt === false) {
        error_log("Błąd przygotowania zapytania: " . $conn->error);
        return;
    }

    $stmt->bind_param("issss", $userId, $email, $action, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}
?>