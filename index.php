<?php
session_start();
include("subpages/global/connection.php");
include("subpages/global/nav_global.php");


try {

    $projectsStmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE status = 'active' OR status = 'completed'");
    $projectsStmt->execute();
    $totalProjects = $projectsStmt->get_result()->fetch_assoc()['total'] ?? 0;
    $projectsStmt->close();


    $usersStmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
    $usersStmt->execute();
    $totalUsers = $usersStmt->get_result()->fetch_assoc()['total'] ?? 0;
    $usersStmt->close();


    $latestProjectsStmt = $conn->prepare("
        SELECT 
            p.id, 
            p.name AS title, 
            p.short_description AS description, 
            REPLACE(p.thumbnail, '../', '') AS image_url, 
            p.location, 
            u.nick as founder_name
        FROM projects p 
        LEFT JOIN users u ON p.founder_id = u.id 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");

    $latestProjectsStmt->execute();
    $latestProjects = $latestProjectsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $joinedAt = $result['created_at'] ?? null;
        $stmt->close();
    } else {
        $joinedAt = null;
    }

    function pluralForm($number, $forms)
    {

        $n = abs($number);

        if ($n == 1) return $forms[0]; // 1 miesiąc
        if ($n >= 2 && $n <= 4) return $forms[1]; // 2,3,4 miesiące
        return $forms[2]; // 5+ miesięcy
    }

    function timeAgo($date)
    {
        if (!$date) return 'jakiegoś czasu';

        $now = new DateTime();
        $joined = new DateTime($date);
        $diff = $joined->diff($now);

        $years = $diff->y;
        $months = $diff->m;
        $days = $diff->d;


        if ($years > 0) {
            if ($years == 1) return '1 roku';
            return "$years lat";
        }


        if ($months > 0) {
            return $months . ' ' . pluralForm($months, ['miesiąca', 'miesięcy', 'miesięcy']);
        }


        if ($days > 0) {
            if ($days == 1) return '1 dnia';
            return "$days dni";
        }

        return 'kilku dni';
    }


    $opinionsStmt = $conn->prepare("
        SELECT u.nick, u.avatar, r.comment, r.created_at
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 4
    ");

    $opinionsStmt->execute();
    $opinions = $opinionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);


    foreach ($opinions as &$opinion) {
        $opinion['avatar'] = preg_replace('#^\.\./#', '', $opinion['avatar']);
    }

    $opinionsStmt->close();
} catch (Exception $e) {

    $totalProjects = 100;
    $totalUsers = 100;
    $latestProjects = [];
    $opinions = [];
}
?>



<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TeenCollab</title>
    <meta name="description"
        content="Platforma, która łączy uczniów z całej Polski, by razem tworzyć projekty społeczne, ekologiczne i wspierające zdrowie psychiczne.">
    <link rel="shortcut icon" href="photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="styles/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <nav>
            <div class="nav-container">
                <div class="nav-brand">
                    <img src="photos/website-logo.jpg" alt="Logo TeenCollab">
                    <span>TeenCollab</span>
                </div>

                <ul class="nav-menu">
                    <li><a class="active" href="index.php">Strona główna</a></li>
                    <li><a href="subpages/projects.php">Projekty</a></li>
                    <li><a href="subpages/community.php">Społeczność</a></li>
                    <li><a href="subpages/about.php">O projekcie</a></li>
                    <li><a href="subpages/notifications.php">Powiadomienia</a></li>
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

        <!-- Main Content Sections -->
        <section id="top-baner">
            <article id="left-baner">
                <div class="top">
                    <h1>TeenCollab</h1>
                    <p>Łączymy <span>młodych</span>, <br>żeby razem zmieniać <span>przyszłość</span>.</p>
                </div>
                <div class="bottom">
                    <p><i>Platforma, która łączy uczniów z całej Polski, by razem tworzyć projekty społeczne,
                            ekologiczne i wspierające zdrowie psychiczne.</i></p>
                </div>
            </article>
            <article id="right-baner">
                <img src="photos/baner-photo.jpg"
                    alt="Baner nawiązujący do założeń platformy: pomocy, wspierania, tworzenia projektów.">
            </article>
        </section>

        <section id="stats">
            <article id="amount_of_projects">
                <h2><span><?php echo $totalProjects; ?></span>+</h2>
                <p>projektów</p>
            </article>

            <article id="amount_of_future_creators">
                <h2><span><?php echo $totalUsers; ?></span>+</h2>
                <p>kreatorów przyszłości</p>
            </article>

            <article id="amount_of_ideas">
                <h2>∞</h2>
                <p>pomysłów</p>
            </article>
        </section>

        <section id="buttons-join_projects">
            <a href="subpages/join.php" class="join_us-button">Dołącz do nas</a>
            <a href="subpages/projects.php" class="projects-button">Projekty</a>
        </section>

        <section id="how_it_works">
            <h1>Jak to działa?</h1>

            <article class="content">
                <div class="make_projects">
                    <img src="photos/make-projects.jpg"
                        alt="Grafika przedstawiająca otwarty laptop, na jego górze żarówka nawiązująca do kreatywności/pomysłów">
                    <h3>Twórz projekty</h3>
                </div>

                <img class="arrow" src="photos/arrow.png" alt="Strzałka w prawo">

                <div class="connect_to_other">
                    <img src="photos/connect-to-other.jpg"
                        alt="Grafika postaci ludzi, połączonych linią świadczącą o współpracy ludzi">
                    <h3>Łącz się z innymi</h3>
                </div>

                <img class="arrow" src="photos/arrow.png" alt="Strzałka w prawo">

                <div class="support_and_inspire">
                    <img src="photos/support-inspire.jpg"
                        alt="Grafika przedstawiająca ręce trzymające serce jako znak wsparcia">
                    <h3>Wspieraj i inspiruj</h3>
                </div>
            </article>
        </section>

        <section id="our_projects">
            <h1>Nasze projekty</h1>

            <article id="projects">
                <?php if (!empty($latestProjects)): ?>
                    <?php foreach ($latestProjects as $index => $project): ?>
                        <div class="project" data-order="<?php echo $index + 1; ?>">
                            <div class="content">
                                <div class="left_side-project">
                                    <img src="<?php echo htmlspecialchars($project['image_url'] ?? ''); ?>"
                                        alt="<?php echo htmlspecialchars($project['title']); ?>">
                                </div>
                                <div class="right_side-project">
                                    <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                                    <p><?php echo htmlspecialchars($project['description']); ?></p>
                                    <div class="project-meta">
                                        <span
                                            class="location"><?php echo htmlspecialchars($project['location'] ?? 'Online'); ?></span>
                                        <?php if (!empty($project['founder_name'])): ?>
                                            <span class="founder"><?php echo htmlspecialchars($project['founder_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="subpages/project.php?id=<?php echo $project['id']; ?>">
                                        Zobacz projekt →
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Domyślne projekty -->
                    <div class="project" data-order="1">
                        <div class="content">
                            <div class="left_side-project">
                                <img src="photos/sprzatanie_lasu.jpg" alt="Sprzątanie lasu">
                            </div>
                            <div class="right_side-project">
                                <h2>Sprzątanie lasu</h2>
                                <p>Zobacz jak wyglądała jedna z akcji w Łowiczu, w całości zorganizowana przez naszą stronę.
                                </p>
                                <div class="project-meta">
                                    <span class="location">Łowicz</span>
                                    <span class="founder">TeenCollab Team</span>
                                </div>
                                <a href="https://teencollab.pl/articles/sprzatanie-lasu-w-łowiczu">
                                    Zobacz projekt →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <article id="move_circles">
                <?php for ($i = 1; $i <= min(5, count($latestProjects) ?: 1); $i++): ?>
                    <div class="circle <?php echo $i === 1 ? 'active' : ''; ?>" data-order="<?php echo $i; ?>"
                        aria-label="Przejdź do slajdu <?php echo $i; ?>"></div>
                <?php endfor; ?>
            </article>

            <div class="projects-view-all">
                <a href="https://teencollab.pl/projekty">Zobacz wszystkie projekty</a>
            </div>
        </section>

        <section id="future_makers_opinions">
            <img class="arrow left" src="photos/mve_arrow.svg"
                alt="Strzałka do przeglądania wstecz opinii twórców przyszłości">
            <img class="arrow right" src="photos/mve_arrow.svg"
                alt="Strzałka do przeglądania kolejnych opinii twórców przyszłości">

            <h1>Opinie kreatorów przyszłości</h1>

            <article id="opinions">
                <?php if (!empty($opinions)): ?>
                    <?php foreach ($opinions as $index => $opinion): ?>
                        <div class="opinion" data-opinion="<?php echo $index + 1; ?>">
                            <div class="content">
                                <div class="left_side-opinions">
                                    <img src="<?php echo htmlspecialchars($opinion['avatar'] ?? 'photos/sample_person.png'); ?>"
                                        alt="<?php echo htmlspecialchars($opinion['nick']); ?>">
                                    <p>
                                        <?php echo htmlspecialchars($opinion['nick']); ?>,<br>
                                        tworzę z Wami przyszłość od <?php echo timeAgo($joinedAt); ?>
                                    </p>


                                </div>
                                <div class="right_side-opinions">
                                    <p>"<?php echo htmlspecialchars($opinion['comment']); ?>"</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Sekcja zachęcająca do dodania pierwszej opinii - FULL WIDTH -->
                    <div class="opinion-prompt-full">
                        <div class="prompt-content">
                            <div class="prompt-icon">
                                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                                    <path
                                        d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z" />
                                    <path d="M11 11h2v2h-2zm-4 0h2v2H7zm8 0h2v2h-2z" />
                                </svg>
                            </div>
                            <h3>Bądź pierwszą osobą, która podzieli się opinią!</h3>
                            <p>Twoje doświadczenia mogą zainspirować innych młodych twórców. Podziel się swoją historią
                                i pokaż, jak TeenCollab pomógł Ci rozwijać pasje i realizować marzenia.</p>
                            <div class="prompt-actions">
                                <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                    <!-- Dla zalogowanych użytkowników -->
                                    <a href="subpages/create_opinion.php" class="btn-primary">Dodaj swoją opinię</a>
                                    <a href="subpages/projects.php" class="btn-secondary">Przeglądaj projekty</a>
                                <?php else: ?>
                                    <!-- Dla niezalogowanych użytkowników -->
                                    <a href="subpages/login.php" class="btn-primary">Zaloguj się i dodaj opinię</a>
                                    <a href="subpages/register.php" class="btn-secondary">Dołącz do społeczności</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <section id="make_future_with_us">
            <h1>Twórz przyszłość razem z nami!</h1>

            <article id="buttons-make_future">
                <a href="subpages/join.php" class="join_us-button">Dołącz do nas</a>
                <a href="subpages/projects.php" class="projects-button">Projekty</a>
            </article>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <img src="photos/website-logo.jpg" alt="Logo TeenCollab">
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

        document.addEventListener('DOMContentLoaded', function() {

            class ProjectsGridManager {
                constructor() {
                    this.projectsContainer = document.getElementById('projects');
                    this.projects = document.querySelectorAll('.project');
                    this.init();
                }

                init() {
                    this.setupHoverEffects();
                    this.setupLazyLoading();
                    this.setupProjectAnimations();
                }

                setupHoverEffects() {
                    this.projects.forEach(project => {
                        project.addEventListener('mouseenter', () => {
                            this.animateProjectHover(project, true);
                        });

                        project.addEventListener('mouseleave', () => {
                            this.animateProjectHover(project, false);
                        });
                    });
                }

                animateProjectHover(project, isHovering) {
                    const img = project.querySelector('img');
                    const button = project.querySelector('a');

                    if (isHovering) {
                        project.style.transform = 'translateY(-8px) scale(1.02)';
                        if (img) img.style.transform = 'scale(1.08)';
                        if (button) button.style.transform = 'translateY(-2px)';
                    } else {
                        project.style.transform = 'translateY(0) scale(1)';
                        if (img) img.style.transform = 'scale(1)';
                        if (button) button.style.transform = 'translateY(0)';
                    }
                }

                setupLazyLoading() {
                    const imageObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                }
                                img.classList.add('loaded');
                                imageObserver.unobserve(img);
                            }
                        });
                    });

                    document.querySelectorAll('.project img').forEach(img => {
                        imageObserver.observe(img);
                    });
                }

                setupProjectAnimations() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.style.opacity = '1';
                                entry.target.style.transform = 'translateY(0) scale(1)';
                            }
                        });
                    }, {
                        threshold: 0.1,
                        rootMargin: '0px 0px -50px 0px'
                    });

                    this.projects.forEach((project, index) => {
                        project.style.opacity = '0';
                        project.style.transform = 'translateY(30px) scale(0.95)';
                        project.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                        project.style.animationDelay = `${index * 0.1}s`;

                        observer.observe(project);
                    });
                }
            }


            class OpinionsSlider {
                constructor() {
                    this.container = document.getElementById('opinions');
                    this.slides = document.querySelectorAll('.opinion');
                    this.leftArrow = document.querySelector('#future_makers_opinions .arrow.left');
                    this.rightArrow = document.querySelector('#future_makers_opinions .arrow.right');
                    this.currentSlide = 0;
                    this.totalSlides = this.slides.length;
                    this.isAnimating = false;

                    this.init();
                }

                init() {
                    if (this.totalSlides <= 1) {
                        if (this.leftArrow) this.leftArrow.style.display = 'none';
                        if (this.rightArrow) this.rightArrow.style.display = 'none';
                        return;
                    }

                    this.setupEventListeners();
                    this.updateSliderPosition();
                }

                setupEventListeners() {
                    if (this.leftArrow) {
                        this.leftArrow.addEventListener('click', () => this.previousSlide());
                    }
                    if (this.rightArrow) {
                        this.rightArrow.addEventListener('click', () => this.nextSlide());
                    }


                    document.addEventListener('keydown', (e) => {
                        const opinionsSection = document.getElementById('future_makers_opinions');
                        if (opinionsSection && opinionsSection.contains(e.target)) {
                            if (e.key === 'ArrowLeft') this.previousSlide();
                            if (e.key === 'ArrowRight') this.nextSlide();
                        }
                    });
                }

                goToSlide(slideIndex) {
                    if (this.isAnimating) return;

                    this.isAnimating = true;
                    this.currentSlide = slideIndex;
                    this.updateSliderPosition();

                    setTimeout(() => {
                        this.isAnimating = false;
                    }, 500);
                }

                nextSlide() {
                    const nextSlide = (this.currentSlide + 1) % this.totalSlides;
                    this.goToSlide(nextSlide);
                }

                previousSlide() {
                    const prevSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                    this.goToSlide(prevSlide);
                }

                updateSliderPosition() {
                    const translateX = -this.currentSlide * 50;
                    this.container.style.transform = `translateX(${translateX}%)`;


                    this.container.style.animation = 'none';
                    setTimeout(() => {
                        this.container.style.animation = 'opinionSlide 0.5s ease-out';
                    }, 10);
                }
            }


            class NavigationManager {
                constructor() {
                    this.burgerMenu = document.getElementById('burger-menu');
                    this.navMenu = document.querySelector('.nav-menu');
                    this.init();
                }

                init() {
                    this.setupEventListeners();
                }

                setupEventListeners() {
                    if (!this.burgerMenu) return;

                    this.burgerMenu.addEventListener('click', () => this.toggleMenu());


                    document.querySelectorAll('.nav-menu a').forEach(link => {
                        link.addEventListener('click', () => this.closeMenu());
                    });


                    document.addEventListener('click', (e) => {
                        if (this.navMenu && this.navMenu.classList.contains('active')) {
                            if (!this.navMenu.contains(e.target) && !this.burgerMenu.contains(e.target)) {
                                this.closeMenu();
                            }
                        }
                    });


                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && this.navMenu && this.navMenu.classList.contains('active')) {
                            this.closeMenu();
                        }
                    });
                }

                toggleMenu() {
                    if (!this.burgerMenu || !this.navMenu) return;

                    const isOpening = !this.burgerMenu.classList.contains('active');

                    this.burgerMenu.classList.toggle('active');
                    this.navMenu.classList.toggle('active');
                    document.body.style.overflow = isOpening ? 'hidden' : '';
                }

                closeMenu() {
                    if (!this.burgerMenu || !this.navMenu) return;

                    this.burgerMenu.classList.remove('active');
                    this.navMenu.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }


            class ScrollAnimations {
                constructor() {
                    this.observerOptions = {
                        threshold: 0.1,
                        rootMargin: '0px 0px -50px 0px'
                    };
                    this.init();
                }

                init() {
                    this.setupIntersectionObserver();
                    this.setupSmoothScrolling();
                }

                setupIntersectionObserver() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.animateElement(entry.target);
                            }
                        });
                    }, this.observerOptions);


                    const animatedElements = document.querySelectorAll(
                        '#stats article, #how_it_works .content > div, .project, .opinion, .projects-view-all, #buttons-join_projects, #make_future_with_us'
                    );

                    animatedElements.forEach(el => {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(30px)';
                        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                        observer.observe(el);
                    });
                }

                animateElement(element) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }

                setupSmoothScrolling() {
                    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                        anchor.addEventListener('click', (e) => {
                            e.preventDefault();
                            const targetId = anchor.getAttribute('href');
                            const target = document.querySelector(targetId);

                            if (target) {
                                target.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });
                            }
                        });
                    });
                }
            }


            const projectsGrid = new ProjectsGridManager();
            const opinionsSlider = new OpinionsSlider();
            const navigationManager = new NavigationManager();
            const scrollAnimations = new ScrollAnimations();


            addCustomAnimations();

            console.log('🚀 TeenCollab - JavaScript initialized successfully!');
        });


        function addCustomAnimations() {
            const style = document.createElement('style');
            style.textContent = `
        @keyframes opinionSlide {
            from {
                opacity: 0.8;
                transform: translateX(10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out both;
        }
        
        /* Smooth transitions for projects only */
        .project {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Focus styles for accessibility */
        .project a:focus-visible {
            outline: 2px solid #10b981;
            outline-offset: 2px;
        }
        
        /* Image load animation */
        .project img.loaded {
            animation: fadeInUp 0.6s ease-out;
        }
    `;
            document.head.appendChild(style);
        }


        function setupLazyLoading() {
            const lazyImages = document.querySelectorAll('img[data-src]');

            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        }


        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>

</html>