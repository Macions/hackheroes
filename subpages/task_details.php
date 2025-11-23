<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");

$taskId = $_GET['task_id'] ?? 0;
$currentUserId = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? '';

// Sprawdź czy użytkownik jest zalogowany
if (!$currentUserId) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['login_message'] = "Musisz się zalogować, aby zobaczyć szczegóły zadania";
    header("Location: login.php");
    exit();
}

$taskStmt = $conn->prepare("
    SELECT t.*, 
           u_assigned.nick as assigned_nick,
           u_assigned.avatar as assigned_avatar,
           u_created.nick as created_nick,
           u_created.avatar as created_avatar,
           p.name as project_name,
           p.id as project_id,
           p.founder_id as project_owner_id
    FROM tasks t
    LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
    LEFT JOIN users u_created ON t.created_by = u_created.id
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

// Sprawdź czy użytkownik jest członkiem projektu
$isMember = false;
// Sprawdź czy użytkownik jest członkiem projektu lub właścicielem
$isMemberStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM project_team 
    WHERE project_id = ? AND user_id = ?
");
$isMemberStmt->bind_param("ii", $task['project_id'], $currentUserId);
$isMemberStmt->execute();
$isMemberStmt->bind_result($memberCount);
$isMemberStmt->fetch();
$isMemberStmt->close();

// Sprawdź właściciela
$isMember = ($memberCount > 0) || ($currentUserId == $task['project_owner_id']);


$isAssignedUser = ($task['assigned_to'] == $currentUserId);
$isProjectOwner = ($task['project_owner_id'] == $currentUserId);
$canEdit = $isAssignedUser || $isProjectOwner;

// Obsługa podejmowania zadania
if ($action === 'take' && $isMember && $task['status'] === 'open' && !$task['assigned_to']) {
    // Sprawdź czy użytkownik może podjąć to zadanie
    $takeStmt = $conn->prepare("UPDATE tasks SET assigned_to = ?, status = 'in_progress', updated_at = NOW() WHERE id = ? AND status = 'open' AND assigned_to IS NULL");
    $takeStmt->bind_param("ii", $currentUserId, $taskId);

    if ($takeStmt->execute()) {
        if ($takeStmt->affected_rows > 0) {
            // Dodaj powiadomienie dla właściciela projektu
            $notificationStmt = $conn->prepare("
                INSERT INTO notifications (user_id, project_id, title, message, type, is_read, related_url, created_at) 
                VALUES (?, ?, ?, ?, 'task_taken', 0, ?, NOW())
            ");

            $userNick = $_SESSION['user_nick'] ?? 'Użytkownik';
            $title = "Zadanie zostało podjęte";
            $message = "{$userNick} podjął się zadania: {$task['name']}";
            $relatedUrl = "task_details.php?task_id={$taskId}";

            $notificationStmt->bind_param("iisss", $task['project_owner_id'], $task['project_id'], $title, $message, $relatedUrl);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Zapisz log
            $logStmt = $conn->prepare("
                INSERT INTO logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, 'take_task', ?, ?, ?, NOW())
            ");
            $details = "Użytkownik podjął się zadania #{$taskId}: '{$task['name']}' w projekcie #{$task['project_id']}";
            $logStmt->bind_param("isss", $currentUserId, $details, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            $logStmt->execute();
            $logStmt->close();

            $_SESSION['success_message'] = "Pomyślnie podjąłeś się zadania!";
        } else {
            $_SESSION['error_message'] = "Nie udało się podjąć zadania. Może zostało już przypisane?";
        }
    } else {
        $_SESSION['error_message'] = "Błąd podczas podejmowania zadania: " . $takeStmt->error;
    }

    $takeStmt->close();

    // Przekieruj z powrotem do strony zadania (bez parametru action)
    header("Location: task_details.php?task_id=" . $taskId);
    exit();
}

// Po przetworzeniu akcji, pobierz zaktualizowane dane zadania
if ($action === 'take') {
    $refreshStmt = $conn->prepare("
        SELECT t.*, 
               u_assigned.nick as assigned_nick,
               u_assigned.avatar as assigned_avatar,
               u_created.nick as created_nick,
               u_created.avatar as created_avatar
        FROM tasks t
        LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
        LEFT JOIN users u_created ON t.created_by = u_created.id
        WHERE t.id = ?
    ");
    $refreshStmt->bind_param("i", $taskId);
    $refreshStmt->execute();
    $task = $refreshStmt->get_result()->fetch_assoc();
    $refreshStmt->close();

    // Aktualizuj zmienne po zmianie
    $isAssignedUser = ($task['assigned_to'] == $currentUserId);
    $canEdit = $isAssignedUser || $isProjectOwner;
}

$priorityLabels = [
    'low' => 'Niski',
    'medium' => 'Średni',
    'high' => 'Wysoki',
    'critical' => 'Krytyczny'
];

$statusLabels = [
    'open' => 'Otwarte',
    'in_progress' => 'W trakcie',
    'done' => 'Zakończone'
];

function formatDate($dateStr)
{
    if (!$dateStr || $dateStr == '0000-00-00')
        return 'Nie ustawiono';
    $date = new DateTime($dateStr);
    return $date->format('d.m.Y');
}

function formatDateTime($dateStr)
{
    if (!$dateStr || $dateStr == '0000-00-00 00:00:00')
        return 'Nie ustawiono';
    $date = new DateTime($dateStr);
    return $date->format('d.m.Y H:i');
}

function isDeadlineApproaching($deadline)
{
    if (!$deadline || $deadline == '0000-00-00')
        return false;
    $now = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $interval = $now->diff($deadlineDate);
    return $interval->days <= 3 && $interval->invert == 0;
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($task['name']); ?> - Zadanie | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/task_details_style.css">
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
                    <li><a href="../index.php">Strona główna</a></li>
                    <li><a href="projects.php">Projekty</a></li>
                    <li><a href="community.php">Społeczność</a></li>
                    <li><a href="about.php">O projekcie</a></li>
                    <li><a href="notifications.php">Powiadomienia</a></li>
                    <?php echo $nav_cta_action; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="task-details-container">
        <div class="container">
            <div class="page-header">
                <div class="header-main">
                    <a href="project.php?id=<?php echo $task['project_id']; ?>" class="btn-back">← Powrót do
                        projektu</a>
                    <h1><?php echo htmlspecialchars($task['name']); ?></h1>
                    <p class="project-name">Projekt: <?php echo htmlspecialchars($task['project_name']); ?></p>
                </div>
                <div class="header-status">
                    <span class="task-status-badge status-<?php echo $task['status']; ?>">
                        <?php echo $statusLabels[$task['status']] ?? $task['status']; ?>
                    </span>
                    <span class="task-priority-badge priority-<?php echo $task['priority']; ?>">
                        <?php echo $priorityLabels[$task['priority']] ?? $task['priority']; ?>
                    </span>
                </div>
            </div>

            <div class="task-layout">
                <!-- Lewa kolumna - główne informacje -->
                <div class="main-content">
                    <!-- Opis zadania -->
                    <section class="content-section">
                        <h2>📝 Opis zadania</h2>
                        <div class="task-description">
                            <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                        </div>
                    </section>

                    <!-- Szczegóły zadania -->
                    <section class="content-section">
                        <h2>📋 Szczegóły</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value status-<?php echo $task['status']; ?>">
                                    <?php echo $statusLabels[$task['status']] ?? $task['status']; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Priorytet:</span>
                                <span class="detail-value priority-<?php echo $task['priority']; ?>">
                                    <?php echo $priorityLabels[$task['priority']] ?? $task['priority']; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Termin:</span>
                                <span
                                    class="detail-value deadline <?php echo isDeadlineApproaching($task['deadline']) ? 'approaching' : ''; ?>">
                                    <?php echo formatDate($task['deadline']); ?>
                                    <?php if (isDeadlineApproaching($task['deadline'])): ?>
                                        <span class="deadline-warning">⚠️ Zbliża się termin!</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Szacowany czas:</span>
                                <span class="detail-value"><?php echo $task['estimated_hours']; ?> godzin</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Przypisane do:</span>
                                <span class="detail-value">
                                    <?php if ($task['assigned_nick']): ?>
                                        <div class="user-info">
                                            <img src="<?php echo htmlspecialchars($task['assigned_avatar'] ?? '../photos/avatars/default_avatar.png'); ?>"
                                                alt="<?php echo htmlspecialchars($task['assigned_nick']); ?>"
                                                class="user-avatar">
                                            <span><?php echo htmlspecialchars($task['assigned_nick']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="not-assigned">Nieprzypisane</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Utworzył:</span>
                                <span class="detail-value">
                                    <div class="user-info">
                                        <img src="<?php echo htmlspecialchars($task['created_avatar'] ?? '../photos/avatars/default_avatar.png'); ?>"
                                            alt="<?php echo htmlspecialchars($task['created_nick']); ?>"
                                            class="user-avatar">
                                        <span><?php echo htmlspecialchars($task['created_nick']); ?></span>
                                    </div>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Data utworzenia:</span>
                                <span class="detail-value"><?php echo formatDateTime($task['created_at']); ?></span>
                            </div>
                            <?php if ($task['updated_at'] && $task['updated_at'] != $task['created_at']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Ostatnia aktualizacja:</span>
                                    <span class="detail-value"><?php echo formatDateTime($task['updated_at']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Akcje -->
                    <section class="content-section">
                        <h2>⚡ Akcje</h2>
                        <div class="action-buttons">

                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success">
                                    <?php echo $_SESSION['success_message']; ?>
                                    <?php unset($_SESSION['success_message']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-error">
                                    <?php echo $_SESSION['error_message']; ?>
                                </div>

                                <script>
                                    <?php if (isset($_SESSION['error_debug'])): ?>
                                        const debugInfo = <?php echo json_encode($_SESSION['error_debug']); ?>;
                                        alert(`Błąd: <?php echo addslashes($_SESSION['error_exception'] ?? $_SESSION['error_message']); ?>\n\nSzczegóły:\n` + JSON.stringify(debugInfo, null, 2));
                                        console.error("Szczegóły błędu:", debugInfo);
                                    <?php endif; ?>
                                </script>

                                <?php
                                unset($_SESSION['error_message']);
                                unset($_SESSION['error_debug']);
                                unset($_SESSION['error_exception']);
                                ?>
                            <?php endif; ?>


                            <?php if ($isMember): ?>

                                <?php if ($task['status'] === 'open' && !$task['assigned_to']): ?>
                                    <!-- Przycisk do podejmowania zadania -->
                                    <a href="task_details.php?task_id=<?php echo $taskId; ?>&action=take"
                                        class="btn-primary btn-large"
                                        onclick="return confirm('Czy na pewno chcesz podjąć się tego zadania?')">
                                        🚀 Weź to zadanie
                                    </a>
                                    <p class="action-description">Podjmij się tego zadania i rozpocznij pracę nad nim.</p>

                                <?php elseif ($task['status'] === 'open' && $task['assigned_to'] && !$isAssignedUser): ?>
                                    <div class="alert alert-info">
                                        ⚠️ To zadanie jest już przypisane do innej osoby.
                                    </div>

                                <?php elseif ($isAssignedUser): ?>
                                    <!-- Akcje dla przypisanego użytkownika -->
                                    <div class="assigned-badge">
                                        ✅ To zadanie jest przypisane do Ciebie
                                    </div>

                                    <!-- Wyświetl opis zadania pod akcjami -->
                                    <div class="task-action-description">
                                        <h3>Instrukcje:</h3>
                                        <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                    </div>

                                    <!-- Formularz dodawania pliku -->
                                    <form action="task_actions.php" method="POST" enctype="multipart/form-data"
                                        class="action-form">
                                        <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $task['project_id']; ?>">
                                        <input type="hidden" name="action" value="upload_file"> <!-- <--- dodaj -->
                                        <input type="file" name="attachment" required>
                                        <button type="submit" class="btn-secondary">📎 Dodaj plik</button>
                                    </form>


                                    <!-- Przyciski zmiany statusu -->
                                    <?php if ($task['status'] === 'open'): ?>
                                        <form method="POST" action="task_actions.php" class="action-form">
                                            <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                            <input type="hidden" name="project_id" value="<?php echo $task['project_id']; ?>">
                                            <input type="hidden" name="action" value="start_task">
                                            <button type="submit" class="btn-primary"
                                                onclick="return confirm('Czy na pewno chcesz rozpocząć to zadanie?')">
                                                🚀 Rozpocznij zadanie
                                            </button>
                                        </form>
                                    <?php elseif ($task['status'] === 'in_progress'): ?>
                                        <form method="POST" action="task_actions.php" class="action-form">
                                            <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                            <input type="hidden" name="project_id" value="<?php echo $task['project_id']; ?>">
                                            <input type="hidden" name="action" value="complete_task">
                                            <button type="submit" class="btn-success"
                                                onclick="return confirm('Czy na pewno chcesz oznaczyć to zadanie jako wykonane?')">
                                                ✅ Oznacz jako wykonane
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="alert alert-warning">
                                    🔒 Aby móc podejmować zadania, musisz być członkiem tego projektu.
                                </div>
                            <?php endif; ?>

                            <!-- Link do edycji dla właściciela projektu -->
                            <?php if ($isProjectOwner): ?>
                                <a href="edit_task.php?task_id=<?php echo $taskId; ?>" class="btn-secondary">
                                    ✏️ Edytuj zadanie
                                </a>
                            <?php endif; ?>

                        </div>
                    </section>

                </div>
                <!-- Notatki do zadania -->
                <?php
                // Pobierz notatki
                $notesStmt = $conn->prepare("
    SELECT tn.*, u.nick, u.avatar 
    FROM task_notes tn 
    JOIN users u ON tn.user_id = u.id 
    WHERE tn.task_id = ? 
    ORDER BY tn.created_at DESC
");
                $notesStmt->bind_param("i", $taskId);
                $notesStmt->execute();
                $notes = $notesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $notesStmt->close();
                ?>

                <?php if (!empty($notes)): ?>
                    <section class="content-section">
                        <h2>📝 Notatki</h2>
                        <div class="notes-list">
                            <?php foreach ($notes as $note): ?>
                                <div class="note-item">
                                    <div class="note-header">
                                        <div class="note-user">
                                            <img src="<?php echo htmlspecialchars($note['avatar'] ?? '../photos/avatars/default_avatar.png'); ?>"
                                                alt="<?php echo htmlspecialchars($note['nick']); ?>" class="user-avatar">
                                            <span class="user-name"><?php echo htmlspecialchars($note['nick']); ?></span>
                                        </div>
                                        <span class="note-date"><?php echo formatDateTime($note['created_at']); ?></span>
                                    </div>
                                    <div class="note-content">
                                        <?php echo nl2br(htmlspecialchars($note['note'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Załączniki do zadania -->
                <?php
                // Pobierz załączniki
                $attachmentsStmt = $conn->prepare("
    SELECT ta.*, u.nick 
    FROM task_attachments ta 
    JOIN users u ON ta.user_id = u.id 
    WHERE ta.task_id = ? 
    ORDER BY ta.uploaded_at DESC
");
                $attachmentsStmt->bind_param("i", $taskId);
                $attachmentsStmt->execute();
                $attachments = $attachmentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $attachmentsStmt->close();
                ?>

                <?php if (!empty($attachments)): ?>
                    <section class="content-section">
                        <h2>📎 Załączniki</h2>
                        <div class="attachments-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <?php
                                $fileName = $attachment['filename'] ?? 'Brak nazwy';
                                $filePath = $attachment['filepath'] ?? '#';
                                $fileSize = $attachment['filesize'] ?? 0;
                                $nick = $attachment['nick'] ?? 'Nieznany';
                                $uploadedAt = $attachment['uploaded_at'] ?? null;
                                ?>
                                <div class="attachment-item">
                                    <div class="attachment-icon">
                                        <?php
                                        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                        $icons = [
                                            'pdf' => '📕',
                                            'doc' => '📘',
                                            'docx' => '📘',
                                            'xls' => '📗',
                                            'xlsx' => '📗',
                                            'zip' => '📦',
                                            'rar' => '📦',
                                            'jpg' => '🖼️',
                                            'jpeg' => '🖼️',
                                            'png' => '🖼️',
                                            'gif' => '🖼️',
                                            'default' => '📄'
                                        ];
                                        echo $icons[strtolower($extension)] ?? $icons['default'];
                                        ?>
                                    </div>
                                    <div class="attachment-info">
                                        <div class="attachment-name"><?php echo htmlspecialchars($fileName); ?></div>
                                        <div class="attachment-meta">
                                            <span>Dodane przez: <?php echo htmlspecialchars($nick); ?></span>
                                            <span>Rozmiar: <?php echo round($fileSize / 1024, 2); ?> KB</span>
                                            <span>Data: <?php echo $uploadedAt ? formatDateTime($uploadedAt) : '-'; ?></span>
                                        </div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($filePath); ?>"
                                        download="<?php echo htmlspecialchars($fileName); ?>" class="btn-download">📥
                                        Pobierz</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>



                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error">
                        <?php echo $_SESSION['error_message']; ?>
                    </div>

                    <script>
                        // Jeśli mamy szczegóły błędu w sesji, pokaż w console.log
                        <?php if (isset($_SESSION['error_debug'])): ?>
                            console.error("Szczegóły błędu:", <?php echo json_encode($_SESSION['error_debug']); ?>);
                            console.error("Exception message:", <?php echo json_encode($_SESSION['error_exception']); ?>);
                        <?php endif; ?>
                    </script>

                    <?php
                    unset($_SESSION['error_message']);
                    unset($_SESSION['error_debug']);
                    unset($_SESSION['error_exception']);
                    ?>
                <?php endif; ?>

                <!-- Prawa kolumna - informacje dodatkowe -->
                <div class="sidebar">
                    <!-- Postęp -->
                    <section class="sidebar-section">
                        <h3>📊 Postęp</h3>
                        <div class="progress-info">
                            <div class="progress-item">
                                <span class="progress-label">Status zadania</span>
                                <div class="progress-bar">
                                    <?php
                                    $progress = 0;
                                    if ($task['status'] === 'in_progress')
                                        $progress = 50;
                                    if ($task['status'] === 'done')
                                        $progress = 100;
                                    ?>
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo $progress; ?>%</span>
                            </div>

                            <?php if ($task['status'] === 'done' && $task['completed_at']): ?>
                                <div class="completion-info">
                                    <strong>Zadanie ukończone:</strong>
                                    <span><?php echo formatDateTime($task['completed_at']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Szybkie informacje -->
                    <section class="sidebar-section">
                        <h3>ℹ️ Informacje</h3>
                        <div class="quick-info">
                            <div class="info-item">
                                <span class="info-icon">⏱️</span>
                                <div class="info-content">
                                    <strong><?php echo $task['estimated_hours']; ?>h</strong>
                                    <span>Szacowany czas</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">📅</span>
                                <div class="info-content">
                                    <strong><?php echo formatDate($task['deadline']); ?></strong>
                                    <span>Termin wykonania</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">👤</span>
                                <div class="info-content">
                                    <strong><?php echo $task['assigned_nick'] ?? 'Brak'; ?></strong>
                                    <span>Odpowiedzialna osoba</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Notatki -->
                    <?php if ($canEdit): ?>
                        <section class="sidebar-section">
                            <h3>📝 Notatki</h3>
                            <form method="POST" action="task_actions.php" class="notes-form">
                                <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                <input type="hidden" name="project_id" value="<?php echo $task['project_id']; ?>">
                                <textarea name="notes" placeholder="Dodaj notatkę do zadania..." rows="4"></textarea>
                                <button type="submit" name="add_notes" class="btn-secondary">Dodaj notatkę</button>
                            </form>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
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
                    <p>©2025 TeenCollab | Zadanie wyświetlone: <?php echo date('d.m.Y H:i'); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
            const forms = document.querySelectorAll('.action-form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const actionInput = this.querySelector('input[name="action"]');
                    const actionName = actionInput ? actionInput.value : 'akcję';
                    if (!confirm(`Czy na pewno chcesz wykonać akcję: "${actionName}"?`)) {
                        e.preventDefault();
                    }
                });
            });
        });


    </script>
</body>

</html>