<?php
session_start();
include("global/connection.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    
    // Rozpocznij transakcję
    $conn->begin_transaction();
    
    try {
        // 1. Usuń powiązane dane z innych tabel (jeśli istnieją)
        
        // Usuń odznaki użytkownika
        $stmt = $conn->prepare("DELETE FROM badges WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Usuń logi użytkownika
        $stmt = $conn->prepare("DELETE FROM logs WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Usuń z project_team (jeśli tabela istnieje)
        $stmt = $conn->prepare("DELETE FROM project_team WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Usuń komentarze (jeśli tabela istnieje)
        $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // 2. Usuń główny rekord użytkownika
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Zatwierdź transakcję
            $conn->commit();
            
            // Zakończ sesję
            session_unset();
            session_destroy();
            
            // Zwróć sukces
            echo json_encode([
                'success' => true,
                'message' => 'Konto zostało pomyślnie usunięte'
            ]);
        } else {
            throw new Exception("Nie udało się usunąć konta");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Cofnij transakcję w przypadku błędu
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Nieprawidłowe żądanie'
    ]);
}

$conn->close();
?>