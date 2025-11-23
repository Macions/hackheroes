<?php

include("global/connection.php");
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nieautoryzowany dostęp']);
    exit();
}

$userId = $_SESSION["user_id"];
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (!$field || !$value) {
    echo json_encode(['success' => false, 'message' => 'Brakujące dane']);
    exit();
}

$allowedFields = ['fullName', 'nick', 'email', 'phone'];
if (!in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe pole']);
    exit();
}

try {
    if ($field === 'fullName') {
        $nameParts = explode(' ', $value, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $firstName, $lastName, $userId);
    } else {
        $columnMap = [
            'nick' => 'nick',
            'email' => 'email',
            'phone' => 'phone'
        ];

        $column = $columnMap[$field];
        $stmt = $conn->prepare("UPDATE users SET $column = ? WHERE id = ?");
        $stmt->bind_param("si", $value, $userId);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd serwera']);
}