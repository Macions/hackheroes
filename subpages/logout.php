<?php
session_start();
include("global/log_action.php"); // Dodaj include pliku z funkcją logowania

// Pobierz dane użytkownika PRZED zniszczeniem sesji
$userId = $_SESSION["user_id"] ?? null;
$userEmail = $_SESSION["user_email"] ?? '';

// Logowanie wylogowania
if ($userId) {
    // Potrzebujemy połączenia z bazą do logowania
    include("global/connection.php");
    logAction($conn, $userId, $userEmail, "user_logged_out", "");
}

// Zniszcz sesję
session_unset();
session_destroy();

// Przekieruj na stronę logowania
header("Location: join.php");
exit();
?>