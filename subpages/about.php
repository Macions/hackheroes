<?php
include("global/nav_global.php"); // Twoja sesja i nav
include("global/connection.php"); // Twoja sesja i nav

$ctaSectionReaction = '<p>Cieszymy się, że jesteś w Kreatorem Przyszłości w naszym projekcie. Wspieraj młodzieć tworząc własne projekty lub dołączając do innych!</p>
                    <div class="cta-buttons">
                        <a href="create_project.php" class="cta-button primary">Stwórz projekt!</a>
                        <a href="projects.php" class="cta-button secondary">Zobacz projekty do których możesz dołaczyć</a>
                    </div>';

if (!isset($_SESSION["logged_in"])) {
    $ctaSectionReaction = '<p>Dołącz do społeczności młodych ludzi, którzy razem tworzą, uczą się i zmieniają świat na lepsze.
                            Nie czekaj - Twój projekt może być następny!</p>
                        <div class="cta-buttons">
                            <a href="join.php" class="cta-button primary">Dołącz do nas!</a>
                            <a href="projects.php" class="cta-button secondary">Zobacz projekty</a>
                        </div>';
}


$result = $conn->query("SELECT COUNT(*) AS total_projects FROM projects WHERE status = 'Zrealizowany'");
$projectsCount = $result->fetch_assoc()['total_projects'] ?? 0;


$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$usersCount = $result->fetch_assoc()['total_users'] ?? 0;


$startDate = new DateTime('2025-11-18'); // data startu projektu
$now = new DateTime();
$interval = $startDate->diff($now);

if ($interval->y >= 1) {
    $activeTime = $interval->y;
    $activeForm = $interval->y == 1 ? 'rok' : 'lata';
} elseif ($interval->m >= 1) {
    $activeTime = $interval->m;
    $activeForm = $interval->m == 1 ? 'miesiąc' : 'miesięcy';
} else {
    $activeTime = $interval->d;
    $activeForm = $interval->d == 1 ? 'dzień' : 'dni';
}




$result = $conn->query("SELECT COUNT(DISTINCT location) AS citiesCount FROM projects WHERE country = 'PL'");
$citiesCount = $result->fetch_assoc()['citiesCount'] ?? 0;

$result = $conn->query("SELECT COUNT(DISTINCT country) AS countryCount FROM projects WHERE country != 'PL'");
$countryCount = $result->fetch_assoc()['countryCount'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O projekcie - TeenCollab</title>
    <meta name="description" content="Poznaj misję, cel i historię projektu TeenCollab - platformy dla młodych twórców">
    <link rel="shortcut icon" href="photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/about_style.css">
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
                    <li><a href="about.php" class="active">O projekcie</a></li>
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
                <h1 class="hero-title">O Projekcie TeenCollab</h1>
                <p class="hero-subtitle">Poznaj misję, historię i wartości, które przyświecają naszej społeczności
                    młodych twórców</p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo $activeTime; ?>+</span>
                        <span class="stat-label"><?php echo $activeForm; ?> działalności</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $projectsCount; ?>+</span>
                        <span class="stat-label">Zrealizowanych projektów</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $usersCount; ?>+</span>
                        <span class="stat-label">Aktywnych uczestników</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $citiesCount; ?>+</span>
                        <span class="stat-label">Miast w Polsce</span>
                    </div>
                </div>

            </div>
            <div class="hero-gradient"></div>
        </section>

        <!-- Misja Projektu -->
        <section class="mission-section">
            <div class="container">
                <div class="mission-content">
                    <div class="mission-text">
                        <h2 class="section-title">Nasza Misja</h2>
                        <p class="mission-statement">
                            TeenCollab to platforma, która łączy młodych ludzi z całej Polski, umożliwiając im rozwój
                            umiejętności technicznych,
                            tworzenie realnych projektów społecznych i budowanie wartościowej społeczności. Wierzymy, że
                            każdy młody człowiek
                            ma potencjał, by zmieniać świat na lepsze.
                        </p>
                        <div class="mission-values">
                            <div class="value-item">
                                <span class="value-icon">🚀</span>
                                <div>
                                    <h4>Empowerment</h4>
                                    <p>Wspieramy młodych w odkrywaniu i rozwijaniu ich potencjału</p>
                                </div>
                            </div>
                            <div class="value-item">
                                <span class="value-icon">🤝</span>
                                <div>
                                    <h4>Współpraca</h4>
                                    <p>Wierzymy w siłę zespołu i wzajemne wsparcie</p>
                                </div>
                            </div>
                            <div class="value-item">
                                <span class="value-icon">🌱</span>
                                <div>
                                    <h4>Zrównoważony rozwój</h4>
                                    <p>Tworzymy projekty, które mają realny, pozytywny wpływ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mission-visual">
                        <div class="visual-card">
                            <div class="card-icon">💡</div>
                            <h3>Pomysły</h3>
                            <p>Młodzi ludzie pełni innowacyjnych pomysłów</p>
                        </div>
                        <div class="visual-card">
                            <div class="card-icon">🛠️</div>
                            <h3>Narzędzia</h3>
                            <p>Dostęp do technologii i wiedzy</p>
                        </div>
                        <div class="visual-card">
                            <div class="card-icon">🌟</div>
                            <h3>Rezultaty</h3>
                            <p>Realne projekty zmieniające społeczności</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cel i założenia -->
        <section class="goals-section">
            <div class="container">
                <h2 class="section-title">Cel i Założenia</h2>
                <p class="section-subtitle">Główne filary, na których budujemy naszą społeczność</p>

                <div class="goals-grid">
                    <div class="goal-card">
                        <div class="goal-icon">💻</div>
                        <h3>Rozwój umiejętności IT i programowania</h3>
                        <p>Praktyczna nauka technologii przyszłości poprzez realne projekty i mentoring doświadczonych
                            developerów</p>
                        <ul class="goal-features">
                            <li>Warsztaty programistyczne</li>
                            <li>Projekty web development</li>
                            <li>Nauka nowych technologii</li>
                        </ul>
                    </div>

                    <div class="goal-card">
                        <div class="goal-icon">🔄</div>
                        <h3>Tworzenie praktycznych projektów</h3>
                        <p>Od pomysłu do implementacji - młodzi tworzą rozwiązania dla realnych problemów społecznych
                        </p>
                        <ul class="goal-features">
                            <li>Projekty społeczne</li>
                            <li>Aplikacje użyteczne</li>
                            <li>Inicjatywy ekologiczne</li>
                        </ul>
                    </div>

                    <div class="goal-card">
                        <div class="goal-icon">👥</div>
                        <h3>Współpraca w zespole</h3>
                        <p>Nauka pracy w grupie, komunikacji i dzielenia się wiedzą w międzyszkolnych zespołach</p>
                        <ul class="goal-features">
                            <li>Zespoły projektowe</li>
                            <li>Code review</li>
                            <li>Wymiana doświadczeń</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Historia projektu -->
        <section class="history-section">
            <div class="container">
                <h2 class="section-title">Historia Projektu</h2>
                <p class="section-subtitle">Od małej inicjatywy do ogólnopolskiej społeczności</p>

                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-year">2022</div>
                        <div class="timeline-content">
                            <h3>Powstanie inicjatywy</h3>
                            <p>Grupa zapalonych uczniów stworzyła pierwszy prototyp platformy do współpracy nad
                                projektami szkolnymi</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-content">
                            <h3>Pierwsze sukcesy</h3>
                            <p>Ukończono 15 pierwszych projektów, społeczność rozrosła się do 100 aktywnych członków z 5
                                miast</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-content">
                            <h3>Ekspansja i rozwój</h3>
                            <p>Platforma zdobyła grant rozwojowy, nawiązano partnerstwa z 10 szkołami, uruchomiono
                                program mentoringowy</p>
                        </div>
                    </div>

                    <div class="timeline-item">
                        <div class="timeline-year">2025</div>
                        <div class="timeline-content">
                            <h3>Obecnie</h3>
                            <p>500+ aktywnych członków, 100+ zrealizowanych projektów, społeczność obecna w 20+ miastach
                                w Polsce</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Jak działa projekt -->
        <section class="how-it-works-section">
            <div class="container">
                <h2 class="section-title">Jak Działa Projekt?</h2>
                <p class="section-subtitle">Prosty proces od pomysłu do realizacji</p>

                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Zgłaszanie się do projektu</h3>
                            <p>Młodzi wybierają interesujące ich inicjatywy lub proponują własne pomysły poprzez naszą
                                platformę</p>
                        </div>
                    </div>

                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Praca w zespołach</h3>
                            <p>Uczestnicy pracują w międzyszkolnych grupach, korzystając z narzędzi collaboration i
                                wsparcia mentorów</p>
                        </div>
                    </div>

                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Prezentacja efektów</h3>
                            <p>Gotowe projekty są prezentowane społeczności, otrzymują feedback i są wdrażane w życie
                            </p>
                        </div>
                    </div>

                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Rozwój i kontynuacja</h3>
                            <p>Najlepsze projekty otrzymują wsparcie w dalszym rozwoju, a uczestnicy - certyfikaty i
                                rekomendacje</p>
                        </div>
                    </div>
                </div>

                <div class="process-visual">
                    <div class="visual-stage">
                        <div class="stage-icon">💡</div>
                        <span>Pomysł</span>
                    </div>
                    <div class="visual-arrow">→</div>
                    <div class="visual-stage">
                        <div class="stage-icon">👥</div>
                        <span>Zespół</span>
                    </div>
                    <div class="visual-arrow">→</div>
                    <div class="visual-stage">
                        <div class="stage-icon">🛠️</div>
                        <span>Realizacja</span>
                    </div>
                    <div class="visual-arrow">→</div>
                    <div class="visual-stage">
                        <div class="stage-icon">🎯</div>
                        <span>Rezultat</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Wyróżnienia i rezultaty -->
        <section class="achievements-section">
            <div class="container">
                <h2 class="section-title">Wyróżnienia & Rezultaty</h2>
                <p class="section-subtitle">Konkretne liczby i sukcesy, które pokazują nasz wpływ</p>

                <div class="achievements-grid">
                    <div class="achievement-card">
                        <div class="achievement-icon">📊</div>
                        <div class="achievement-number"><?php echo $projectsCount; ?>+</div>
                        <div class="achievement-label">Zrealizowanych projektów</div>
                    </div>

                    <div class="achievement-card">
                        <div class="achievement-icon">👥</div>
                        <div class="achievement-number"><?php echo $usersCount; ?>+</div>
                        <div class="achievement-label">Aktywnych uczestników</div>
                    </div>

                    <div class="achievement-card">
                        <div class="achievement-icon">🏆</div>
                        <div class="achievement-number">10+</div>
                        <div class="achievement-label">Nagród i wyróżnień</div>
                    </div>

                    <div class="achievement-card">
                        <div class="achievement-icon">🌍</div>
                        <div class="achievement-number"><?php echo $countryCount; ?>+</div>
                        <div class="achievement-label">Projektów światowych</div>
                    </div>
                </div>

            </div>

            <div class="success-stories">
                <h3>Nasze największe sukcesy</h3>
                <div class="stories-grid">
                    <div class="story-card">
                        <div class="story-badge">🥇</div>
                        <h4>EcoYouth Award 2024</h4>
                        <p>Projekt "Zielone Miasto" zdobył główną nagrodę w ogólnopolskim konkursie ekologicznym</p>
                    </div>

                    <div class="story-card">
                        <div class="story-badge">💼</div>
                        <h4>Partnerstwo z TechCorp</h4>
                        <p>Nawiazaliśmy współpracę z wiodącą firmą technologiczną, która wspiera nasze inicjatywy
                        </p>
                    </div>

                    <div class="story-card">
                        <div class="story-badge">🎓</div>
                        <h4>Program mentoringowy</h4>
                        <p>Uruchomiliśmy program, w którym 50+ doświadczonych mentorów wspiera młodych twórców</p>
                    </div>
                </div>
            </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Zainspirowała Cię nasza historia?</h2>
                    <?php echo $ctaSectionReaction; ?>
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
                        <p>Platforma dla kreatorów przyszłości</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
                </div>
            </div>
        </div>
    </footer>

    <script>

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


        document.querySelectorAll('.goal-card, .timeline-item, .process-step, .achievement-card, .story-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });


        document.querySelectorAll('.goal-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });

        document.querySelectorAll('.timeline-item').forEach((item, index) => {
            item.style.transitionDelay = `${index * 0.2}s`;
        });

        document.querySelectorAll('.process-step').forEach((step, index) => {
            step.style.transitionDelay = `${index * 0.1}s`;
        });
    </script>
</body>

</html>