<?php
function logAction($conn, $userId, $email, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, email, action, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) return;

    $stmt->bind_param("isssss", $userId, $email, $action, $details, $ip, $agent);
    $stmt->execute();
    $stmt->close();
}
