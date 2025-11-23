<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");


if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {

}

$userId = $_SESSION["user_id"] ?? null;
$userEmail = $_SESSION["user_email"] ?? '';


try {

    $projects = [];
    $projectStmt = $conn->prepare("
        SELECT 
            p.*, 
            u.nick AS founder_name, 
            u.avatar AS founder_avatar,
            COUNT(DISTINCT pt.user_id) as member_count,
            COUNT(DISTINCT l.id) as like_count,
            COUNT(DISTINCT f.id) as follows_count
        FROM projects p
        LEFT JOIN users u ON p.founder_id = u.id
        LEFT JOIN project_team pt ON p.id = pt.project_id
        LEFT JOIN likes l ON p.id = l.project_id
        LEFT JOIN follows f ON p.id = f.project_id
        WHERE p.status = 'active' OR p.status = 'Aktywny' AND p.visibility = 'public'
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");

    if ($projectStmt) {
        $projectStmt->execute();
        $projectResult = $projectStmt->get_result();
        while ($row = $projectResult->fetch_assoc()) {
            $projects[] = $row;
        }
        $projectStmt->close();
    }


    foreach ($projects as &$project) {
        $categories = [];
        $catStmt = $conn->prepare("
            SELECT c.name 
            FROM categories c
            JOIN project_categories pc ON c.id = pc.category_id
            WHERE pc.project_id = ?
        ");
        if ($catStmt) {
            $catStmt->bind_param("i", $project['id']);
            $catStmt->execute();
            $catResult = $catStmt->get_result();
            while ($row = $catResult->fetch_assoc()) {
                $categories[] = $row['name'];
            }
            $catStmt->close();
        }
        $project['categories'] = $categories;


        if (empty($project['location'])) {
            $project['location'] = 'Online';
        }
    }


    $totalProjects = count($projects);


    $usersStmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as total_users FROM project_team");
    $usersStmt->execute();
    $totalUsers = $usersStmt->get_result()->fetch_assoc()['total_users'] ?? 0;
    $usersStmt->close();


    $citiesStmt = $conn->prepare("SELECT COUNT(DISTINCT location) as total_cities FROM projects WHERE location IS NOT NULL AND location != ''");
    if ($citiesStmt) {
        $citiesStmt->execute();
        $citiesResult = $citiesStmt->get_result();
        $totalCities = $citiesResult->fetch_assoc()['total_cities'] ?? 0;
        $citiesStmt->close();
    } else {
        $totalCities = 0;
    }

} catch (Exception $e) {

    $projects = [];
    $totalProjects = 0;
    $totalUsers = 0;
    $totalCities = 0;
    $error = "Błąd ładowania projektów: " . $e->getMessage();
}


$categoryMap = [
    'Ekologia' => 'ekologia',
    'Zdrowie' => 'zdrowie',
    'Społeczne' => 'społeczne',
    'Technologia' => 'technologia',
    'Edukacja' => 'edukacja',
    'Sztuka' => 'sztuka',
    'Biznes' => 'biznes',
    'Media' => 'media'
];

function getCategoryClass($categories, $categoryMap)
{
    foreach ($categories as $category) {
        if (isset($categoryMap[$category])) {
            return $categoryMap[$category];
        }
    }
    return 'inne';
}

function formatDate($dateString)
{
    if (!$dateString || $dateString == '0000-00-00') {
        return 'Brak daty';
    }
    return (new DateTime($dateString))->format('d.m.Y');
}

function truncateText($text, $length = 150)
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projekty - TeenCollab</title>
    <meta name="description"
        content="Przeglądaj wszystkie projekty zrealizowane przez młodych kreatorów na platformie TeenCollab.">
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/projects_style.css">
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
                    <li><a href="projects.php" class="active">Projekty</a></li>
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
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Projekty TeenCollab</h1>
                <p class="hero-subtitle">Odkryj inspirujące inicjatywy młodych kreatorów z całej Polski. 🌱💡</p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo $totalProjects; ?>+</span>
                        <span class="stat-label">Projektów</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $totalUsers; ?>+</span>
                        <span class="stat-label">Uczestników</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $totalCities; ?>+</span>
                        <span class="stat-label">Miast</span>
                    </div>
                </div>
            </div>
            <div class="hero-gradient"></div>
        </section>

        <section class="filters-section">
            <div class="container">
                <div class="filters-wrapper">
                    <button class="filter-btn active" data-category="all">
                        <span>Wszystkie</span>
                    </button>
                    <button class="filter-btn" data-category="ekologia">
                        <span>Ekologia</span>
                    </button>
                    <button class="filter-btn" data-category="zdrowie">
                        <span>Zdrowie</span>
                    </button>
                    <button class="filter-btn" data-category="społeczne">
                        <span>Społeczne</span>
                    </button>
                    <button class="filter-btn" data-category="technologia">
                        <span>Technologia</span>
                    </button>
                    <button class="filter-btn" data-category="edukacja">
                        <span>Edukacja</span>
                    </button>
                </div>
            </div>
        </section>



        <!-- Projekty Grid -->
        <section class="projects-section">
            <div class="container">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($projects)): ?>
                    <div class="no-projects">
                        <h3>Brak projektów do wyświetlenia</h3>
                        <p>Nie ma jeszcze żadnych publicznych projektów. Bądź pierwszy i stwórz swój projekt!</p>
                        <?php if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true): ?>
                            <a href="create_project.php" class="btn-primary">Stwórz projekt</a>
                        <?php else: ?>
                            <a href="join.php" class="btn-primary">Zaloguj się, aby tworzyć projekty</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="projects-grid" id="projects-grid">
                        <?php foreach ($projects as $project): ?>
                            <?php
                            $categoryClass = getCategoryClass($project['categories'], $categoryMap);
                            $primaryCategory = !empty($project['categories']) ? $project['categories'][0] : 'Inne';
                            ?>
                            <article class="project-card" data-category="<?php echo $categoryClass; ?>">
                                <div class="project-image">
                                    <?php if ($project['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars($project['thumbnail']); ?>"
                                            alt="<?php echo htmlspecialchars($project['name']); ?>">
                                    <?php else: ?>
                                        <img src="../photos/project-default.jpg"
                                            alt="<?php echo htmlspecialchars($project['name']); ?>">
                                    <?php endif; ?>
                                    <span class="project-category"><?php echo $primaryCategory; ?></span>
                                    <div class="project-overlay">
                                        <div class="project-stats">
                                            <span class="stat">👥 <?php echo $project['member_count'] ?? 0; ?></span>
                                            <span class="stat">👍 <?php echo $project['like_count'] ?? 0; ?></span>
                                            <span class="stat">❤️ <?php echo $project['follows_count'] ?? 0; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="project-content">
                                    <h3><?php echo htmlspecialchars($project['name']); ?></h3>
                                    <p><?php echo truncateText(htmlspecialchars($project['short_description'])); ?></p>
                                    <div class="project-meta">
                                        <span class="project-location">📍
                                            <?php echo htmlspecialchars($project['location'] ?? 'Online'); ?></span>
                                        <span class="project-date">📅 <?php echo formatDate($project['created_at']); ?></span>
                                    </div>
                                    <div class="project-founder">

                                        <?php if (!empty($project['founder_name'])): ?>

                                            <!-- Avatar + nazwa gdy użytkownik istnieje -->
                                            <?php if (!empty($project['founder_avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($project['founder_avatar']); ?>"
                                                    alt="<?php echo htmlspecialchars($project['founder_name']); ?>"
                                                    class="founder-avatar">
                                            <?php else: ?>
                                                <img src="../photos/avatars/default_avatar.png"
                                                    alt="<?php echo htmlspecialchars($project['founder_name']); ?>"
                                                    class="founder-avatar">
                                            <?php endif; ?>

                                            <span class="founder-name">
                                                <?php echo htmlspecialchars($project['founder_name']); ?>
                                            </span>

                                        <?php else: ?>

                                            <!-- Tylko info o usunięciu konta -->
                                            <span class="founder-name" style="font-style: italic; opacity: 0.7;">
                                                Użytkownik usunął konto
                                            </span>

                                        <?php endif; ?>

                                    </div>
                                    <a href="<?php echo !empty($project['founder_name']) ? 'project.php?id=' . $project['id'] : 'javascript:void(0)'; ?>"
                                        class="project-link <?php echo empty($project['founder_name']) ? 'disabled-project' : ''; ?>"
                                        title="<?php echo empty($project['founder_name']) ? 'Projekt oczekuje na przydzielenie administratora' : ''; ?>">
                                        <span>Zobacz szczegóły</span>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </a>



                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer>
        <article id="logo">
            <img src="../photos/website-logo.jpg" alt="Logo TeenCollab">
            <h1>TeenCollab</h1>
        </article>
        <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
    </footer>

    <script>

        const filterButtons = document.querySelectorAll('.filter-btn');
        const projects = document.querySelectorAll('.project-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const category = button.dataset.category;

                projects.forEach(project => {
                    if (category === 'all' || project.dataset.category === category) {
                        project.style.display = 'block';
                        setTimeout(() => {
                            project.style.opacity = '1';
                            project.style.transform = 'translateY(0)';
                        }, 50);
                    } else {
                        project.style.opacity = '0';
                        project.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            project.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });


        const burgerMenu = document.getElementById('burger-menu');
        const navMenu = document.querySelector('.nav-menu');

        burgerMenu.addEventListener('click', () => {
            burgerMenu.classList.toggle('active');
            navMenu.classList.toggle('active');
        });


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


        document.querySelectorAll('.project-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>

</html>