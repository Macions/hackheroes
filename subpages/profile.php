<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");
include("global/log_action.php"); // Dodaj include pliku z funkcją logowania

// Sprawdź czy użytkownik jest zalogowany (opcjonalnie - jeśli strona ma być dostępna tylko dla zalogowanych)
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$currentUserId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

// Pobierz ID użytkownika z URL (jeśli oglądamy czyjś profil) lub użyj aktualnego użytkownika
$profileUserId = $_GET['id'] ?? $currentUserId;
$isOwnProfile = ($profileUserId == $currentUserId);

// Logowanie wejścia na stronę profilu
logAction($conn, $currentUserId, $userEmail, "profile_page_accessed", "ID profilu: $profileUserId, Własny profil: " . ($isOwnProfile ? 'TAK' : 'NIE'));

// Pobierz dane użytkownika
$userStmt = $conn->prepare("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as project_count,
           COUNT(DISTINCT f1.id) as followers_count,
           COUNT(DISTINCT f2.id) as following_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.founder_id
    LEFT JOIN follows f1 ON u.id = f1.user_id
    LEFT JOIN follows f2 ON u.id = f2.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$userStmt->bind_param("i", $profileUserId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user) {
    // Logowanie błędu - użytkownik nie istnieje
    logAction($conn, $currentUserId, $userEmail, "profile_not_found", "ID profilu: $profileUserId");
    die("Użytkownik nie istnieje.");
}

// Pobierz projekty użytkownika
$projects = [];
$projectStmt = $conn->prepare("
    SELECT p.*, 
           COUNT(DISTINCT pt.user_id) as member_count,
           COUNT(DISTINCT l.id) as like_count
    FROM projects p
    LEFT JOIN project_team pt ON p.id = pt.project_id
    LEFT JOIN likes l ON p.id = l.project_id
    WHERE p.founder_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 6
");
$projectStmt->bind_param("i", $profileUserId);
$projectStmt->execute();
$projectResult = $projectStmt->get_result();
while ($row = $projectResult->fetch_assoc()) {
    $projects[] = $row;
}
$projectStmt->close();

$skills = [];

if ($userId) {
    $stmt = $conn->prepare("SELECT interest FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            // zakładam, że w kolumnie 'interest' masz np. wartości oddzielone przecinkami
            // jeśli tak, możemy je zamienić na tablicę
            $skills = array_map('trim', explode(',', $row['interest']));
        }
        $stmt->close();
    }
}

// $skills teraz będzie tablicą zainteresowań użytkownika


// Pobierz odznaki użytkownika
$achievements = [];
$stmt = $conn->prepare("
    SELECT * 
    FROM badges 
    WHERE user_id = ?
");
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $achievements[] = $row;
}
$stmt->close();


// Pobierz ostatnią aktywność
$activities = [];
$activityStmt = $conn->prepare("
    SELECT action, details, created_at 
    FROM logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$activityStmt->bind_param("i", $profileUserId);
$activityStmt->execute();
$activityResult = $activityStmt->get_result();
while ($row = $activityResult->fetch_assoc()) {
    $activities[] = $row;
}
$activityStmt->close();

// Sprawdź czy aktualny użytkownik obserwuje tego użytkownika
$isFollowing = false;
if (!$isOwnProfile) {
    $followStmt = $conn->prepare("
        SELECT id FROM follows 
        WHERE user_id = ? AND project_id IN (SELECT id FROM projects WHERE founder_id = ?)
    ");
    $followStmt->bind_param("ii", $currentUserId, $profileUserId);
    $followStmt->execute();
    $isFollowing = $followStmt->get_result()->num_rows > 0;
    $followStmt->close();
}

// Obsługa akcji obserwowania/odobserwowania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['follow_action'])) {
    if (!$isOwnProfile) {
        $action = $_POST['follow_action'];

        if ($action === 'follow') {
            // Znajdź projekt użytkownika do obserwowania
            $projectStmt = $conn->prepare("SELECT id FROM projects WHERE founder_id = ? LIMIT 1");
            $projectStmt->bind_param("i", $profileUserId);
            $projectStmt->execute();
            $projectResult = $projectStmt->get_result();
            $project = $projectResult->fetch_assoc();
            $projectStmt->close();

            if ($project) {
                $followStmt = $conn->prepare("INSERT INTO follows (user_id, project_id) VALUES (?, ?)");
                $followStmt->bind_param("ii", $currentUserId, $project['id']);
                $followStmt->execute();
                $followStmt->close();

                // Logowanie obserwowania użytkownika
                logAction($conn, $currentUserId, $userEmail, "user_followed", "ID obserwowanego użytkownika: $profileUserId");
                $isFollowing = true;
            }
        } elseif ($action === 'unfollow') {
            $followStmt = $conn->prepare("
                DELETE FROM follows 
                WHERE user_id = ? AND project_id IN (SELECT id FROM projects WHERE founder_id = ?)
            ");
            $followStmt->bind_param("ii", $currentUserId, $profileUserId);
            $followStmt->execute();
            $followStmt->close();

            // Logowanie odobserwowania użytkownika
            logAction($conn, $currentUserId, $userEmail, "user_unfollowed", "ID odobserwowanego użytkownika: $profileUserId");
            $isFollowing = false;
        }

        header("Location: profile.php?id=" . $profileUserId);
        exit();
    }
}

// Funkcja formatująca datę
function formatDate($dateString)
{
    if (!$dateString || $dateString == '0000-00-00')
        return '';
    $date = new DateTime($dateString);
    return $date->format('d.m.Y');
}

// Funkcja obliczająca czas od ostatniej aktywności
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => ['rok', 'lata', 'lat'],
        'm' => ['miesiąc', 'miesiące', 'miesięcy'],
        'w' => ['tydzień', 'tygodnie', 'tygodni'],
        'd' => ['dzień', 'dni', 'dni'],
        'h' => ['godzinę', 'godziny', 'godzin'],
        'i' => ['minutę', 'minuty', 'minut'],
        's' => ['sekundę', 'sekundy', 'sekund']
    ];

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v[($diff->$k == 1) ? 0 : (($diff->$k % 10 >= 2 && $diff->$k % 10 <= 4 && ($diff->$k % 100 < 10 || $diff->$k % 100 >= 20)) ? 1 : 2)];
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' temu' : 'przed chwilą';
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo htmlspecialchars($user['nick']); ?> | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/profile_style.css">
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
        <!-- 🧑‍🚀 Hero Section -->
        <section class="profile-hero">
            <div class="container">
                <div class="hero-content">
                    <div class="profile-avatar">
                        <img src="<?php echo htmlspecialchars($user['avatar'] ?? '../photos/sample_person.png'); ?>"
                            alt="<?php echo htmlspecialchars($user['nick']); ?>">
                    </div>
                    <div class="profile-info">
                        <div class="profile-badge">Aktywny członek</div>
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['nick']); ?></h1>
                        <p class="profile-role">
                            <?php echo htmlspecialchars($user['role'] ?? 'Uczestnik społeczności'); ?>
                        </p>
                        <p class="profile-bio">
                            <?php echo htmlspecialchars($user['bio'] ?? 'Uczeń zaangażowany w projekty społeczne i technologiczne. Wierzę, że razem możemy zmieniać świat na lepsze.'); ?>
                        </p>
                        <div class="profile-actions">
                            <?php if (!$isOwnProfile): ?>
                                <form method="POST" style="display: inline;">
                                    <?php if ($isFollowing): ?>
                                        <input type="hidden" name="follow_action" value="unfollow">
                                        <button type="submit" class="btn-secondary">
                                            <span>❤️ Przestań obserwować</span>
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="follow_action" value="follow">
                                        <button type="submit" class="btn-primary">
                                            <span>❤️ Obserwuj</span>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <a href="edit_profile.php" class="btn-primary">
                                    <span>✏️ Edytuj profil</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="profile-container">
            <div class="profile-layout">
                <!-- Lewa kolumna - główna zawartość -->
                <div class="content-column">
                    <!-- 📊 Podstawowe informacje -->
                    <section class="content-section basic-info-section">
                        <div class="section-header">
                            <h2>Podstawowe informacje</h2>
                        </div>
                        <div class="info-grid">
                            <?php if ($user['school']): ?>
                                <div class="info-item">
                                    <span class="info-icon">🏫</span>
                                    <div class="info-content">
                                        <span class="info-label">Szkoła</span>
                                        <span class="info-value"><?php echo htmlspecialchars($user['school']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($user['city']): ?>
                                <div class="info-item">
                                    <span class="info-icon">🏙️</span>
                                    <div class="info-content">
                                        <span class="info-label">Miasto</span>
                                        <span class="info-value"><?php echo htmlspecialchars($user['city']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="info-item">
                                <span class="info-icon">🎯</span>
                                <div class="info-content">
                                    <span class="info-label">Dziedziny zainteresowań</span>
                                    <span
                                        class="info-value"><?php echo htmlspecialchars($user['interests'] ?? 'Technologia, Społeczność, Edukacja'); ?></span>
                                </div>
                            </div>

                            <div class="info-item">
                                <span class="info-icon">📅</span>
                                <div class="info-content">
                                    <span class="info-label">Dołączył</span>
                                    <span class="info-value"><?php echo formatDate($user['created_at']); ?></span>
                                </div>
                            </div>

                            <div class="info-item">
                                <span class="info-icon">🌟</span>
                                <div class="info-content">
                                    <span class="info-label">Rola w TeenCollab</span>
                                    <span
                                        class="info-value"><?php echo htmlspecialchars($user['community_role'] ?? 'Aktywny uczestnik'); ?></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 🚀 Aktywne projekty -->
                    <section class="content-section projects-section">
                        <div class="section-header">
                            <h2>Projekty</h2>
                            <a href="projects.php?user=<?php echo $profileUserId; ?>" class="see-all-link">Zobacz
                                wszystkie →</a>
                        </div>
                        <div class="projects-grid">
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $project): ?>
                                    <div class="project-card">
                                        <div class="project-image">
                                            <img src="<?php echo htmlspecialchars($project['thumbnail'] ?? '../photos/project-default.jpg'); ?>"
                                                alt="<?php echo htmlspecialchars($project['name']); ?>">
                                            <span class="project-status status-<?php echo $project['status'] ?? 'active'; ?>">
                                                <?php echo $project['status'] == 'completed' ? 'Zakończony' : 'Aktywny'; ?>
                                            </span>
                                        </div>
                                        <div class="project-info">
                                            <h3 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h3>
                                            <p class="project-description">
                                                <?php echo htmlspecialchars($project['short_description']); ?>
                                            </p>
                                            <div class="project-meta">
                                                <span class="meta-item">👥 <?php echo $project['member_count'] ?? 0; ?>
                                                    członków</span>
                                                <span class="meta-item">❤️ <?php echo $project['like_count'] ?? 0; ?></span>
                                            </div>
                                            <a href="project.php?id=<?php echo $project['id']; ?>"
                                                class="btn-secondary btn-sm">Zobacz projekt</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-projects">
                                    <p>🎯 Ten użytkownik nie ma jeszcze żadnych projektów.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- 🧾 O użytkowniku -->
                    <section class="content-section about-section">
                        <div class="section-header">
                            <h2>O mnie</h2>
                        </div>
                        <div class="about-content">
                            <p class="about-text">
                                <?php echo nl2br(htmlspecialchars($user['about'] ?? 'Pasjonat technologii i projektów społecznych. Uwielbiam tworzyć rzeczy, które mają realny wpływ na społeczność i pomagają innym.')); ?>
                            </p>

                            <?php if (!empty($skills)): ?>
                                <div class="skills-section">
                                    <h3 class="skills-title">Umiejętności</h3>
                                    <div class="skills-grid">
                                        <?php foreach ($skills as $skill): ?>
                                            <span class="skill-badge"><?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="interests-section">
                                <h3 class="interests-title">Zainteresowania</h3>
                                <div class="interests-grid">
                                    <?php
                                    $interests = explode(',', $user['interests'] ?? 'Technologia,Ekologia,Edukacja');
                                    foreach ($interests as $interest):
                                        $interest = trim($interest);
                                        if (!empty($interest)):
                                            ?>
                                            <span class="interest-tag">🎯 <?php echo htmlspecialchars($interest); ?></span>
                                        <?php endif; endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ⭐ Osiągnięcia -->
                    <?php if (!empty($achievements)): ?>
                        <section class="content-section achievements-section">
                            <div class="section-header">
                                <h2>Osiągnięcia</h2>
                            </div>
                            <div class="achievements-grid">
                                <?php foreach ($achievements as $achievement): ?>
                                    <div class="achievement-card">
                                        <div class="achievement-icon">🏆</div>
                                        <div class="achievement-content">
                                            <h3><?php echo htmlspecialchars($achievement['title']); ?></h3>
                                            <p><?php echo htmlspecialchars($achievement['description']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- ❤️ Aktywność społecznościowa -->
                    <section class="content-section activity-section">
                        <div class="section-header">
                            <h2>Aktywność</h2>
                        </div>
                        <div class="activity-timeline">
                            <?php if (!empty($activities)): ?>
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php
                                            $icons = [
                                                'project_created' => '🚀',
                                                'comment_added' => '💬',
                                                'project_followed' => '❤️',
                                                'task_completed' => '✅',
                                                'profile_updated' => '✏️'
                                            ];
                                            echo $icons[$activity['action']] ?? '📝';
                                            ?>
                                        </div>
                                        <div class="activity-content">
                                            <p><strong><?php echo htmlspecialchars($activity['action']); ?></strong></p>
                                            <?php if ($activity['details']): ?>
                                                <p class="activity-details"><?php echo htmlspecialchars($activity['details']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <span
                                                class="activity-time"><?php echo time_elapsed_string($activity['created_at']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-activity">
                                    <p>📝 Brak ostatniej aktywności do wyświetlenia.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <!-- Prawa kolumna - sidebar -->
                <div class="sidebar-column">
                    <!-- 📎 Linki użytkownika -->
                    <div class="sidebar-card links-card">
                        <h3>Linki</h3>
                        <div class="links-list">
                            <?php if ($user['portfolio_url']): ?>
                                <a href="<?php echo htmlspecialchars($user['portfolio_url']); ?>" class="profile-link"
                                    target="_blank">
                                    <span class="link-icon">💼</span>
                                    <span class="link-text">Portfolio</span>
                                </a>
                            <?php endif; ?>

                            <?php if ($user['github_url']): ?>
                                <a href="<?php echo htmlspecialchars($user['github_url']); ?>" class="profile-link"
                                    target="_blank">
                                    <span class="link-icon">💻</span>
                                    <span class="link-text">GitHub</span>
                                </a>
                            <?php endif; ?>

                            <a href="projects.php?user=<?php echo $profileUserId; ?>" class="profile-link">
                                <span class="link-icon">🔗</span>
                                <span class="link-text">Wszystkie projekty</span>
                            </a>
                        </div>
                    </div>

                    <!-- 📊 Statystyki -->
                    <div class="sidebar-card stats-card">
                        <h3>Statystyki</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user['project_count'] ?? 0; ?></span>
                                <span class="stat-label">Projektów</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user['followers_count'] ?? 0; ?></span>
                                <span class="stat-label">Obserwujących</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user['following_count'] ?? 0; ?></span>
                                <span class="stat-label">Obserwowanych</span>
                            </div>
                        </div>
                    </div>

                    <!-- ⚠️ Sekcja niepubliczna (tylko dla właściciela) -->
                    <?php if ($isOwnProfile): ?>
                        <div class="sidebar-card private-card">
                            <h3>Twoje konto</h3>
                            <div class="private-actions">
                                <a href="edit_profile.php" class="private-btn">✏️ Edytuj profil</a>
                                <a href="settings.php" class="private-btn">⚙️ Ustawienia konta</a>
                                <a href="logout.php" class="private-btn danger">🚪 Wyloguj się</a>
                            </div>
                            <div class="private-info">
                                <p><strong>Email:</strong>
                                    <?php echo substr($userEmail, 0, 3) . '...' . substr($userEmail, strpos($userEmail, '@')); ?>
                                </p>
                                <p><strong>Ostatnie logowanie:</strong> Dzisiaj</p>
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
                        <p>Platforma dla kreatorów przyszłości</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="../scripts/profile.js"></script>
    <script>
        // Pokaż sekcję prywatną tylko dla właściciela profilu
        const isOwnProfile = <?php echo $isOwnProfile ? 'true' : 'false'; ?>;
        if (isOwnProfile) {
            document.getElementById('privateSection')?.style.display = 'block';
        }
    </script>
</body>

</html>