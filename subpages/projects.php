<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projekty - TeenCollab</title>
    <meta name="description"
        content="Przeglądaj wszystkie projekty zrealizowane przez młodych kreatorów na platformie TeenCollab.">
    <link rel="shortcut icon" href="photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/projects_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="scripts/script_projects.js" defer></script>
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
                    <li><a href="index.html">Strona główna</a></li>
                    <li><a href="projekty.html" class="active">Projekty</a></li>
                    <li><a href="społeczność.html">Społeczność</a></li>
                    <li><a href="o-projekcie.html">O projekcie</a></li>
                    <li class="nav-cta"><a href="dolacz.html" class="cta-button">Dołącz</a></li>
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
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Projektów</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">1000+</span>
                        <span class="stat-label">Uczestników</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">15</span>
                        <span class="stat-label">Miast</span>
                    </div>
                </div>
            </div>
            <div class="hero-gradient"></div>
        </section>

        <!-- Filtry -->
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
                </div>
            </div>
        </section>

        <!-- Projekty Grid -->
        <section class="projects-section">
            <div class="container">
                <div class="projects-grid" id="projects-grid">
                    <article class="project-card" data-category="ekologia">
                        <div class="project-image">
                            <img src="../photos/baner-photo.jpg" alt="Wolontariusze sprzątający las">
                            <span class="project-category">Ekologia</span>
                        </div>
                        <div class="project-content">
                            <h3>Sprzątanie lasu w Łowiczu</h3>
                            <p>Akcja porządkowania lokalnego lasu, w pełni zorganizowana przez TeenCollab. Dołącz do nas i pomóż chronić przyrodę!</p>
                            <div class="project-meta">
                                <span class="project-location">📍 Łowicz</span>
                                <span class="project-date">📅 15.06.2024</span>
                            </div>
                            <a href="articles/sprzatanie-lasu-w-łowiczu.html" class="project-link">
                                <span>Zobacz szczegóły</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                        </div>
                    </article>

                    <article class="project-card" data-category="zdrowie">
                        <div class="project-image">
                            <img src="../photos/baner-photo.jpg" alt="Warsztaty o zdrowiu psychicznym">
                            <span class="project-category">Zdrowie</span>
                        </div>
                        <div class="project-content">
                            <h3>Zdrowe życie młodzieży</h3>
                            <p>Projekt edukacyjny promujący zdrowe nawyki i dbanie o psychikę młodzieży. Warsztaty, spotkania z ekspertami.</p>
                            <div class="project-meta">
                                <span class="project-location">📍 Online</span>
                                <span class="project-date">📅 Cyklicznie</span>
                            </div>
                            <a href="#" class="project-link">
                                <span>Zobacz szczegóły</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                        </div>
                    </article>

                    <article class="project-card" data-category="społeczne">
                        <div class="project-image">
                            <img src="../photos/baner-photo.jpg" alt="Młodzież pomagająca seniorom">
                            <span class="project-category">Społeczne</span>
                        </div>
                        <div class="project-content">
                            <h3>Pomoc seniorom</h3>
                            <p>Inicjatywa wspierająca seniorów w lokalnej społeczności poprzez wolontariat i regularne wizyty.</p>
                            <div class="project-meta">
                                <span class="project-location">📍 Warszawa</span>
                                <span class="project-date">📅 Co tydzień</span>
                            </div>
                            <a href="#" class="project-link">
                                <span>Zobacz szczegóły</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                        </div>
                    </article>

                    <article class="project-card" data-category="ekologia">
                        <div class="project-image">
                            <img src="../photos/baner-photo.jpg" alt="Sadzenie drzew w parku">
                            <span class="project-category">Ekologia</span>
                        </div>
                        <div class="project-content">
                            <h3>Sadzenie drzew w mieście</h3>
                            <p>Projekt ekologiczny, sadzenie drzew w miejskich parkach i skwerach. Razem tworzymy zielone płuca miasta!</p>
                            <div class="project-meta">
                                <span class="project-location">📍 Kraków</span>
                                <span class="project-date">📅 20.05.2024</span>
                            </div>
                            <a href="#" class="project-link">
                                <span>Zobacz szczegóły</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                        </div>
                    </article>

                    <article class="project-card" data-category="społeczne">
                        <div class="project-image">
                            <img src="../photos/baner-photo.jpg" alt="Akcja charytatywna">
                            <span class="project-category">Społeczne</span>
                        </div>
                        <div class="project-content">
                            <h3>Akcja charytatywna</h3>
                            <p>Zbiórka funduszy i darów dla lokalnych organizacji społecznych. Każda pomoc się liczy!</p>
                            <div class="project-meta">
                                <span class="project-location">📍 Wrocław</span>
                                <span class="project-date">📅 10.04.2024</span>
                            </div>
                            <a href="#" class="project-link">
                                <span>Zobacz szczegóły</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </a>
                        </div>
                    </article>
                </div>
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
        // Filtracja projektów
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

        // Burger menu
        const burgerMenu = document.getElementById('burger-menu');
        const navMenu = document.querySelector('.nav-menu');

        burgerMenu.addEventListener('click', () => {
            burgerMenu.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    </script>
</body>

</html>