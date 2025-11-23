<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");



if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];


$projectId = $_GET['project_id'] ?? null;
if (!$projectId) {
    header("Location: projects.php");
    exit();
}


$accessStmt = $conn->prepare("
    SELECT p.id, p.name, p.founder_id 
    FROM projects p 
    LEFT JOIN project_team pt ON p.id = pt.project_id AND pt.user_id = ?
    WHERE p.id = ? AND (p.founder_id = ? OR pt.user_id IS NOT NULL)
");
$accessStmt->bind_param("iii", $userId, $projectId, $userId);
$accessStmt->execute();
$project = $accessStmt->get_result()->fetch_assoc();
$accessStmt->close();

if (!$project) {
    die("Brak dostępu do tego projektu.");
}

$isOwner = ($project['founder_id'] == $userId);


function formatDate($dateString)
{
    if (!$dateString || $dateString == '0000-00-00')
        return 'Nie ustawiono';
    return (new DateTime($dateString))->format('d.m.Y');
}

function formatDateTime($dateString)
{
    if (!$dateString || $dateString == '0000-00-00 00:00:00')
        return 'Nie ustawiono';
    return (new DateTime($dateString))->format('d.m.Y H:i');
}

function formatHours($hours)
{
    return number_format($hours, 1) . 'h';
}


$taskStats = [];
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tasks,
        SUM(estimated_hours) as total_estimated_hours,
        SUM(actual_hours) as total_actual_hours,
        AVG(estimated_hours) as avg_estimated_hours,
        AVG(actual_hours) as avg_actual_hours
    FROM tasks 
    WHERE project_id = ?
");
$statsStmt->bind_param("i", $projectId);
$statsStmt->execute();
$taskStats = $statsStmt->get_result()->fetch_assoc();
$statsStmt->close();


$priorityStats = [];
$priorityStmt = $conn->prepare("
    SELECT 
        priority,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as completed,
        AVG(estimated_hours) as avg_estimated,
        AVG(actual_hours) as avg_actual
    FROM tasks 
    WHERE project_id = ?
    GROUP BY priority
    ORDER BY 
        CASE priority
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END
");
$priorityStmt->bind_param("i", $projectId);
$priorityStmt->execute();
$priorityResult = $priorityStmt->get_result();
while ($row = $priorityResult->fetch_assoc()) {
    $priorityStats[] = $row;
}
$priorityStmt->close();


$memberStats = [];
$memberStmt = $conn->prepare("
    SELECT 
        u.id,
        u.nick,
        u.avatar,
        COUNT(t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(t.estimated_hours) as total_estimated_hours,
        SUM(t.actual_hours) as total_actual_hours,
        AVG(t.estimated_hours) as avg_estimated,
        AVG(t.actual_hours) as avg_actual
    FROM project_team pt
    JOIN users u ON pt.user_id = u.id
    LEFT JOIN tasks t ON pt.user_id = t.assigned_to AND t.project_id = ?
    WHERE pt.project_id = ?
    GROUP BY u.id, u.nick, u.avatar
    ORDER BY total_tasks DESC
");
$memberStmt->bind_param("ii", $projectId, $projectId);
$memberStmt->execute();
$memberResult = $memberStmt->get_result();
while ($row = $memberResult->fetch_assoc()) {
    $memberStats[] = $row;
}
$memberStmt->close();


$overdueTasks = [];
$overdueStmt = $conn->prepare("
    SELECT 
        t.*,
        u.nick as assigned_nick,
        u.avatar as assigned_avatar
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.project_id = ? 
    AND t.deadline < CURDATE() 
    AND t.status != 'done'
    ORDER BY t.deadline ASC
");
$overdueStmt->bind_param("i", $projectId);
$overdueStmt->execute();
$overdueResult = $overdueStmt->get_result();
while ($row = $overdueResult->fetch_assoc()) {
    $overdueTasks[] = $row;
}
$overdueStmt->close();


$recentCompleted = [];
$recentStmt = $conn->prepare("
    SELECT 
        t.*,
        u.nick as assigned_nick,
        u.avatar as assigned_avatar
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.project_id = ? 
    AND t.status = 'done'
    ORDER BY t.completed_at DESC
    LIMIT 10
");
$recentStmt->bind_param("i", $projectId);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();
while ($row = $recentResult->fetch_assoc()) {
    $recentCompleted[] = $row;
}
$recentStmt->close();


$weeklyStats = [];
$weeklyStmt = $conn->prepare("
    SELECT 
        YEARWEEK(created_at) as week,
        COUNT(*) as tasks_created,
        SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as tasks_completed
    FROM tasks 
    WHERE project_id = ?
    GROUP BY YEARWEEK(created_at)
    ORDER BY week DESC
    LIMIT 8
");
$weeklyStmt->bind_param("i", $projectId);
$weeklyStmt->execute();
$weeklyResult = $weeklyStmt->get_result();
while ($row = $weeklyResult->fetch_assoc()) {
    $weeklyStats[] = $row;
}
$weeklyStmt->close();


$completionRate = $taskStats['total_tasks'] > 0 ?
    round(($taskStats['completed_tasks'] / $taskStats['total_tasks']) * 100, 1) : 0;

$timeEfficiency = $taskStats['total_estimated_hours'] > 0 ?
    round(($taskStats['total_actual_hours'] / $taskStats['total_estimated_hours']) * 100, 1) : 0;


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
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporty projektu <?php echo htmlspecialchars($project['name']); ?> | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/project_reports_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li><a href="project.php?id=<?php echo $projectId; ?>">Powrót do projektu</a></li>
                    <li><a href="notifications.php">Powiadomienia</a></li>
                    <?php echo $nav_cta_action; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="reports-container">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1>Raporty projektu</h1>
                    <p class="project-name"><?php echo htmlspecialchars($project['name']); ?></p>
                </div>
                <div class="header-actions">
                    <a href="project.php?id=<?php echo $projectId; ?>" class="btn-back">← Powrót do projektu</a>
                    <button onclick="window.print()" class="btn-primary">🖨️ Drukuj raport</button>
                </div>
            </div>

            <!-- Karty z głównymi statystykami -->
            <section class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?php echo $taskStats['total_tasks']; ?></h3>
                        <p>Wszystkich zadań</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $taskStats['completed_tasks']; ?></h3>
                        <p>Zadań ukończonych</p>
                        <span class="stat-percent"><?php echo $completionRate; ?>%</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-info">
                        <h3><?php echo formatHours($taskStats['total_estimated_hours']); ?></h3>
                        <p>Szacowany czas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-info">
                        <h3><?php echo formatHours($taskStats['total_actual_hours']); ?></h3>
                        <p>Przepracowany czas</p>
                        <span class="stat-percent <?php echo $timeEfficiency > 100 ? 'overtime' : 'efficient'; ?>">
                            <?php echo $timeEfficiency; ?>%
                        </span>
                    </div>
                </div>
            </section>

            <div class="reports-layout">
                <!-- Lewa kolumna -->
                <div class="main-content">
                    <!-- Wykres statusów zadań -->
                    <section class="chart-section">
                        <h2>Status zadań</h2>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </section>

                    <!-- Statystyki priorytetów -->
                    <section class="stats-section">
                        <h2>Zadania według priorytetów</h2>
                        <div class="priority-stats">
                            <?php foreach ($priorityStats as $priority): ?>
                                <div class="priority-item priority-<?php echo $priority['priority']; ?>">
                                    <div class="priority-header">
                                        <span class="priority-name">
                                            <?php echo $priorityLabels[$priority['priority']] ?? $priority['priority']; ?>
                                        </span>
                                        <span class="priority-count"><?php echo $priority['count']; ?> zadań</span>
                                    </div>
                                    <div class="progress-bar">
                                        <?php
                                        $completionPercent = $priority['count'] > 0 ?
                                            round(($priority['completed'] / $priority['count']) * 100, 1) : 0;
                                        ?>
                                        <div class="progress-fill" style="width: <?php echo $completionPercent; ?>%"></div>
                                    </div>
                                    <div class="priority-details">
                                        <span>Ukończono:
                                            <?php echo $priority['completed']; ?>/<?php echo $priority['count']; ?></span>
                                        <span><?php echo $completionPercent; ?>%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Zadania z przekroczonym terminem -->
                    <?php if (!empty($overdueTasks)): ?>
                        <section class="overdue-section">
                            <h2>⚠️ Zadania po terminie</h2>
                            <div class="tasks-list">
                                <?php foreach ($overdueTasks as $task): ?>
                                    <div class="task-card overdue">
                                        <div class="task-header">
                                            <h4><?php echo htmlspecialchars($task['name']); ?></h4>
                                            <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                                <?php echo $priorityLabels[$task['priority']] ?? $task['priority']; ?>
                                            </span>
                                        </div>
                                        <div class="task-meta">
                                            <span>Przypisane do: <?php echo htmlspecialchars($task['assigned_nick']); ?></span>
                                            <span class="deadline">Termin: <?php echo formatDate($task['deadline']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <!-- Prawa kolumna -->
                <div class="sidebar">
                    <!-- Statystyki członków -->
                    <section class="members-section">
                        <h2>Aktywność członków</h2>
                        <div class="members-stats">
                            <?php foreach ($memberStats as $member): ?>
                                <div class="member-stat-card">
                                    <div class="member-header">
                                        <img src="<?php echo htmlspecialchars($member['avatar'] ?? 'default.png'); ?>"
                                            alt="<?php echo htmlspecialchars($member['nick']); ?>" class="member-avatar">
                                        <div class="member-info">
                                            <strong><?php echo htmlspecialchars($member['nick']); ?></strong>
                                            <span><?php echo $member['total_tasks']; ?> zadań</span>
                                        </div>
                                    </div>
                                    <div class="member-progress">
                                        <?php
                                        $completionRate = $member['total_tasks'] > 0 ?
                                            round(($member['completed_tasks'] / $member['total_tasks']) * 100, 1) : 0;
                                        ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $completionRate; ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $completionRate; ?>%</span>
                                    </div>
                                    <div class="member-hours">
                                        <span>⏱️ <?php echo formatHours($member['total_actual_hours']); ?></span>
                                        <span>✅
                                            <?php echo $member['completed_tasks']; ?>/<?php echo $member['total_tasks']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Ostatnio ukończone zadania -->
                    <section class="recent-section">
                        <h2>Ostatnio ukończone</h2>
                        <div class="recent-tasks">
                            <?php foreach ($recentCompleted as $task): ?>
                                <div class="recent-task">
                                    <span class="task-name"><?php echo htmlspecialchars($task['name']); ?></span>
                                    <span class="completion-date">
                                        <?php echo formatDate($task['completed_at']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
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
                    <p>©2025 TeenCollab | Raport wygenerowany: <?php echo date('d.m.Y H:i'); ?></p>
                </div>
            </div>
        </div>
    </footer>

    <script>

        const statusChart = new Chart(
            document.getElementById('statusChart'),
            {
                type: 'doughnut',
                data: {
                    labels: ['Ukończone', 'W trakcie', 'Otwarte'],
                    datasets: [{
                        data: [
                            <?php echo $taskStats['completed_tasks']; ?>,
                            <?php echo $taskStats['in_progress_tasks']; ?>,
                            <?php echo $taskStats['open_tasks']; ?>
                        ],
                        backgroundColor: [
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = <?php echo $taskStats['total_tasks']; ?>;
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            }
        );


        const weeklyData = {
            weeks: [<?php echo implode(',', array_map(function ($week) {
                return "'" . $week['week'] . "'"; }, array_reverse($weeklyStats))); ?>],
            created: [<?php echo implode(',', array_map(function ($week) {
                return $week['tasks_created']; }, array_reverse($weeklyStats))); ?>],
            completed: [<?php echo implode(',', array_map(function ($week) {
                return $week['tasks_completed']; }, array_reverse($weeklyStats))); ?>]
        };
    </script>
</body>

</html>