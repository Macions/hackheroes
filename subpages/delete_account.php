<?php
session_start();
include("global/connection.php");
include("global/log_action.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

    // 2. Logowanie faktycznego usunięcia (OSOBNY COMMIT - przed transakcją główną)
    logAction($conn, $userId, $userEmail, "account_deleted", "Usunięto konto o ID $userId");

    // Ręczny commit dla logów
    $conn->commit();

    // 3. Teraz główna transakcja usuwania
    $conn->begin_transaction();

    try {
        // Usuwanie danych powiązanych z użytkownikiem
        $tablesToDelete = ['badges', 'project_team', 'comments'];
        foreach ($tablesToDelete as $table) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }

        // Usuwanie samego użytkownika (to uruchomi CASCADE w logs)
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $conn->commit();

            session_unset();
            session_destroy();

            echo json_encode([
                'success' => true,
                'message' => 'Konto zostało pomyślnie usunięte'
            ]);
        } else {
            throw new Exception("Nie udało się usunąć konta");
        }

        $stmt->close();

    } catch (Exception $e) {
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