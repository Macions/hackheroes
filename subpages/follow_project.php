<?php
session_start();
include("global/connection.php");
include("global/log_action.php"); // Dodaj include pliku z funkcją logowania

header('Content-Type: application/json');

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany!']);
    exit;
}

if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Brakujące ID projektu!']);
    exit;
}

$user_id = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';
$project_id = intval($_POST['project_id']);

try {
    // Pobierz nazwę projektu dla logów
    $stmt = $conn->prepare("SELECT id, name FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projekt nie istnieje!']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND project_id = ?");
    $stmt->bind_param("ii", $user_id, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_follow = $result->fetch_assoc();
    $stmt->close();

    if ($existing_follow) {
        // Usuwanie obserwacji
        $stmt = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND project_id = ?");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();

        // Logowanie usunięcia obserwacji
        logAction($conn, $user_id, $userEmail, "project_unfollowed", "ID projektu: $project_id");

        echo json_encode([
            'success' => true,
            'action' => 'unfollow',
            'message' => 'Przestajesz obserwować projekt'
        ]);
    } else {
        // Dodawanie obserwacji
        $stmt = $conn->prepare("INSERT INTO follows (user_id, project_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();

        // Logowanie dodania obserwacji
        logAction($conn, $user_id, $userEmail, "project_followed", "ID projektu: $project_id");

        echo json_encode([
            'success' => true,
            'action' => 'follow',
            'message' => 'Obserwujesz projekt'
        ]);
    }

} catch (Exception $e) {
    error_log("Błąd przy obsłudze obserwacji: " . $e->getMessage());

    // Logowanie błędu
    logAction($conn, $user_id, $userEmail, "follow_error", "ID projektu: $project_id, Błąd: " . $e->getMessage());

    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd serwera!']);
}
?>