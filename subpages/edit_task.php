<?php
session_start();
include("global/connection.php");
include("global/log_action.php");

$taskId = $_GET['task_id'] ?? 0;
$currentUserId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION["user_email"] ?? '';

$taskStmt = $conn->prepare("
    SELECT t.*, 
           p.name as project_name,
           p.founder_id as project_owner_id,
           p.id as project_id
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    WHERE t.id = ?
");
$taskStmt->bind_param("i", $taskId);
$taskStmt->execute();
$task = $taskStmt->get_result()->fetch_assoc();
$taskStmt->close();

if (!$task) {
    die("Zadanie nie istnieje.");
}

if ($task['project_owner_id'] != $currentUserId) {
    die("Nie masz uprawnień do edycji tego zadania.");
}

$teamMembers = [];
$memberStmt = $conn->prepare("
    SELECT u.id, u.nick, u.avatar 
    FROM project_team pt 
    JOIN users u ON pt.user_id = u.id 
    WHERE pt.project_id = ?
");
$memberStmt->bind_param("i", $task['project_id']);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();
while ($row = $memberResult->fetch_assoc()) {
    $teamMembers[] = $row;
}
$memberStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $assignedTo = $_POST['assigned_to'] ?? null;
    $deadline = $_POST['deadline'] ?? null;
    $estimatedHours = $_POST['estimated_hours'] ?? null;
    $status = $_POST['status'];

    // Sprawdzanie zmian
    $changes = [];

    if ($name !== $task['name']) {
        $changes[] = "nazwa:{$task['name']}->$name";
    }
    if ($description !== $task['description']) {
        $changes[] = "opis";
    }
    if ($priority !== $task['priority']) {
        $changes[] = "priorytet:{$task['priority']}->$priority";
    }
    if ($assignedTo != $task['assigned_to']) {
        $oldAssignee = $task['assigned_to'] ?: 'brak';
        $newAssignee = $assignedTo ?: 'brak';
        $changes[] = "przypisanie:$oldAssignee->$newAssignee";
    }
    if ($deadline !== $task['deadline']) {
        $oldDeadline = $task['deadline'] ?: 'brak';
        $newDeadline = $deadline ?: 'brak';
        $changes[] = "deadline:$oldDeadline->$newDeadline";
    }
    if ($estimatedHours != $task['estimated_hours']) {
        $oldHours = $task['estimated_hours'] ?: 'brak';
        $newHours = $estimatedHours ?: 'brak';
        $changes[] = "godziny:$oldHours->$newHours";
    }
    if ($status !== $task['status']) {
        $changes[] = "status:{$task['status']}->$status";
    }

    $updateStmt = $conn->prepare("
        UPDATE tasks 
        SET name = ?, description = ?, priority = ?, assigned_to = ?, 
            deadline = ?, estimated_hours = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->bind_param(
        "sssssdsi",
        $name,
        $description,
        $priority,
        $assignedTo,
        $deadline,
        $estimatedHours,
        $status,
        $taskId
    );

    if ($updateStmt->execute()) {
        // Logowanie tylko jeśli były zmiany
        if (!empty($changes)) {
            $changeDetails = "ID zadania: $taskId, Zmiany: " . implode(", ", $changes);
            logAction($conn, $currentUserId, $userEmail, "task_updated", $changeDetails);
        }

        $_SESSION['success_message'] = "Zadanie zostało zaktualizowane pomyślnie!";
        header("Location: task_details.php?task_id=" . $taskId);
        exit;
    } else {
        $error = "Błąd podczas aktualizacji zadania: " . $conn->error;
        logAction($conn, $currentUserId, $userEmail, "task_update_failed", "ID zadania: $taskId, Błąd: " . $conn->error);
    }
    $updateStmt->close();
}

function formatDateForInput($dateStr)
{
    if (!$dateStr || $dateStr == '0000-00-00')
        return '';
    $date = new DateTime($dateStr);
    return $date->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj zadanie | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/edit_task_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <nav>
            <div class="nav-container">
                <div class="nav-brand">
                    <img src="../photos/website-logo.jpg" alt="Logo TeenCollab">
                    <span>TeenCollab</span>
                </div>
                <ul class="nav-menu">
                    <li><a href="project.php?id=<?php echo $task['project_id']; ?>">Powrót do projektu</a></li>
                    <li><a href="task_details.php?task_id=<?php echo $taskId; ?>">Podgląd zadania</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="edit-task-container">
        <div class="container">
            <div class="page-header">
                <h1>Edytuj zadanie</h1>
                <a href="task_details.php?task_id=<?php echo $taskId; ?>" class="btn-back">← Anuluj edycję</a>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <section class="edit-form-section">
                <form method="POST" class="edit-task-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nazwa zadania *</label>
                            <input type="text" name="name" id="name" required
                                value="<?php echo htmlspecialchars($task['name']); ?>"
                                placeholder="Wprowadź nazwę zadania">
                        </div>
                        <div class="form-group">
                            <label for="priority">Priorytet *</label>
                            <select name="priority" id="priority" required>
                                <option value="low" <?php echo $task['priority'] == 'low' ? 'selected' : ''; ?>>Niski
                                </option>
                                <option value="medium" <?php echo $task['priority'] == 'medium' ? 'selected' : ''; ?>>
                                    Średni</option>
                                <option value="high" <?php echo $task['priority'] == 'high' ? 'selected' : ''; ?>>Wysoki
                                </option>
                                <option value="critical" <?php echo $task['priority'] == 'critical' ? 'selected' : ''; ?>>
                                    Krytyczny</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status *</label>
                            <select name="status" id="status" required>
                                <option value="open" <?php echo $task['status'] == 'open' ? 'selected' : ''; ?>>Otwarte
                                </option>
                                <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>W trakcie</option>
                                <option value="done" <?php echo $task['status'] == 'done' ? 'selected' : ''; ?>>Zakończone
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="assigned_to">Przypisz do</label>
                            <select name="assigned_to" id="assigned_to">
                                <option value="">-- Nieprzypisane --</option>
                                <?php foreach ($teamMembers as $member): ?>
                                    <option value="<?php echo $member['id']; ?>" <?php echo (($task['assigned_to'] ?? '') == $member['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['nick']); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="deadline">Termin</label>
                            <input type="date" name="deadline" id="deadline"
                                value="<?php echo formatDateForInput($task['deadline']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="estimated_hours">Szacowany czas (godziny)</label>
                            <input type="number" name="estimated_hours" id="estimated_hours" step="0.5" min="0"
                                value="<?php echo $task['estimated_hours']; ?>" placeholder="np. 2.5">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Opis zadania *</label>
                        <textarea name="description" id="description" rows="6" required
                            placeholder="Opisz szczegóły zadania..."><?php echo htmlspecialchars($task['description']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_task" class="btn-primary">💾 Zapisz zmiany</button>
                        <a href="task_details.php?task_id=<?php echo $taskId; ?>" class="btn-secondary">❌ Anuluj</a>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="../photos/website-logo.jpg" alt="Logo TeenCollab">
                    <div>
                        <h3>TeenCollab</h3>
                        <p>Platforma dla kreatorów przyszłości</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab</p>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>