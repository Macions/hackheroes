<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");


$projectId = $_GET['project_id'] ?? 0;
$currentUserId = $_SESSION['user_id'] ?? 0;

// Sprawdzenie czy user jest ownerem
$stmt = $conn->prepare("SELECT founder_id FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$stmt->bind_result($projectOwner);
$stmt->fetch();
$stmt->close();

if ($currentUserId != $projectOwner) {
    die("Nie masz dostępu do zarządzania zadaniami w tym projekcie.");
}

// Dodawanie zadania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $assignedTo = $_POST['assigned_to'] ?? null;
    $deadline = $_POST['deadline'] ?? null;
    $estimatedHours = $_POST['estimated_hours'] ?? null;

    $stmt = $conn->prepare("INSERT INTO tasks (project_id, name, description, priority, assigned_to, deadline, estimated_hours, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')");
    $stmt->bind_param("isssisdi", $projectId, $name, $description, $priority, $assignedTo, $deadline, $estimatedHours, $currentUserId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_tasks.php?project_id=$projectId");
    exit;
}

// Przypisywanie zadania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $taskId = $_POST['task_id'];
    $assignedTo = $_POST['assigned_to'];

    $stmt = $conn->prepare("UPDATE tasks SET assigned_to = ?, status = 'in_progress', updated_at = NOW() WHERE id = ? AND project_id = ?");
    $stmt->bind_param("iii", $assignedTo, $taskId, $projectId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_tasks.php?project_id=$projectId");
    exit;
}

// Usuwanie zadania
if (isset($_GET['delete_task'])) {
    $taskId = (int) $_GET['delete_task'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND project_id = ?");
    $stmt->bind_param("ii", $taskId, $projectId);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_tasks.php?project_id=$projectId");
    exit;
}

// Pobieranie zadań
$tasks = [];
$stmt = $conn->prepare("
    SELECT t.*, 
           u_assigned.nick as assigned_nick,
           u_assigned.avatar as assigned_avatar,
           u_created.nick as created_nick
    FROM tasks t
    LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
    LEFT JOIN users u_created ON t.created_by = u_created.id
    WHERE t.project_id = ? 
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();

function formatDate($dateStr)
{
    if (!$dateStr || $dateStr == '0000-00-00')
        return '';
    $date = new DateTime($dateStr);
    return $date->format('d.m.Y');
}

// Pobieranie członków zespołu do przypisywania
$teamMembers = [];
$memberStmt = $conn->prepare("
    SELECT u.id, u.nick, u.avatar 
    FROM project_team pt 
    JOIN users u ON pt.user_id = u.id 
    WHERE pt.project_id = ?
");
$memberStmt->bind_param("i", $projectId);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();

// W assign_task dodaj:
function sendTaskAssignmentNotification($conn, $userId, $taskId, $taskName)
{
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_url) 
        VALUES (?, 'Nowe zadanie', ?, 'info', ?)
    ");
    $message = "Zostałeś przypisany do zadania: " . $taskName;
    $url = "task_details.php?task_id=" . $taskId;
    $stmt->bind_param("iss", $userId, $message, $url);
    $stmt->execute();
    $stmt->close();
}

// Sprawdź zbliżające się deadline'y
$upcomingDeadlines = [];
$deadlineStmt = $conn->prepare("
    SELECT t.name, t.deadline, t.priority 
    FROM tasks t 
    WHERE t.project_id = ? 
    AND t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    AND t.status != 'done'
    ORDER BY t.deadline ASC
");
$deadlineStmt->bind_param("i", $projectId);
$deadlineStmt->execute();
$deadlineResult = $deadlineStmt->get_result();
while ($row = $deadlineResult->fetch_assoc()) {
    $upcomingDeadlines[] = $row;
}
$deadlineStmt->close();

while ($row = $memberResult->fetch_assoc()) {
    $teamMembers[] = $row;
}
$memberStmt->close();
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie zadaniami | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/manage_tasks_style.css">
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

    <main class="manage-tasks-container">
        <div class="container">
            <div class="page-header">
                <h1>Zarządzanie zadaniami</h1>
                <a href="project.php?id=<?php echo $projectId; ?>" class="btn-back">← Powrót do projektu</a>
            </div>

            <!-- Sekcja dodawania nowego zadania -->
            <section class="add-task-section">
                <h2>Dodaj nowe zadanie</h2>
                <form method="POST" class="add-task-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nazwa zadania:</label>
                            <input type="text" name="name" id="name" required placeholder="Wprowadź nazwę zadania">
                        </div>
                        <div class="form-group">
                            <label for="priority">Priorytet:</label>
                            <select name="priority" id="priority" required>
                                <option value="" disabled selected>Wybierz priorytet</option>
                                <option value="low">Niski</option>
                                <option value="medium">Średni</option>
                                <option value="high">Wysoki</option>
                                <option value="critical">Krytyczny</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="assigned_to">Przypisz do:</label>
                            <select name="assigned_to" id="assigned_to" required>
                                <option value="" disabled selected>-- Wybierz członka zespołu --</option>
                                <?php foreach ($teamMembers as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['nick']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="deadline">Termin:</label>
                            <input type="date" name="deadline" id="deadline" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Opis zadania:</label>
                        <textarea name="description" id="description" rows="4" required
                            placeholder="Opisz szczegóły zadania..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="estimated_hours">Szacowany czas (godziny):</label>
                        <input type="number" name="estimated_hours" id="estimated_hours" step="0.5" min="0" required
                            placeholder="np. 2.5">
                    </div>

                    <button type="submit" name="add_task" class="btn-primary">Dodaj zadanie</button>
                </form>
            </section>


            <!-- Lista zadań z opcjami przypisywania -->
            <section class="tasks-section">
                <h2>Zadania projektu (<?php echo count($tasks); ?>)</h2>

                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <p>Brak zadań w projekcie. Dodaj pierwsze zadanie!</p>
                    </div>
                <?php else: ?>
                    <div class="tasks-grid">
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-card" data-task-id="<?php echo $task['id']; ?>">
                                <!-- W pętli zadań ZMIEŃ: -->
                                <div class="task-header">
                                    <h3 class="task-name">
                                        <a href="task_details.php?task_id=<?php echo $task['id']; ?>" class="task-link">
                                            <?php echo htmlspecialchars($task['name']); ?>
                                        </a>
                                    </h3>
                                    <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                        <?php
                                        $priorityLabels = [
                                            'low' => 'Niski',
                                            'medium' => 'Średni',
                                            'high' => 'Wysoki',
                                            'critical' => 'Krytyczny'
                                        ];
                                        echo $priorityLabels[$task['priority']] ?? $task['priority'];
                                        ?>
                                    </span>
                                </div>

                                <div class="task-body">
                                    <?php if (!empty($task['description'])): ?>
                                        <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <?php endif; ?>

                                    <div class="task-meta-extended">
                                        <div class="meta-item">
                                            <span class="meta-label">Utworzył:</span>
                                            <span
                                                class="meta-value"><?php echo htmlspecialchars($task['created_nick']); ?></span>
                                        </div>

                                        <?php if ($task['assigned_nick']): ?>
                                            <div class="meta-item">
                                                <span class="meta-label">Przypisane do:</span>
                                                <span class="meta-value assigned-user">
                                                    <img src="<?php echo htmlspecialchars($task['assigned_avatar'] ?? 'default.png'); ?>"
                                                        alt="<?php echo htmlspecialchars($task['assigned_nick']); ?>"
                                                        class="assignee-avatar">
                                                    <?php echo htmlspecialchars($task['assigned_nick']); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="meta-item">
                                                <span class="meta-label">Status:</span>
                                                <span class="meta-value status-open">Nieprzypisane</span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($task['deadline'] && $task['deadline'] != '0000-00-00'): ?>
                                            <div class="meta-item">
                                                <span class="meta-label">Termin:</span>
                                                <span
                                                    class="meta-value deadline"><?php echo formatDate($task['deadline']); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($task['estimated_hours']): ?>
                                            <div class="meta-item">
                                                <span class="meta-label">Szacowany czas:</span>
                                                <span class="meta-value"><?php echo $task['estimated_hours']; ?>h</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="task-actions-admin">
                                    <!-- Formularz przypisywania zadania -->
                                    <form method="POST" class="assign-form">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <div class="assign-controls">
                                            <select name="assigned_to" class="assign-select">
                                                <option value="">-- Przypisz --</option>
                                                <?php foreach ($teamMembers as $member): ?>
                                                    <option value="<?php echo $member['id']; ?>" <?php echo $task['assigned_to'] == $member['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($member['nick']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_task" class="btn-assign">Przypisz</button>
                                        </div>
                                    </form>

                                    <a href="manage_tasks.php?project_id=<?php echo $projectId; ?>&delete_task=<?php echo $task['id']; ?>"
                                        class="btn-remove" onclick="return confirm('Czy na pewno chcesz usunąć to zadanie?')">
                                        🗑️ Usuń
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                        <p>Platforma dla młodych zmieniaczy świata</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="../scripts/manage_tasks.js"></script>
</body>

</html>