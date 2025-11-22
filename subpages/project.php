<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");

$userId = $_SESSION["user_id"] ?? null;
$userEmail = $_SESSION["user_email"] ?? '';
$isLoggedIn = isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;

// Pobierz ID projektu z URL
$projectId = $_GET['id'] ?? null;
if (!$projectId) {
    header("Location: projects.php");
    exit();
}

// Sprawdź połączenie z bazą danych
if (!$conn) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Funkcje do formatowania dat
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

// Funkcja do zwiększania licznika wyświetleń
function incrementProjectViews($conn, $projectId)
{
    $viewKey = 'project_view_' . $projectId;
    if (!isset($_SESSION[$viewKey])) {
        $updateStmt = $conn->prepare("UPDATE projects SET views_counter = views_counter + 1, updated_at = NOW() WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("i", $projectId);
            $updateStmt->execute();
            $updateStmt->close();
            $_SESSION[$viewKey] = true;
        }
    }

    $stmt = $conn->prepare("SELECT views_counter FROM projects WHERE id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['views_counter'] ?? 0;
}

$currentViews = incrementProjectViews($conn, $projectId);

// Mapowania
$priorityMap = ['low' => 'Niski', 'medium' => 'Średni', 'high' => 'Wysoki'];
$statusMap = ['active' => 'Aktywny', 'completed' => 'Zakończony', 'paused' => 'Wstrzymany', 'draft' => 'Szkic'];
$visibilityMap = ['public' => 'Publiczny', 'private' => 'Prywatny'];

function getStatus($status, $statusMap)
{
    return $statusMap[$status] ?? 'Aktywny';
}
function getVisibility($visibility, $visibilityMap)
{
    return $visibilityMap[$visibility] ?? 'Publiczny';
}

function isDeadlineApproaching($deadline)
{
    if (!$deadline || $deadline == '0000-00-00')
        return false;

    $now = new DateTime();
    $deadlineDate = new DateTime($deadline);
    $interval = $now->diff($deadlineDate);

    // Zwróć true jeśli termin jest w ciągu 3 dni
    return $interval->days <= 3 && $interval->invert == 0;
}

// Funkcja do sprawdzania czy wymagane jest logowanie
function requireLogin($action)
{
    if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $_SESSION['login_message'] = "Musisz się zalogować, aby $action";
        header("Location: login.php");
        exit();
    }
}

try {
    // Pobierz projekt wraz z founderem
    $stmt = $conn->prepare("
        SELECT p.*, u.nick AS founder_name, u.email AS founder_email, u.avatar AS founder_avatar
        FROM projects p
        LEFT JOIN users u ON p.founder_id = u.id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$project)
        throw new Exception("Projekt nie istnieje");

    // Kategorie
    $categories = [];
    $catStmt = $conn->prepare("
        SELECT c.name 
        FROM categories c
        JOIN project_categories pc ON c.id = pc.category_id
        WHERE pc.project_id = ?
    ");
    $catStmt->bind_param("i", $projectId);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    while ($row = $catResult->fetch_assoc())
        $categories[] = $row['name'];
    $catStmt->close();

    // Cele
    $goals = [];
    $goalStmt = $conn->prepare("SELECT description, status FROM goals WHERE project_id = ?");
    $goalStmt->bind_param("i", $projectId);
    $goalStmt->execute();
    $goalResult = $goalStmt->get_result();
    while ($row = $goalResult->fetch_assoc()) {
        $goals[] = $row;
    }
    $goalStmt->close();

    // Liczba polubień
    $likeCountStmt = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE project_id = ?");
    $likeCountStmt->bind_param("i", $projectId);
    $likeCountStmt->execute();
    $likeCount = $likeCountStmt->get_result()->fetch_assoc()['like_count'] ?? 0;
    $likeCountStmt->close();

    // Sprawdź czy użytkownik polubił projekt (tylko jeśli jest zalogowany)
    $userLiked = false;
    if ($isLoggedIn) {
        $userLikeStmt = $conn->prepare("SELECT id FROM likes WHERE project_id = ? AND user_id = ?");
        $userLikeStmt->bind_param("ii", $projectId, $userId);
        $userLikeStmt->execute();
        $userLiked = $userLikeStmt->get_result()->fetch_assoc() !== null;
        $userLikeStmt->close();
    }

    // Obserwacja (tylko jeśli jest zalogowany)
    $isFollowing = false;
    if ($isLoggedIn) {
        $followStmt = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND project_id = ?");
        $followStmt->bind_param("ii", $userId, $projectId);
        $followStmt->execute();
        $isFollowing = $followStmt->get_result()->fetch_assoc() !== null;
        $followStmt->close();
    }

    // Avatar aktualnego użytkownika (tylko jeśli jest zalogowany)
    if ($isLoggedIn) {
        $userAvatarUrlStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
        $userAvatarUrlStmt->bind_param("i", $userId);
        $userAvatarUrlStmt->execute();
        $userAvatarUrl = $userAvatarUrlStmt->get_result()->fetch_assoc()['avatar'] ?? 'default.png';
        $userAvatarUrlStmt->close();
    }

    $FounderUserAvatarUrlStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $FounderUserAvatarUrlStmt->bind_param("i", $project['founder_id']); // tu ID założyciela
    $FounderUserAvatarUrlStmt->execute();
    $FounderUserAvatarUrl = $FounderUserAvatarUrlStmt->get_result()->fetch_assoc()['avatar'] ?? 'default.png';
    $FounderUserAvatarUrlStmt->close();


    // Umiejętności
    $skills = [];
    $skillStmt = $conn->prepare("
        SELECT s.name
        FROM skills s
        JOIN project_skills ps ON s.id = ps.skill_id
        WHERE ps.project_id = ?
    ");
    $skillStmt->bind_param("i", $projectId);
    $skillStmt->execute();
    $skillResult = $skillStmt->get_result();
    while ($row = $skillResult->fetch_assoc())
        $skills[] = $row['name'];
    $skillStmt->close();

    // Zadania z przypisaniami
    $tasks = [];
    $taskStmt = $conn->prepare("
    SELECT t.*, 
           u_assigned.nick as assigned_nick,
           u_assigned.avatar as assigned_avatar,
           u_created.nick as created_nick
    FROM tasks t
    LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
    LEFT JOIN users u_created ON t.created_by = u_created.id
    WHERE t.project_id = ?
    ORDER BY 
        CASE t.status 
            WHEN 'open' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'done' THEN 3
            ELSE 4
        END,
        CASE t.priority
            WHEN 'critical' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END
");
    $taskStmt->bind_param("i", $projectId);
    $taskStmt->execute();
    $taskResult = $taskStmt->get_result();
    while ($row = $taskResult->fetch_assoc()) {
        $tasks[] = $row;
    }
    $taskStmt->close();

    // Mapowania statusów
    $taskStatusMap = [
        'open' => 'Otwarte',
        'in_progress' => 'W trakcie',
        'done' => 'Zrobione',
        'cancelled' => 'Anulowane'
    ];

    // Członkowie zespołu
    $teamMembers = [];

    // Pobranie właściciela
    $ownerStmt = $conn->prepare("
    SELECT u.id, u.nick, u.email, u.avatar, pt.joined_at
    FROM project_team pt
    JOIN users u ON pt.user_id = u.id
    WHERE pt.project_id = ? AND pt.user_id = ?
");
    $ownerStmt->bind_param("ii", $projectId, $project['founder_id']);
    $ownerStmt->execute();
    $ownerResult = $ownerStmt->get_result();
    $owner = $ownerResult->fetch_assoc();
    $ownerStmt->close();

    if ($owner) {
        $owner['role'] = 'Założyciel';
        if (empty($owner['avatar']))
            $owner['avatar'] = 'default.png';
        $teamMembers[] = $owner;
    }

    // Pobranie pozostałych członków
    $teamStmt = $conn->prepare("
    SELECT u.id, u.nick, u.email, u.avatar, pt.role, pt.joined_at
    FROM project_team pt
    JOIN users u ON pt.user_id = u.id
    WHERE pt.project_id = ? AND pt.user_id != ?
");
    $teamStmt->bind_param("ii", $projectId, $project['founder_id']);
    $teamStmt->execute();
    $teamResult = $teamStmt->get_result();

    while ($row = $teamResult->fetch_assoc()) {
        if (empty($row['avatar']))
            $row['avatar'] = 'default.png';
        $teamMembers[] = $row;
    }
    $teamStmt->close();

    // Sprawdzenie roli użytkownika (tylko jeśli jest zalogowany)
    $isOwner = false;
    $isMember = false;
    if ($isLoggedIn) {
        $isOwner = ($userId == $project['founder_id']);
        foreach ($teamMembers as $member) {
            if ($member['id'] == $userId) {
                $isMember = true;
                break;
            }
        }
    }

    // Komentarze
    $comments = [];
    $commentStmt = $conn->prepare("
        SELECT c.comment, c.created_at, u.nick, u.avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.project_id = ?
        ORDER BY c.created_at ASC
    ");
    $commentStmt->bind_param("i", $projectId);
    $commentStmt->execute();
    $commentResult = $commentStmt->get_result();
    while ($row = $commentResult->fetch_assoc())
        $comments[] = $row;
    $commentStmt->close();

    // Zgłoszenia do projektu (tylko dla właściciela)
    $joinRequests = [];
    if ($isLoggedIn && $isOwner) {
        $requestSql = "
        SELECT pr.id, pr.user_id, pr.applied_at, pr.motivation, pr.desired_role, pr.availability, pr.status, u.nick, u.avatar
        FROM project_applications pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.project_id = ? AND pr.status = 'pending'
        ORDER BY pr.applied_at ASC
    ";

        $requestStmt = $conn->prepare($requestSql);
        if ($requestStmt === false) {
            die("Błąd przygotowania zapytania: " . $conn->error . "\nSQL: " . $requestSql);
        }

        $requestStmt->bind_param("i", $projectId);
        $requestStmt->execute();
        $requestResult = $requestStmt->get_result();
        while ($row = $requestResult->fetch_assoc()) {
            $joinRequests[] = $row;
        }
        $requestStmt->close();
    }

} catch (Exception $e) {
    die("Błąd: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Projekt | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/project_style.css">
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

                <button class="burger-menu" id="burger-menu" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <!-- 🧠 Hero Section -->
        <section class="project-hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-image">
                        <?php if ($project['thumbnail']): ?>
                            <img src="<?php echo htmlspecialchars($project['thumbnail']); ?>"
                                alt="<?php echo htmlspecialchars($project['name']); ?>">
                        <?php else: ?>
                            <img src="../photos/project-sample.jpg" alt="<?php echo htmlspecialchars($project['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="hero-info">
                        <div class="project-status status-<?php echo $project['status'] ?? 'active'; ?>">
                            <span class="status-dot"></span>
                            <?php echo getStatus($project['status'] ?? '', $statusMap); ?>
                        </div>
                        <h1 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h1>
                        <p class="project-tagline"><?php echo htmlspecialchars($project['short_description']); ?></p>

                        <div class="project-categories">
                            <?php foreach ($categories as $category): ?>
                                <span class="category-tag"><?php echo htmlspecialchars($category); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="hero-actions">
                            <?php if (!$isMember && !$isOwner): ?>
                                <button class="btn-primary btn-join" id="joinProjectBtn">
                                    <span>Dołącz do projektu</span>
                                </button>
                            <?php elseif ($isMember): ?>
                                <button class="btn-secondary" disabled>
                                    <span>✅ Jesteś członkiem</span>
                                </button>
                            <?php endif; ?>
                            <button class="btn-secondary <?php echo $isFollowing ? 'following' : ''; ?>" id="followBtn">
                                <span><?php echo $isFollowing ? 'Obserwujesz' : '❤️ Obserwuj'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="project-container">
            <div class="project-layout">
                <!-- Lewa kolumna - główna zawartość -->
                <div class="content-column">
                    <!-- 👤 Twórca projektu -->
                    <section class="content-section creator-section">
                        <div class="section-header">
                            <h2>Twórca projektu</h2>
                        </div>
                        <div class="creator-card">
                            <div class="creator-avatar">
                                <img src="<?php echo $userAvatarUrl; ?>"
                                    alt="<?php echo htmlspecialchars($project['founder_name']); ?>">
                            </div>
                            <div class="creator-info">
                                <h3 class="creator-name"><?php echo htmlspecialchars($project['founder_name']); ?></h3>
                                <p class="creator-role">Założyciel projektu</p>
                                <div class="creator-meta">
                                    <span class="meta-item">📅 Projekt utworzony:
                                        <?php echo formatDate($project['created_at']); ?></span>
                                    <span class="meta-item">👥 <?php echo count($teamMembers); ?> członków
                                        zespołu</span>
                                </div>
                                <a href="profil.php?id=<?php echo $project['founder_id']; ?>"
                                    class="creator-link">Zobacz profil twórcy →</a>
                            </div>
                        </div>
                    </section>

                    <!-- 📝 Pełny opis projektu -->
                    <?php if ($project['full_description']): ?>
                        <section class="content-section description-section">
                            <div class="section-header">
                                <h2>O projekcie</h2>
                            </div>
                            <div class="project-description">
                                <?php echo nl2br(htmlspecialchars($project['full_description'])); ?>
                            </div>
                        </section>
                    <?php endif; ?>


                    <!-- 💼 Quick Actions dla członków -->
                    <?php if ($isMember): ?>
                        <section class="content-section">
                            <h2>Szybkie akcje</h2>
                            <div class="quick-actions-grid">
                                <a href="member_dashboard.php?project_id=<?php echo $projectId; ?>" class="action-card">
                                    <span class="action-icon">📊</span>
                                    <span class="action-text">Moje zadania</span>
                                    <span class="action-description">Zobacz wszystkie przypisane zadania</span>
                                </a>

                                <a href="project_reports.php?project_id=<?php echo $projectId; ?>" class="action-card">
                                    <span class="action-icon">📈</span>
                                    <span class="action-text">Raporty</span>
                                    <span class="action-description">Statystyki projektu</span>
                                </a>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- 🎯 Cele projektu -->
                    <?php if (!empty($goals)): ?>
                        <section class="content-section goals-section">
                            <div class="section-header">
                                <h2>Cele projektu</h2>
                            </div>
                            <div class="goals-list">
                                <?php foreach ($goals as $goal): ?>
                                    <div class="goal-item">
                                        <div class="goal-header">
                                            <span class="goal-icon">🎯</span>
                                            <span class="goal-text"><?php echo htmlspecialchars($goal['description']); ?></span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill"
                                                style="width: <?php echo $goal['status'] == 1 ? '100%' : '0%'; ?>"></div>
                                        </div>
                                        <span class="progress-text">
                                            <?php echo $goal['status'] == 1 ? '100% ukończono' : '0% ukończono'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- 🔧 Lista zadań -->
                    <?php if (!empty($tasks)): ?>
                        <section class="content-section tasks-section">
                            <div class="section-header">
                                <h2>Zadania do wykonania</h2>
                                <div class="task-filters">
                                    <button class="filter-btn active" data-filter="all">Wszystkie</button>
                                    <button class="filter-btn" data-filter="open">Otwarte</button>
                                    <button class="filter-btn" data-filter="in-progress">W trakcie</button>
                                    <button class="filter-btn" data-filter="done">Zrobione</button>
                                    <button class="filter-btn" data-filter="my-tasks">Moje zadania</button>
                                </div>
                            </div>
                            <div class="tasks-list">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-card" data-status="<?php echo $task['status']; ?>"
                                        data-priority="<?php echo $task['priority']; ?>"
                                        data-assigned="<?php echo $task['assigned_to'] == $userId ? 'true' : 'false'; ?>">

                                        <div class="task-main">
                                            <h3 class="task-title"><?php echo htmlspecialchars($task['name']); ?></h3>
                                            <?php if ($task['description']): ?>
                                                <p class="task-description"><?php echo htmlspecialchars($task['description']); ?>
                                                </p>
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
                                                        <span class="meta-value">
                                                            <img src="<?php echo htmlspecialchars($task['assigned_avatar'] ?? 'default.png'); ?>"
                                                                alt="<?php echo htmlspecialchars($task['assigned_nick']); ?>"
                                                                class="assignee-avatar">
                                                            <?php echo htmlspecialchars($task['assigned_nick']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($task['deadline'] && $task['deadline'] != '0000-00-00'): ?>
                                                    <div class="meta-item">
                                                        <span class="meta-label">Termin:</span>
                                                        <span
                                                            class="meta-value deadline <?php echo isDeadlineApproaching($task['deadline']) ? 'approaching' : ''; ?>">
                                                            <?php echo formatDate($task['deadline']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="task-actions-member">
                                            <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                                <?php echo $priorityMap[$task['priority']] ?? 'Średni'; ?>
                                            </span>
                                            <span class="task-status status-<?php echo $task['status']; ?>">
                                                <?php echo $taskStatusMap[$task['status']] ?? $task['status']; ?>
                                            </span>

                                            <?php if ($isMember && $task['status'] !== 'done' && $task['status'] !== 'cancelled'): ?>
                                                <div class="member-actions">
                                                    <?php if (!$task['assigned_to'] || $task['assigned_to'] == $userId): ?>
                                                        <?php if ($task['assigned_to'] == $userId): ?>
                                                            <form method="POST" action="task_actions.php" class="task-action-form">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                                                <input type="hidden" name="action" value="complete_task">
                                                                <button type="submit" class="btn-success btn-small">
                                                                    <span class="btn-icon">✓</span> Wykonane
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="task_actions.php" class="task-action-form">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                                                <input type="hidden" name="action" value="release_task">
                                                                <button type="submit" class="btn-warning btn-small">
                                                                    <span class="btn-icon">✏️</span> Szczegóły
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" action="task_actions.php" class="task-action-form">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                                                <input type="hidden" name="action" value="take_task">
                                                                <button type="submit" class="btn-primary btn-small">
                                                                    <span class="btn-icon">👤</span> Weź zadanie
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($isMember): ?>
                                <div class="tasks-summary">
                                    <div class="summary-item">
                                        <span
                                            class="summary-count"><?php echo count(array_filter($tasks, fn($t) => $t['assigned_to'] == $userId && $t['status'] == 'in_progress')); ?></span>
                                        <span class="summary-label">Twoje zadania w trakcie</span>
                                    </div>
                                    <div class="summary-item">
                                        <span
                                            class="summary-count"><?php echo count(array_filter($tasks, fn($t) => $t['status'] == 'open')); ?></span>
                                        <span class="summary-label">Zadań otwartych</span>
                                    </div>
                                    <div class="summary-item">
                                        <span
                                            class="summary-count"><?php echo count(array_filter($tasks, fn($t) => $t['status'] == 'done')); ?></span>
                                        <span class="summary-label">Zadań wykonanych</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <!-- 👥 Zespół projektu -->
                    <section class="content-section team-section">
                        <div class="section-header">
                            <h2>Nasz zespół</h2>
                            <span class="section-subtitle"><?php echo count($teamMembers); ?> członków</span>
                        </div>
                        <div class="team-grid">
                            <?php foreach ($teamMembers as $member): ?>
                                <div class="team-member-card">
                                    <div class="member-avatar">
                                        <img src="<?php echo htmlspecialchars($member['avatar'] ?? 'default.png'); ?>"
                                            alt="<?php echo htmlspecialchars($member['nick']); ?>">

                                    </div>
                                    <div class="member-info">
                                        <h3 class="member-name"><?php echo htmlspecialchars($member['nick']); ?></h3>
                                        <p class="member-role"><?php echo htmlspecialchars($member['role']); ?></p>
                                        <span class="member-tenure">
                                            W zespole od
                                            <?php
                                            if (!empty($member['joined_at'])) {
                                                echo formatDate($member['joined_at']);
                                            } else {
                                                echo "Nieznana data dołączenia";
                                            }
                                            ?>
                                        </span>

                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (!$isMember && !$isOwner && $project['allow_applications']): ?>
                                <div class="team-join-card">
                                    <div class="join-icon">➕</div>
                                    <h3>Dołącz do zespołu!</h3>
                                    <p>Szukamy nowych członków</p>
                                    <button class="btn-secondary btn-apply" id="applyBtn">Aplikuj do projektu</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <?php if ($isOwner || ($isMember && isset($member['role']) && $member['role'] === 'developer')): ?>
                        <section class="content-section">
                            <div class="section-header">
                                <h2>Zgłoszenia do projektu <span
                                        class="request-count">(<?php echo count($joinRequests); ?>)</span></h2>
                            </div>

                            <?php if (count($joinRequests) === 0): ?>
                                <div class="empty-state">
                                    <p>Brak oczekujących zgłoszeń do projektu.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($joinRequests as $req): ?>
                                    <div class="request-card">
                                        <div class="request-avatar">
                                            <img src="<?php echo htmlspecialchars($req['avatar'] ?? '../photos/default-avatar.jpg'); ?>"
                                                alt="Avatar <?php echo htmlspecialchars($req['nick']); ?>">
                                        </div>

                                        <div class="request-info">
                                            <div class="request-header">
                                                <strong
                                                    class="request-username"><?php echo htmlspecialchars($req['nick']); ?></strong>
                                                <span class="request-date"><?php echo formatDateTime($req['applied_at']); ?></span>
                                            </div>

                                            <div class="request-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Motywacja:</span>
                                                    <p class="detail-value"><?php echo htmlspecialchars($req['motivation']); ?></p>
                                                </div>

                                                <div class="detail-row">
                                                    <div class="detail-item">
                                                        <span class="detail-label">Pożądana rola:</span>
                                                        <span
                                                            class="role-badge"><?php echo htmlspecialchars($req['desired_role']); ?></span>
                                                    </div>

                                                    <div class="detail-item">
                                                        <span class="detail-label">Dostępność:</span>
                                                        <span
                                                            class="availability"><?php echo htmlspecialchars($req['availability']); ?></span>
                                                    </div>
                                                </div>

                                                <div class="detail-item">
                                                    <span class="detail-label">Status:</span>
                                                    <span class="status-badge status-<?php echo $req['status']; ?>">
                                                        <?php
                                                        $statusLabels = [
                                                            'pending' => 'Oczekujące',
                                                            'accepted' => 'Zaakceptowane',
                                                            'rejected' => 'Odrzucone'
                                                        ];
                                                        echo $statusLabels[$req['status']] ?? $req['status'];
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="request-actions">
                                            <?php if ($req['status'] === 'pending'): ?>
                                                <form action="project_accept.php" method="POST" class="action-form">
                                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                                    <button type="submit" class="btn-primary" name="accept_request">
                                                        <span class="btn-icon">✓</span>
                                                        Akceptuj
                                                    </button>
                                                </form>

                                                <form action="project_decline.php" method="POST" class="action-form">
                                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                                                    <button type="submit" class="btn-danger" name="decline_request">
                                                        <span class="btn-icon">✕</span>
                                                        Odrzuć
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="request-status-info">
                                                    <span class="status-message">
                                                        Zgłoszenie zostało
                                                        <?php echo $req['status'] === 'accepted' ? 'zaakceptowane' : 'odrzucone'; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>


                    <!-- 💬 Sekcja komentarzy -->
                    <section class="content-section comments-section">
                        <div class="section-header">
                            <h2>Dyskusja</h2>
                            <div class="comments-stats">
                                <span class="stat-item">💬 <?php echo count($comments); ?> komentarzy</span>
                                <span class="stat-item">👁️ <?php echo $currentViews; ?> wyświetleń</span>
                            </div>
                        </div>

                        <?php if ($isMember || $isOwner): ?>
                            <div class="comment-form">
                                <div class="comment-avatar">
                                    <img src="<?php echo $userAvatarUrl; ?>" alt="Twój avatar">
                                </div>
                                <div class="comment-input-container">
                                    <textarea id="commentInput" class="comment-input"
                                        placeholder="Podziel się swoją opinią lub zadaj pytanie..."></textarea>
                                    <div class="comment-actions">
                                        <button id="btnAddComment" class="btn-primary btn-comment">Dodaj komentarz</button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="comment-restricted">
                                <p>💬 Dołącz do projektu, aby uczestniczyć w dyskusji</p>
                            </div>
                        <?php endif; ?>

                        <div class="comments-list">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-avatar">
                                            <img src="<?php echo $comment['avatar'] ?? 'default-avatar.jpg'; ?>"
                                                alt="<?php echo htmlspecialchars($comment['nick']); ?>">
                                        </div>
                                        <div class="comment-content">
                                            <h4><?php echo htmlspecialchars($comment['nick']); ?></h4>
                                            <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                            <span
                                                class="comment-date"><?php echo formatDateTime($comment['created_at']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-comments">
                                    <p>Brak komentarzy. Bądź pierwszy, który skomentuje!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <script>
                        document.getElementById('btnAddComment')?.addEventListener('click', function (e) {
                            e.preventDefault();
                            const comment = document.getElementById('commentInput').value.trim();
                            if (!comment) return alert('Komentarz nie może być pusty!');

                            fetch('add_comment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'project_id=<?php echo $projectId; ?>&comment=' + encodeURIComponent(comment)
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        const list = document.querySelector('.comments-list');
                                        const newComment = document.createElement('div');
                                        newComment.classList.add('comment-item');
                                        newComment.innerHTML = `
                <div class="comment-avatar">
                    <img src="<?php echo $userAvatarUrl; ?>" alt="Twój avatar">
                </div>
                <div class="comment-content">
                    <h4>Ty</h4>
                    <p>${comment.replace(/\n/g, '<br>')}</p>
                    <span class="comment-date">Właśnie teraz</span>
                </div>
            `;
                                        list.prepend(newComment);
                                        document.getElementById('commentInput').value = '';
                                        document.querySelector('.stat-item').innerText = `💬 ${list.querySelectorAll('.comment-item').length} komentarzy`;
                                    } else {
                                        alert('Błąd przy dodawaniu komentarza!');
                                    }
                                })
                                .catch(() => alert('Coś poszło nie tak...'));
                        });
                    </script>


                </div>

                <!-- Prawa kolumna - sidebar -->
                <div class="sidebar-column">
                    <!-- 🏷️ Tagi projektu -->
                    <?php if ($project['seo_tags']): ?>
                        <div class="sidebar-card tags-card">
                            <h3>Tagi projektu</h3>
                            <div class="tags-cloud">
                                <?php
                                $tags = explode(',', $project['seo_tags']);
                                foreach ($tags as $tag):
                                    $tag = trim($tag);
                                    if ($tag):
                                        ?>
                                        <span class="project-tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ❤️ Reakcje -->
                    <div class="sidebar-card reactions-card">
                        <h3>Reakcje</h3>
                        <div class="reactions-stats">
                            <div class="reaction-item">
                                <span class="reaction-icon">❤️</span>
                                <span class="reaction-count"><?php echo $likeCount; ?></span>
                            </div>
                            <div class="reaction-item">
                                <span class="reaction-icon">👁️</span>
                                <span class="reaction-count"><?php echo $currentViews; ?></span>
                            </div>
                            <div class="reaction-item">
                                <span class="reaction-icon">💬</span>
                                <span class="reaction-count">0</span>
                            </div>
                        </div>
                        <div class="reaction-actions">
                            <button class="reaction-btn like-btn">❤️ Polub</button>
                            <button class="reaction-btn share-btn">↗️ Udostępnij</button>
                        </div>
                    </div>

                    <!-- 🗂️ Informacje o projekcie -->
                    <div class="sidebar-card info-card">
                        <h3>Informacje o projekcie</h3>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value status-<?php echo $project['status'] ?? 'active'; ?>">
                                    <?php echo getStatus($project['status'] ?? '', $statusMap); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Data utworzenia:</span>
                                <span class="info-value"><?php echo formatDate($project['created_at']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Członkowie:</span>
                                <span class="info-value"><?php echo count($teamMembers); ?> osób</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Widoczność:</span>
                                <span class="info-value">
                                    <?php echo getVisibility($project['visibility'] ?? '', $visibilityMap); ?>
                                </span>
                            </div>
                            <?php if ($project['deadline'] && $project['deadline'] != '0000-00-00'): ?>
                                <div class="info-item">
                                    <span class="info-label">Termin:</span>
                                    <span class="info-value"><?php echo formatDate($project['deadline']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <span class="info-label">Ostatnia aktywność:</span>
                                <span
                                    class="info-value"><?php echo formatDateTime($project['updated_at'] ?? $project['created_at']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔒 Narzędzia (dla właściciela) -->
                    <?php if ($isOwner): ?>
                        <div class="sidebar-card tools-card">
                            <h3>Narzędzia projektu</h3>
                            <div class="tools-list">
                                <a href="edit_project.php?id=<?php echo $projectId; ?>" class="tool-btn">✏️ Edytuj
                                    projekt</a>
                                <a href="manage_team.php?project_id=<?php echo $projectId; ?>" class="tool-btn">👥 Zarządzaj
                                    zespołem</a>
                                <a href="manage_tasks.php?project_id=<?php echo $projectId; ?>" class="tool-btn">✅ Zarządzaj
                                    zadaniami</a>

                                <!-- DODAJ TEN LINK -->
                                <a href="project_reports.php?project_id=<?php echo $projectId; ?>" class="tool-btn">📊
                                    Raporty projektu</a>

                                <!-- Usuwanie projektu przez formularz POST -->
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="delete_project_id" value="<?php echo $projectId; ?>">
                                    <button type="submit" class="tool-btn danger"
                                        onclick="return confirm('Na pewno chcesz usunąć projekt?');">🗑️ Usuń
                                        projekt</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>


                    <!-- 🛠️ Wymagane umiejętności -->
                    <?php if (!empty($skills)): ?>
                        <div class="sidebar-card skills-card">
                            <h3>Wymagane umiejętności</h3>
                            <div class="skills-list">
                                <?php foreach ($skills as $skill): ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
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
                    <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal dołączania do projektu -->
    <div class="modal" id="joinModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dołącz do projektu "<?php echo htmlspecialchars($project['name']); ?>"</h3>
                <button class="modal-close" onclick="closeJoinModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Dlaczego chcesz dołączyć do projektu?</label>
                    <textarea class="modal-textarea" placeholder="Opisz swoje motywacje i doświadczenie..."></textarea>
                </div>
                <div class="form-group">
                    <label>Jaką rolę chcesz pełnić?</label>
                    <select class="modal-select">
                        <option value="">Wybierz rolę</option>
                        <option value="developer">Developer</option>
                        <option value="designer">Designer</option>
                        <option value="content">Content Specialist</option>
                        <option value="other">Inna</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Twój poziom zaangażowania</label>
                    <select class="modal-select">
                        <option value="">Wybierz dostępność</option>
                        <option value="low">Kilka godzin tygodniowo</option>
                        <option value="medium">5-10 godzin tygodniowo</option>
                        <option value="high">Ponad 10 godzin tygodniowo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn secondary" onclick="closeJoinModal()">Anuluj</button>
                <button class="modal-btn primary" onclick="submitApplication()">Wyślij zgłoszenie</button>
            </div>
        </div>
    </div>

    <script src="../scripts/project.js"></script>
    <script>
        // Przekazanie danych do JavaScript
        const projectData = {
            id: <?php echo $projectId; ?>,
            name: "<?php echo addslashes($project['name']); ?>",
            isOwner: <?php echo $isOwner ? 'true' : 'false'; ?>,
            isMember: <?php echo $isMember ? 'true' : 'false'; ?>,
            allowApplications: <?php echo $project['allow_applications'] ? 'true' : 'false'; ?>,
            autoAccept: <?php echo $project['auto_accept'] ? 'true' : 'false'; ?>
        };
    </script>
</body>

</html>