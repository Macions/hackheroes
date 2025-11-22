<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");

// Sprawdź czy użytkownik jest zalogowany (opcjonalne - strona może być publiczna)
$isLoggedIn = isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;
$userId = $_SESSION["user_id"] ?? null;

try {
    // 1. STATYSTYKI SPOŁECZNOŚCI
    $stats = [];

    // Całkowita liczba użytkowników
    $totalUsersStmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
    $totalUsersStmt->execute();
    $stats['total_users'] = $totalUsersStmt->get_result()->fetch_assoc()['total'] ?? 0;
    $totalUsersStmt->close();

    // Aktywni użytkownicy (logowanie w ostatnich 30 dniach)
    $activeUsersStmt = $conn->prepare("
        SELECT COUNT(DISTINCT user_id) as active 
        FROM logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $activeUsersStmt->execute();
    $stats['active_users'] = $activeUsersStmt->get_result()->fetch_assoc()['active'] ?? 0;
    $activeUsersStmt->close();

    // Liczba projektów aktywnych
    $totalProjectsStmt = $conn->prepare("
    SELECT COUNT(*) as total_active 
    FROM projects 
    WHERE status = 'Aktywny'
");
    $totalProjectsStmt->execute();
    $stats['total_projects'] = $totalProjectsStmt->get_result()->fetch_assoc()['total_active'] ?? 0;
    $totalProjectsStmt->close();

    // Liczba projektów zakończonych
    $completedProjectsStmt = $conn->prepare("
    SELECT COUNT(*) as total_completed 
    FROM projects 
    WHERE status = 'Zakończony'
");
    $completedProjectsStmt->execute();
    $stats['completed_projects'] = $completedProjectsStmt->get_result()->fetch_assoc()['total_completed'] ?? 0;
    $completedProjectsStmt->close();

    // Unikalne szkoły
    $schoolsStmt = $conn->prepare("SELECT COUNT(DISTINCT school) as schools FROM users WHERE school IS NOT NULL AND school != ''");
    $schoolsStmt->execute();
    $stats['total_schools'] = $schoolsStmt->get_result()->fetch_assoc()['schools'] ?? 0;
    $schoolsStmt->close();

    // Liczba komentarzy
    $commentsStmt = $conn->prepare("SELECT COUNT(*) as total FROM comments");
    $commentsStmt->execute();
    $stats['total_comments'] = $commentsStmt->get_result()->fetch_assoc()['total'] ?? 0;
    $commentsStmt->close();

    // 2. NAJBARDZIEJ ZASŁUŻENI TWÓRCY (według wzoru)
    $topCreatorsStmt = $conn->prepare("
        SELECT 
            u.id,
            u.nick,
            u.avatar,
            u.interests,
            u.experience,
            COUNT(DISTINCT p_own.id) as own_projects,
            COUNT(DISTINCT pt.project_id) as member_projects,
            COUNT(DISTINCT c.id) as comments_count,
            (COUNT(DISTINCT p_own.id) * 10 + COUNT(DISTINCT pt.project_id) * 5 + COUNT(DISTINCT c.id) * 2) as merit_points
        FROM users u
        LEFT JOIN projects p_own ON u.id = p_own.founder_id AND p_own.status = 'active'
        LEFT JOIN project_team pt ON u.id = pt.user_id
        LEFT JOIN comments c ON u.id = c.user_id
        GROUP BY u.id, u.nick, u.avatar, u.interests, u.experience
        HAVING merit_points > 0
        ORDER BY merit_points DESC
        LIMIT 3
    ");
    $topCreatorsStmt->execute();
    $topCreators = $topCreatorsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $topCreatorsStmt->close();

    $newTalentsStmt = $conn->prepare("
    SELECT 
        u.id,
        u.nick,
        u.avatar,
        u.interests,
        u.goals,
        u.created_at,
        DATEDIFF(NOW(), u.created_at) as days_since_join
    FROM users u
    ORDER BY u.created_at DESC
    LIMIT 3
");
    $newTalentsStmt->execute();
    $newTalents = $newTalentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $newTalentsStmt->close();

    // Zamiana podkreślników w interests na spacje
    foreach ($newTalents as &$talent) {
        if (!empty($talent['interests'])) {
            $talent['interests'] = str_replace('_', ' ', $talent['interests']);
        }
    }


    // 4. DODATKOWE STATYSTYKI DO SEKCJI CIEKAWOSTEK
    // Nowi członkowie w ostatnim roku
    $newMembersYearStmt = $conn->prepare("
        SELECT COUNT(*) as new_members 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $newMembersYearStmt->execute();
    $newMembersYear = $newMembersYearStmt->get_result()->fetch_assoc()['new_members'] ?? 0;
    $newMembersYearStmt->close();

    // Nowe projekty w tym roku
    $newProjectsYearStmt = $conn->prepare("
        SELECT COUNT(*) as new_projects 
        FROM projects 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) AND status = 'active'
    ");
    $newProjectsYearStmt->execute();
    $newProjectsYear = $newProjectsYearStmt->get_result()->fetch_assoc()['new_projects'] ?? 0;
    $newProjectsYearStmt->close();

    // Międzynarodowe partnerstwa (projekty spoza Polski)
    $partnershipsStmt = $conn->prepare("
    SELECT COUNT(*) as international_projects 
    FROM projects 
    WHERE country IS NOT NULL AND country != 'PL'
");
    $partnershipsStmt->execute();
    $partnerships = $partnershipsStmt->get_result()->fetch_assoc()['international_projects'] ?? 0;
    $partnershipsStmt->close();


} catch (Exception $e) {
    // W przypadku błędu, ustaw puste dane
    $stats = ['total_users' => 0, 'active_users' => 0, 'total_projects' => 0, 'total_schools' => 0, 'total_comments' => 0];
    $topCreators = [];
    $newTalents = [];
    $newMembersYear = 0;
    $newProjectsYear = 0;
    $partnerships = 0;
    $error = "Błąd ładowania danych społeczności: " . $e->getMessage();
}

// Funkcje pomocnicze
function formatJoinDate($dateString)
{
    $joinDate = new DateTime($dateString);
    $now = new DateTime();
    $interval = $now->diff($joinDate);

    // Jeśli dołączył dzisiaj
    if ($interval->d == 0 && $interval->m == 0 && $interval->y == 0) {
        return "Dołączył/a dzisiaj";
    }

    if ($interval->m > 0) {
        return "Dołączył/a " . " {$interval->m} " . ($interval->m == 1 ? "miesiąc" : ($interval->m < 5 ? "miesiące" : "miesięcy")) . " temu";
    } else {
        return "Dołączył/a " . " {$interval->d} " . ($interval->d == 1 ? "dzień" : "dni") . " temu";
    }
}

function getInterestsPreview($interests)
{
    if (empty($interests))
        return [];
    $interestsArray = explode(',', $interests);
    return array_slice($interestsArray, 0, 3);
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Społeczność - TeenCollab</title>
    <meta name="description" content="Poznaj naszą niesamowitą społeczność młodych twórców i dołącz do nas!">
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/community_style.css">
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
                    <li><a href="community.php" class="active">Społeczność</a></li>
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
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Nasza Społeczność</h1>
                <p class="hero-subtitle">Poznaj młodych twórców, którzy razem zmieniają świat na lepsze! 🌟</p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo $stats['active_users']; ?>+</span>
                        <span class="stat-label">Aktywnych członków</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $stats['completed_projects']; ?>+</span>
                        <span class="stat-label">Zrealizowanych projektów</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $stats['total_schools']; ?>+</span>
                        <span class="stat-label">Miast w Polsce</span>
                    </div>
                </div>
            </div>
            <div class="hero-gradient"></div>
        </section>

        <!-- Najbardziej zasłużeni twórcy -->
        <section class="featured-creators">
            <div class="container">
                <h2 class="section-title">Najbardziej Zasłużeni Twórcy</h2>
                <p class="section-subtitle">Poznaj naszych topowych aktywistów według systemu punktacji zasług</p>

                <div class="creators-grid">
                    <?php if (!empty($topCreators)): ?>
                        <?php
                        $badges = ['🥇 #1', '🥈 #2', '🥉 #3'];
                        foreach ($topCreators as $index => $creator):
                            ?>
                            <article class="creator-card featured">
                                <div class="creator-badge"><?php echo $badges[$index] ?? '🏅'; ?></div>
                                <div class="creator-image">
                                    <img src="<?php echo htmlspecialchars($creator['avatar'] ?? '../photos/default-avatar.jpg'); ?>"
                                        alt="<?php echo htmlspecialchars($creator['nick']); ?>">
                                </div>
                                <div class="creator-content">
                                    <h3><?php echo htmlspecialchars($creator['nick']); ?></h3>
                                    <span class="creator-role">
                                        <?php
                                        $roles = [];
                                        if ($creator['own_projects'] > 0)
                                            $roles[] = "Założyciel {$creator['own_projects']} projektów";
                                        if ($creator['member_projects'] > 0)
                                            $roles[] = "Uczestnik {$creator['member_projects']} projektów";
                                        echo implode(' • ', $roles) ?: 'Aktywny członek';
                                        ?>
                                    </span>
                                    <p class="creator-achievements">
                                        <?php
                                        $achievements = [];
                                        if ($creator['own_projects'] > 0)
                                            $achievements[] = "Założył {$creator['own_projects']} projektów";
                                        if ($creator['member_projects'] > 0)
                                            $achievements[] = "Uczestniczy w {$creator['member_projects']} projektach";
                                        if ($creator['comments_count'] > 0)
                                            $achievements[] = "Dodał {$creator['comments_count']} komentarzy";
                                        echo implode(', ', $achievements) ?: 'Aktywny uczestnik społeczności';
                                        ?>
                                    </p>
                                    <div class="creator-stats">
                                        <div class="stat">
                                            <span
                                                class="number"><?php echo $creator['own_projects'] + $creator['member_projects']; ?></span>
                                            <span class="label">Projektów</span>
                                        </div>
                                        <div class="stat">
                                            <span class="number"><?php echo $creator['merit_points']; ?></span>
                                            <span class="label">Punktów</span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-creators">
                            <p>🎯 Jeszcze nikt nie zdobył punktów zasług. Bądź pierwszy!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Młode talenty -->
        <section class="young-talents">
            <div class="container">
                <h2 class="section-title">Młode Talenty</h2>
                <p class="section-subtitle">Świeża energia i nowe pomysły - przyszłość naszej społeczności</p>

                <div class="talents-grid">
                    <?php if (!empty($newTalents)): ?>
                        <?php foreach ($newTalents as $talent): ?>
                            <article class="talent-card">
                                <div class="talent-image">
                                    <img src="<?php echo htmlspecialchars($talent['avatar'] ?? '../photos/default-avatar.jpg'); ?>"
                                        alt="<?php echo htmlspecialchars($talent['nick']); ?>">
                                </div>
                                <div class="talent-content">
                                    <h3><?php echo htmlspecialchars($talent['nick']); ?></h3>
                                    <span class="talent-join-date"><?php echo formatJoinDate($talent['created_at']); ?></span>
                                    <blockquote class="talent-quote">
                                        "<?php
                                        if (!empty($talent['goals'])) {
                                            echo htmlspecialchars($talent['goals']);
                                        } else {
                                            echo "Chcę rozwijać się razem ze społecznością TeenCollab i realizować ciekawe projekty!";
                                        }
                                        ?>"
                                    </blockquote>
                                    <div class="talent-goals">
                                        <?php
                                        $interests = getInterestsPreview($talent['interests'] ?? '');
                                        if (!empty($interests)):
                                            foreach ($interests as $interest):
                                                ?>
                                                <span class="goal-tag">🎯 <?php echo htmlspecialchars(trim($interest)); ?></span>
                                                <?php
                                            endforeach;
                                        else:
                                            ?>
                                            <span class="goal-tag">🎯 Rozwój</span>
                                            <span class="goal-tag">🎯 Społeczność</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-talents">
                            <p>🌟 Bądź pierwszym nowym talentem w naszej społeczności!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Ciekawostki i statystyki -->
        <section class="community-stats">
            <div class="container">
                <h2 class="section-title">Ciekawostki & Wyróżnienia</h2>
                <p class="section-subtitle">Zobacz, co udało nam się osiągnąć razem w ostatnim czasie</p>

                <div class="stats-highlights">
                    <div class="highlight-card">
                        <div class="highlight-icon">🚀</div>
                        <h3>Rekordowy rok</h3>
                        <p class="highlight-number"><?php echo $newMembersYear; ?></p>
                        <p class="highlight-text">nowych członków dołączyło w ostatnim roku</p>
                    </div>

                    <div class="highlight-card">
                        <div class="highlight-icon">💡</div>
                        <h3>Innowacyjne pomysły</h3>
                        <p class="highlight-number"><?php echo $newProjectsYear; ?></p>
                        <p class="highlight-text">nowych projektów rozpoczętych w tym roku</p>
                    </div>

                    <div class="highlight-card">
                        <div class="highlight-icon">🌍</div>
                        <h3>Globalny zasięg</h3>
                        <p class="highlight-number"><?php echo $partnerships; ?></p>
                        <p class="highlight-text">projektów zagranicznych</p>
                    </div>
                </div>

                <div class="monthly-stats">
                    <div class="stat-item">
                        <span
                            class="stat-value"><?php echo $stats['total_users'] > 0 ? round(($stats['active_users'] / $stats['total_users']) * 100) : 0; ?>%</span>
                        <span class="stat-label monthly">Aktywnych członków</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['total_comments']; ?>+</span>
                        <span class="stat-label monthly">Komentarzy</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['total_projects']; ?>+</span>
                        <span class="stat-label monthly">Aktywnych projektów</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['total_schools']; ?>+</span>
                        <span class="stat-label monthly">Szkół partnerskich</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Chcesz być częścią naszej społeczności?</h2>
                    <p>Dołącz do tysięcy młodych ludzi, którzy razem tworzą lepszą przyszłość. Nie ma znaczenia, czy
                        dopiero zaczynasz, czy masz już doświadczenie - każdy znajdzie tu swoje miejsce!</p>
                    <div class="cta-buttons">
                        <?php if ($isLoggedIn): ?>
                            <a href="create_project.php" class="cta-button primary">Stwórz projekt</a>
                        <?php else: ?>
                            <a href="register.php" class="cta-button primary">Dołącz do nas!</a>
                        <?php endif; ?>
                        <a href="projects.php" class="cta-button secondary">Zobacz projekty</a>
                    </div>
                    <div class="cta-features">
                        <div class="feature">
                            <span class="feature-icon">🤝</span>
                            <span class="text">Wsparcie mentorów</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">💼</span>
                            <span class="text">Rozwój umiejętności</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">🌱</span>
                            <span class="text">Realny wpływ</span>
                        </div>
                        <div class="feature">
                            <span class="feature-icon">🎯</span>
                            <span class="text">Konkretne projekty</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="../photos/website-logo.jpg" alt="Logo TeenCollab">
                    <div>
                        <h3>TeenCollab</h3>
                        <p>Platforma dla młodych kreatorów przyszłości</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Burger menu
        const burgerMenu = document.getElementById('burger-menu');
        const navMenu = document.querySelector('.nav-menu');

        burgerMenu.addEventListener('click', () => {
            burgerMenu.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Animacje przy scrollowaniu
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Obserwuj elementy do animacji
        document.querySelectorAll('.creator-card, .talent-card, .highlight-card, .stat-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Dodaj opóźnienia dla lepszego efektu
        document.querySelectorAll('.creator-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });

        document.querySelectorAll('.talent-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });

        document.querySelectorAll('.highlight-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });
    </script>
</body>

</html>