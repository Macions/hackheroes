<?php
// follow_project.php

session_start();
include("global/connection.php");

header('Content-Type: application/json');

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany!']);
    exit;
}

// Sprawdź czy przesłano project_id
if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Brakujące ID projektu!']);
    exit;
}

$user_id = $_SESSION["user_id"];
$project_id = intval($_POST['project_id']);

try {
    // Sprawdź czy projekt istnieje
    $stmt = $conn->prepare("SELECT id FROM projects WHERE id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projekt nie istnieje!']);
        exit;
    }
    
    // Sprawdź czy użytkownik już obserwuje projekt
    $stmt = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND project_id = ?");
    $stmt->bind_param("ii", $user_id, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_follow = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing_follow) {
        // Usuń obserwację (unfollow)
        $stmt = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND project_id = ?");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'action' => 'unfollow',
            'message' => 'Przestajesz obserwować projekt'
        ]);
    } else {
        // Dodaj obserwację (follow)
        $stmt = $conn->prepare("INSERT INTO follows (user_id, project_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'action' => 'follow',
            'message' => 'Obserwujesz projekt'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Błąd przy obsłudze obserwacji: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd serwera!']);
}
?>