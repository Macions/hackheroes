<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");


$taskId = $_GET['task_id'] ?? 0;
$currentUserId = $_SESSION['user_id'] ?? 0;

// Pobierz zadanie
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

// Sprawdź uprawnienia
$isAssignedUser = ($task['assigned_to'] == $currentUserId);
$isProjectOwner = ($task['project_owner_id'] == $currentUserId);
$canEdit = $isAssignedUser || $isProjectOwner;

// Mapowania
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
                                            <img src="<?php echo htmlspecialchars($task['assigned_avatar'] ?? '../photos/default-avatar.jpg'); ?>"
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
                                        <img src="<?php echo htmlspecialchars($task['created_avatar'] ?? '../photos/default-avatar.jpg'); ?>"
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
                    <?php if ($canEdit): ?>
                        <section class="content-section">
                            <h2>⚡ Akcje</h2>
                            <div class="action-buttons">
                                <?php if ($task['status'] !== 'done'): ?>
                                    <form method="POST" action="task_actions.php" class="action-form">
                                        <input type="hidden" name="task_id" value="<?php echo $taskId; ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $task['project_id']; ?>">
                                        <?php if ($task['status'] === 'open'): ?>
                                            <input type="hidden" name="action" value="start_task">
                                            <button type="submit" class="btn-primary">
                                                🚀 Rozpocznij zadanie
                                            </button>
                                        <?php elseif ($task['status'] === 'in_progress'): ?>
                                            <input type="hidden" name="action" value="complete_task">
                                            <button type="submit" class="btn-success">
                                                ✅ Oznacz jako wykonane
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>

                                <!-- DODAJ TEN LINK DO EDYCJI -->
                                <?php if ($isProjectOwner): ?>
                                    <a href="edit_task.php?task_id=<?php echo $taskId; ?>" class="btn-secondary">
                                        ✏️ Edytuj zadanie
                                    </a>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

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
                        <p>Platforma dla młodych zmieniaczy świata</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab | Zadanie wyświetlone: <?php echo date('d.m.Y H:i'); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Proste potwierdzenia akcji
        document.addEventListener('DOMContentLoaded', function () {
            const forms = document.querySelectorAll('.action-form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const action = this.querySelector('button').textContent.trim();
                    if (!confirm(`Czy na pewno chcesz ${action.toLowerCase()}?`)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>

</html>