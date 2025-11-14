<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Anna Nowak | TeenCollab</title>
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
                    <li><a href="index.html">Strona główna</a></li>
                    <li><a href="projekty.html">Projekty</a></li>
                    <li><a href="społeczność.html">Społeczność</a></li>
                    <li><a href="o-projekcie.html">O projekcie</a></li>
                    <li class="nav-cta"><a href="konto.html">Moje konto</a></li>
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
                        <img src="../photos/sample_person.png" alt="Anna Nowak">
                    </div>
                    <div class="profile-info">
                        <div class="profile-badge">Aktywny członek</div>
                        <h1 class="profile-name">Anna Nowak</h1>
                        <p class="profile-role">Front-end Developer & Organizator społeczny</p>
                        <p class="profile-bio">
                            Uczeń technikum, pasjonatka IT i projektów społecznych. Tworzę rzeczy, które pomagają innym i zmieniają świat na lepsze.
                        </p>
                        <div class="profile-actions">
                            <button class="btn-primary">
                                <span>✉️ Wyślij wiadomość</span>
                            </button>
                            <button class="btn-secondary">
                                <span>❤️ Obserwuj</span>
                            </button>
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
                            <div class="info-item">
                                <span class="info-icon">🏫</span>
                                <div class="info-content">
                                    <span class="info-label">Szkoła</span>
                                    <span class="info-value">Technikum Informatyczne nr 1 w Warszawie</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">🏙️</span>
                                <div class="info-content">
                                    <span class="info-label">Miasto</span>
                                    <span class="info-value">Warszawa</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">🎯</span>
                                <div class="info-content">
                                    <span class="info-label">Dziedziny zainteresowań</span>
                                    <span class="info-value">Technologia, Ekologia, Edukacja</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">📅</span>
                                <div class="info-content">
                                    <span class="info-label">Dołączył</span>
                                    <span class="info-value">15 stycznia 2024</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <span class="info-icon">🌟</span>
                                <div class="info-content">
                                    <span class="info-label">Rola w TeenCollab</span>
                                    <span class="info-value">Twórca projektów, Mentor społeczności</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 🚀 Aktywne projekty -->
                    <section class="content-section projects-section">
                        <div class="section-header">
                            <h2>Aktywne projekty</h2>
                            <a href="projekty.html?user=anna" class="see-all-link">Zobacz wszystkie →</a>
                        </div>
                        <div class="projects-grid">
                            <div class="project-card">
                                <div class="project-image">
                                    <img src="../photos/project-sample.jpg" alt="EcoFuture">
                                    <span class="project-status status-active">Aktywny</span>
                                </div>
                                <div class="project-info">
                                    <h3 class="project-title">EcoFuture</h3>
                                    <p class="project-description">Platforma edukacyjna promująca zrównoważony rozwój wśród młodzieży</p>
                                    <div class="project-meta">
                                        <span class="meta-item">👥 3 członków</span>
                                        <span class="meta-item">❤️ 132</span>
                                    </div>
                                    <button class="btn-secondary btn-sm">Zobacz projekt</button>
                                </div>
                            </div>

                            <div class="project-card">
                                <div class="project-image">
                                    <img src="../photos/project-sample2.jpg" alt="CodeMentor">
                                    <span class="project-status status-active">Aktywny</span>
                                </div>
                                <div class="project-info">
                                    <h3 class="project-title">CodeMentor</h3>
                                    <p class="project-description">Platforma łącząca młodych programistów z mentorami</p>
                                    <div class="project-meta">
                                        <span class="meta-item">👥 5 członków</span>
                                        <span class="meta-item">❤️ 89</span>
                                    </div>
                                    <button class="btn-secondary btn-sm">Zobacz projekt</button>
                                </div>
                            </div>

                            <div class="project-card">
                                <div class="project-image">
                                    <img src="../photos/project-sample3.jpg" alt="GreenCity">
                                    <span class="project-status status-completed">Zakończony</span>
                                </div>
                                <div class="project-info">
                                    <h3 class="project-title">GreenCity</h3>
                                    <p class="project-description">Aplikacja do zarządzania odpadami w mieście</p>
                                    <div class="project-meta">
                                        <span class="meta-item">👥 8 członków</span>
                                        <span class="meta-item">❤️ 156</span>
                                    </div>
                                    <button class="btn-secondary btn-sm">Zobacz projekt</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 🧾 O użytkowniku -->
                    <section class="content-section about-section">
                        <div class="section-header">
                            <h2>O mnie</h2>
                        </div>
                        <div class="about-content">
                            <p class="about-text">
                                Od 3 lat pasjonuję się programowaniem i technologią. Uwielbiam tworzyć projekty, które mają realny wpływ na społeczność. 
                                Specjalizuję się w front-end development, ale ciągle rozwijam swoje umiejętności w zakresie UI/UX design i zarządzania projektami.
                                Wierzę, że technologia może zmieniać świat na lepsze i chcę być częścią tej zmiany.
                            </p>
                            
                            <div class="skills-section">
                                <h3 class="skills-title">Umiejętności</h3>
                                <div class="skills-grid">
                                    <span class="skill-badge">HTML/CSS</span>
                                    <span class="skill-badge">JavaScript</span>
                                    <span class="skill-badge">React</span>
                                    <span class="skill-badge">UI/UX Design</span>
                                    <span class="skill-badge">Zarządzanie projektami</span>
                                    <span class="skill-badge">Figma</span>
                                    <span class="skill-badge">Git</span>
                                    <span class="skill-badge">Photoshop</span>
                                </div>
                            </div>

                            <div class="interests-section">
                                <h3 class="interests-title">Zainteresowania</h3>
                                <div class="interests-grid">
                                    <span class="interest-tag">🎨 Design</span>
                                    <span class="interest-tag">🌱 Ekologia</span>
                                    <span class="interest-tag">📚 Edukacja</span>
                                    <span class="interest-tag">🎮 Gry planszowe</span>
                                    <span class="interest-tag">📸 Fotografia</span>
                                    <span class="interest-tag">🚴 Rower</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ⭐ Osiągnięcia -->
                    <section class="content-section achievements-section">
                        <div class="section-header">
                            <h2>Osiągnięcia</h2>
                        </div>
                        <div class="achievements-grid">
                            <div class="achievement-card">
                                <div class="achievement-icon">🏆</div>
                                <div class="achievement-content">
                                    <h3>Zrealizował 10+ projektów</h3>
                                    <p>Aktywnie uczestniczy w tworzeniu społecznościowych inicjatyw</p>
                                </div>
                            </div>
                            <div class="achievement-card">
                                <div class="achievement-icon">⭐</div>
                                <div class="achievement-content">
                                    <h3>Twórca wyróżnionego projektu</h3>
                                    <p>Projekt EcoFuture został wyróżniony jako "Projekt Tygodnia"</p>
                                </div>
                            </div>
                            <div class="achievement-card">
                                <div class="achievement-icon">👨‍🏫</div>
                                <div class="achievement-content">
                                    <h3>Mentor społeczności</h3>
                                    <p>Pomaga młodym developerom w rozwijaniu ich umiejętności</p>
                                </div>
                            </div>
                            <div class="achievement-card">
                                <div class="achievement-icon">💬</div>
                                <div class="achievement-content">
                                    <h3>Aktywny uczestnik</h3>
                                    <p>Zaangażowany w ponad 50 dyskusji i pomocy innym</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ❤️ Aktywność społecznościowa -->
                    <section class="content-section activity-section">
                        <div class="section-header">
                            <h2>Aktywność</h2>
                        </div>
                        <div class="activity-timeline">
                            <div class="activity-item">
                                <div class="activity-icon">💬</div>
                                <div class="activity-content">
                                    <p><strong>Skomentował projekt</strong> "TechEdu Platform"</p>
                                    <span class="activity-time">2 godziny temu</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">❤️</div>
                                <div class="activity-content">
                                    <p><strong>Polubił projekt</strong> "ArtHub Community"</p>
                                    <span class="activity-time">1 dzień temu</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">👥</div>
                                <div class="activity-content">
                                    <p><strong>Dołączył do projektu</strong> "GreenCity Initiative"</p>
                                    <span class="activity-time">3 dni temu</span>
                                </div>
                            </div>
                            <div class="activity-item">
                                <div class="activity-icon">🎯</div>
                                <div class="activity-content">
                                    <p><strong>Ukończył cel</strong> w projekcie "EcoFuture"</p>
                                    <span class="activity-time">5 dni temu</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 👥 Współpraca -->
                    <section class="content-section collaboration-section">
                        <div class="section-header">
                            <h2>Współpraca</h2>
                            <span class="section-subtitle">Osoby, z którymi współpracuję</span>
                        </div>
                        <div class="collaboration-grid">
                            <div class="collaborator-card">
                                <div class="collaborator-avatar">
                                    <img src="../photos/sample_person2.png" alt="Jan Kowalski">
                                </div>
                                <div class="collaborator-info">
                                    <h4>Jan Kowalski</h4>
                                    <p>Wspólnie w: EcoFuture, CodeMentor</p>
                                </div>
                            </div>
                            <div class="collaborator-card">
                                <div class="collaborator-avatar">
                                    <img src="../photos/sample_person3.png" alt="Maria Wiśniewska">
                                </div>
                                <div class="collaborator-info">
                                    <h4>Maria Wiśniewska</h4>
                                    <p>Wspólnie w: EcoFuture</p>
                                </div>
                            </div>
                            <div class="collaborator-card">
                                <div class="collaborator-avatar">
                                    <img src="../photos/sample_person4.png" alt="Piotr Nowak">
                                </div>
                                <div class="collaborator-info">
                                    <h4>Piotr Nowak</h4>
                                    <p>Wspólnie w: CodeMentor</p>
                                </div>
                            </div>
                            <div class="collaborator-card">
                                <div class="collaborator-avatar">
                                    <img src="../photos/sample_person5.png" alt="Katarzyna Zielińska">
                                </div>
                                <div class="collaborator-info">
                                    <h4>Katarzyna Zielińska</h4>
                                    <p>Wspólnie w: GreenCity</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Prawa kolumna - sidebar -->
                <div class="sidebar-column">
                    <!-- 📎 Linki użytkownika -->
                    <div class="sidebar-card links-card">
                        <h3>Linki</h3>
                        <div class="links-list">
                            <a href="#" class="profile-link">
                                <span class="link-icon">💼</span>
                                <span class="link-text">Portfolio</span>
                            </a>
                            <a href="#" class="profile-link">
                                <span class="link-icon">💻</span>
                                <span class="link-text">GitHub</span>
                            </a>
                            <a href="#" class="profile-link">
                                <span class="link-icon">📸</span>
                                <span class="link-text">Instagram</span>
                            </a>
                            <a href="#" class="profile-link">
                                <span class="link-icon">🏫</span>
                                <span class="link-text">Strona szkoły</span>
                            </a>
                            <a href="#" class="profile-link">
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
                                <span class="stat-number">12</span>
                                <span class="stat-label">Projektów</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">156</span>
                                <span class="stat-label">Obserwujących</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">89</span>
                                <span class="stat-label">Obserwowanych</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">342</span>
                                <span class="stat-label">Polubienia</span>
                            </div>
                        </div>
                    </div>

                    <!-- 🖼️ Galeria (opcjonalna) -->
                    <div class="sidebar-card gallery-card">
                        <h3>Galeria</h3>
                        <div class="gallery-grid">
                            <div class="gallery-item">
                                <img src="../photos/project-sample.jpg" alt="Projekt 1">
                            </div>
                            <div class="gallery-item">
                                <img src="../photos/project-sample2.jpg" alt="Projekt 2">
                            </div>
                            <div class="gallery-item">
                                <img src="../photos/project-sample3.jpg" alt="Projekt 3">
                            </div>
                            <div class="gallery-item">
                                <img src="../photos/project-sample4.jpg" alt="Projekt 4">
                            </div>
                        </div>
                        <button class="btn-secondary btn-full">Zobacz więcej</button>
                    </div>

                    <!-- ⚠️ Sekcja niepubliczna (tylko dla właściciela) -->
                    <div class="sidebar-card private-card" id="privateSection" style="display: none;">
                        <h3>Twoje konto</h3>
                        <div class="private-actions">
                            <button class="private-btn">✏️ Edytuj profil</button>
                            <button class="private-btn">⚙️ Ustawienia konta</button>
                            <button class="private-btn">🔐 Zmień hasło</button>
                            <button class="private-btn danger">🚪 Wyloguj się</button>
                        </div>
                        <div class="private-info">
                            <p><strong>Email:</strong> anna...@gmail.com</p>
                            <p><strong>Ostatnie logowanie:</strong> Dzisiaj, 14:30</p>
                        </div>
                    </div>

                    <!-- 🎯 Ostatnia aktywność -->
                    <div class="sidebar-card recent-activity-card">
                        <h3>Ostatnia aktywność</h3>
                        <div class="recent-activities">
                            <div class="recent-activity">
                                <span class="activity-badge new">Nowe</span>
                                <p>Nowy komentarz w projekcie EcoFuture</p>
                                <span class="activity-time">10 min temu</span>
                            </div>
                            <div class="recent-activity">
                                <p>Projekt CodeMentor otrzymał 5 nowych polubień</p>
                                <span class="activity-time">2 godziny temu</span>
                            </div>
                            <div class="recent-activity">
                                <p>Maria zaakceptowała Twoje zaproszenie do zespołu</p>
                                <span class="activity-time">Wczoraj</span>
                            </div>
                        </div>
                    </div>
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

    <script src="../scripts/profile.js"></script>
</body>

</html>