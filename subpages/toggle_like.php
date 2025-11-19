<?php
// toggle_like.php
session_start();
include("global/connection.php");

header('Content-Type: application/json');

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany!']);
    exit;
}

// Sprawdź czy przesłano project_id
$input = json_decode(file_get_contents('php://input'), true);
$project_id = $input['project_id'] ?? null;

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Brakujące ID projektu!']);
    exit;
}

$user_id = $_SESSION["user_id"];
$project_id = intval($project_id);

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
    
    // Sprawdź czy użytkownik już polubił projekt
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND project_id = ?");
    $stmt->bind_param("ii", $user_id, $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_like = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing_like) {
        // Usuń like
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND project_id = ?");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();
        
        // Pobierz aktualną liczbę like'ów
        $stmt = $conn->prepare("SELECT COUNT(*) as likeCount FROM likes WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $likeData = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'liked' => false,
            'likeCount' => $likeData['likeCount'],
            'message' => 'Usunięto like'
        ]);
    } else {
        // Dodaj like
        $stmt = $conn->prepare("INSERT INTO likes (user_id, project_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $project_id);
        $stmt->execute();
        $stmt->close();
        
        // Pobierz aktualną liczbę like'ów
        $stmt = $conn->prepare("SELECT COUNT(*) as likeCount FROM likes WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $likeData = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'liked' => true,
            'likeCount' => $likeData['likeCount'],
            'message' => 'Dodano like'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Błąd przy obsłudze like: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Wystąpił błąd serwera!']);
}
?>