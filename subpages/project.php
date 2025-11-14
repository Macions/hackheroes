<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoFuture - Projekt | TeenCollab</title>
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
        <!-- 🧠 Hero Section -->
        <section class="project-hero">
            <div class="container">
                <div class="hero-content">
                    <div class="hero-image">
                        <img src="../photos/project-sample.jpg" alt="EcoFuture - projekt ekologiczny">
                    </div>
                    <div class="hero-info">
                        <div class="project-status status-active">
                            <span class="status-dot"></span>
                            Aktywny
                        </div>
                        <h1 class="project-title">EcoFuture</h1>
                        <p class="project-tagline">Innowacyjna platforma edukacyjna promująca zrównoważony rozwój wśród młodzieży</p>
                        
                        <div class="project-categories">
                            <span class="category-tag">🌱 Ekologia</span>
                            <span class="category-tag">💻 Technologia</span>
                            <span class="category-tag">🎓 Edukacja</span>
                        </div>
                        
                        <div class="hero-actions">
                            <button class="btn-primary btn-join" id="joinProjectBtn">
                                <span>Dołącz do projektu</span>
                            </button>
                            <button class="btn-secondary">
                                <span>❤️ Obserwuj</span>
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
                                <img src="../photos/sample_person.png" alt="Anna Nowak">
                            </div>
                            <div class="creator-info">
                                <h3 class="creator-name">Anna Nowak</h3>
                                <p class="creator-role">Założycielka projektu</p>
                                <div class="creator-meta">
                                    <span class="meta-item">📅 Projekt utworzony: 15.01.2025</span>
                                    <span class="meta-item">👥 3 członków zespołu</span>
                                </div>
                                <a href="profil.html" class="creator-link">Zobacz profil twórcy →</a>
                            </div>
                        </div>
                    </section>

                    <!-- 📝 Pełny opis projektu -->
                    <section class="content-section description-section">
                        <div class="section-header">
                            <h2>O projekcie</h2>
                        </div>
                        <div class="project-description">
                            <h3>🌍 Problem, który rozwiązujemy</h3>
                            <p>Młodzież często czuje się bezsilna wobec zmian klimatycznych. Brakuje platform, które w przystępny sposób edukują i dają konkretne narzędzia do działania.</p>
                            
                            <h3>💡 Nasze rozwiązanie</h3>
                            <p>EcoFuture to interaktywna platforma z gamifikacją, która:</p>
                            <ul>
                                <li>Uczy przez zabawę - questy i wyzwania ekologiczne</li>
                                <li>Łączy społeczność - wspólne akcje i projekty</li>
                                <li>Daje realny wpływ - tracking zmniejszonego śladu węglowego</li>
                            </ul>
                            
                            <h3>🛠️ Technologie</h3>
                            <div class="tech-stack">
                                <span class="tech-tag">React</span>
                                <span class="tech-tag">Node.js</span>
                                <span class="tech-tag">MongoDB</span>
                                <span class="tech-tag">Figma</span>
                            </div>
                            
                            <h3>🚀 Plany rozwoju</h3>
                            <p>Chcemy dotrzeć do 10,000 użytkowników w ciągu roku i zorganizować 50 lokalnych akcji sprzątania świata.</p>
                        </div>
                    </section>

                    <!-- 🎯 Cele projektu -->
                    <section class="content-section goals-section">
                        <div class="section-header">
                            <h2>Cele projektu</h2>
                            <span class="section-subtitle">Śledzimy nasz progres!</span>
                        </div>
                        <div class="goals-list">
                            <div class="goal-item">
                                <div class="goal-header">
                                    <span class="goal-icon">🎯</span>
                                    <span class="goal-text">Przygotować prototyp platformy</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 75%"></div>
                                </div>
                                <span class="progress-text">75% ukończono</span>
                            </div>
                            
                            <div class="goal-item">
                                <div class="goal-header">
                                    <span class="goal-icon">👥</span>
                                    <span class="goal-text">Zebrać 5-osobowy zespół</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 60%"></div>
                                </div>
                                <span class="progress-text">3/5 osób</span>
                            </div>
                            
                            <div class="goal-item">
                                <div class="goal-header">
                                    <span class="goal-icon">🌐</span>
                                    <span class="goal-text">Stworzyć landing page</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 20%"></div>
                                </div>
                                <span class="progress-text">20% ukończono</span>
                            </div>
                        </div>
                    </section>

                    <!-- 🔧 Lista zadań -->
                    <section class="content-section tasks-section">
                        <div class="section-header">
                            <h2>Zadania do wykonania</h2>
                            <div class="task-filters">
                                <button class="filter-btn active" data-filter="all">Wszystkie</button>
                                <button class="filter-btn" data-filter="open">Otwarte</button>
                                <button class="filter-btn" data-filter="in-progress">W trakcie</button>
                                <button class="filter-btn" data-filter="done">Zrobione</button>
                            </div>
                        </div>
                        <div class="tasks-list">
                            <div class="task-card" data-status="open" data-priority="high">
                                <div class="task-main">
                                    <h3 class="task-title">Projekt interfejsu użytkownika</h3>
                                    <p class="task-description">Stworzyć wireframe'y i mockupy głównych ekranów aplikacji</p>
                                </div>
                                <div class="task-meta">
                                    <span class="task-priority priority-high">Wysoki</span>
                                    <span class="task-status status-open">Otwarte</span>
                                    <span class="task-deadline">📅 Do 28.02.2025</span>
                                </div>
                            </div>
                            
                            <div class="task-card" data-status="in-progress" data-priority="medium">
                                <div class="task-main">
                                    <h3 class="task-title">Backend API</h3>
                                    <p class="task-description">Implementacja endpointów dla użytkowników i questów</p>
                                </div>
                                <div class="task-meta">
                                    <span class="task-priority priority-medium">Średni</span>
                                    <span class="task-status status-in-progress">W trakcie</span>
                                    <span class="task-assignee">👤 Anna Nowak</span>
                                </div>
                            </div>
                            
                            <div class="task-card" data-status="done" data-priority="low">
                                <div class="task-main">
                                    <h3 class="task-title">Research konkurencji</h3>
                                    <p class="task-description">Analiza istniejących rozwiązań ekologicznych</p>
                                </div>
                                <div class="task-meta">
                                    <span class="task-priority priority-low">Niski</span>
                                    <span class="task-status status-done">Zrobione</span>
                                    <span class="task-completed">✅ Ukończono 10.01.2025</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 👥 Zespół projektu -->
                    <section class="content-section team-section">
                        <div class="section-header">
                            <h2>Nasz zespół</h2>
                            <span class="section-subtitle">Dołącz do nas!</span>
                        </div>
                        <div class="team-grid">
                            <div class="team-member-card">
                                <div class="member-avatar">
                                    <img src="../photos/sample_person.png" alt="Anna Nowak">
                                </div>
                                <div class="member-info">
                                    <h3 class="member-name">Anna Nowak</h3>
                                    <p class="member-role">Project Lead & Developer</p>
                                    <span class="member-tenure">W zespole od początku</span>
                                </div>
                            </div>
                            
                            <div class="team-member-card">
                                <div class="member-avatar">
                                    <img src="../photos/sample_person2.png" alt="Jan Kowalski">
                                </div>
                                <div class="member-info">
                                    <h3 class="member-name">Jan Kowalski</h3>
                                    <p class="member-role">UI/UX Designer</p>
                                    <span class="member-tenure">W zespole 2 miesiące</span>
                                </div>
                            </div>
                            
                            <div class="team-member-card">
                                <div class="member-avatar">
                                    <img src="../photos/sample_person3.png" alt="Maria Wiśniewska">
                                </div>
                                <div class="member-info">
                                    <h3 class="member-name">Maria Wiśniewska</h3>
                                    <p class="member-role">Content Specialist</p>
                                    <span class="member-tenure">W zespole 1 miesiąc</span>
                                </div>
                            </div>
                            
                            <div class="team-join-card">
                                <div class="join-icon">➕</div>
                                <h3>Dołącz do zespołu!</h3>
                                <p>Szukamy developerów i ekologów</p>
                                <button class="btn-secondary btn-apply">Aplikuj do projektu</button>
                            </div>
                        </div>
                    </section>

                    <!-- 💬 Sekcja komentarzy -->
                    <section class="content-section comments-section">
                        <div class="section-header">
                            <h2>Dyskusja</h2>
                            <div class="comments-stats">
                                <span class="stat-item">💬 14 komentarzy</span>
                                <span class="stat-item">👁️ 2390 wyświetleń</span>
                            </div>
                        </div>
                        
                        <div class="comment-form">
                            <div class="comment-avatar">
                                <img src="../photos/sample_person.png" alt="Twój avatar">
                            </div>
                            <div class="comment-input-container">
                                <textarea class="comment-input" placeholder="Podziel się swoją opinią lub zadaj pytanie..."></textarea>
                                <div class="comment-actions">
                                    <button class="btn-primary btn-comment">Dodaj komentarz</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="comments-list">
                            <div class="comment">
                                <div class="comment-avatar">
                                    <img src="../photos/sample_person2.png" alt="Jan Kowalski">
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <span class="comment-author">Jan Kowalski</span>
                                        <span class="comment-date">2 godziny temu</span>
                                    </div>
                                    <p class="comment-text">Świetny projekt! Czy planujecie integrację z popularnymi platformami społecznościowymi?</p>
                                    <div class="comment-actions">
                                        <button class="comment-like">❤️ 5</button>
                                        <button class="comment-reply">Odpowiedz</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="comment">
                                <div class="comment-avatar">
                                    <img src="../photos/sample_person3.png" alt="Maria Wiśniewska">
                                </div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <span class="comment-author">Maria Wiśniewska</span>
                                        <span class="comment-date">1 dzień temu</span>
                                    </div>
                                    <p class="comment-text">Bardzo podoba mi się koncepcja gamifikacji w edukacji ekologicznej. Czy mogłabym pomóc w tworzeniu treści?</p>
                                    <div class="comment-actions">
                                        <button class="comment-like">❤️ 8</button>
                                        <button class="comment-reply">Odpowiedz</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 📎 Załączniki -->
                    <section class="content-section attachments-section">
                        <div class="section-header">
                            <h2>Materiały projektu</h2>
                        </div>
                        <div class="attachments-grid">
                            <a href="#" class="attachment-card">
                                <div class="attachment-icon">📋</div>
                                <div class="attachment-info">
                                    <h3>Dokumentacja projektu</h3>
                                    <p>PDF • 2.4 MB</p>
                                </div>
                            </a>
                            
                            <a href="#" class="attachment-card">
                                <div class="attachment-icon">🎨</div>
                                <div class="attachment-info">
                                    <h3>Projekt w Figma</h3>
                                    <p>Link • Ostatnia aktualizacja: wczoraj</p>
                                </div>
                            </a>
                            
                            <a href="#" class="attachment-card">
                                <div class="attachment-icon">💻</div>
                                <div class="attachment-info">
                                    <h3>Kod źródłowy</h3>
                                    <p>GitHub • Publiczny repozytorium</p>
                                </div>
                            </a>
                            
                            <a href="#" class="attachment-card">
                                <div class="attachment-icon">📊</div>
                                <div class="attachment-info">
                                    <h3>Prezentacja</h3>
                                    <p>Google Slides • Dostęp do odczytu</p>
                                </div>
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Prawa kolumna - sidebar -->
                <div class="sidebar-column">
                    <!-- 🏷️ Tagi projektu -->
                    <div class="sidebar-card tags-card">
                        <h3>Tagi projektu</h3>
                        <div class="tags-cloud">
                            <span class="project-tag">edukacja</span>
                            <span class="project-tag">AI</span>
                            <span class="project-tag">społeczność</span>
                            <span class="project-tag">młodzież</span>
                            <span class="project-tag">szkoła</span>
                            <span class="project-tag">ekologia</span>
                            <span class="project-tag">technologia</span>
                            <span class="project-tag">zrównoważony rozwój</span>
                        </div>
                    </div>

                    <!-- ❤️ Reakcje -->
                    <div class="sidebar-card reactions-card">
                        <h3>Reakcje</h3>
                        <div class="reactions-stats">
                            <div class="reaction-item">
                                <span class="reaction-icon">❤️</span>
                                <span class="reaction-count">132</span>
                            </div>
                            <div class="reaction-item">
                                <span class="reaction-icon">👁️</span>
                                <span class="reaction-count">2390</span>
                            </div>
                            <div class="reaction-item">
                                <span class="reaction-icon">💬</span>
                                <span class="reaction-count">14</span>
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
                                <span class="info-value status-active">Aktywny</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Data utworzenia:</span>
                                <span class="info-value">15.01.2025</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Członkowie:</span>
                                <span class="info-value">3 osoby</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Widoczność:</span>
                                <span class="info-value">Publiczny</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ostatnia aktywność:</span>
                                <span class="info-value">2 godziny temu</span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔒 Narzędzia (dla właściciela) -->
                    <div class="sidebar-card tools-card" id="ownerTools" style="display: none;">
                        <h3>Narzędzia projektu</h3>
                        <div class="tools-list">
                            <button class="tool-btn">✏️ Edytuj projekt</button>
                            <button class="tool-btn">👥 Zarządzaj zespołem</button>
                            <button class="tool-btn">✅ Zarządzaj zadaniami</button>
                            <button class="tool-btn danger">🗑️ Usuń projekt</button>
                        </div>
                    </div>

                    <!-- 📅 Nadchodzące wydarzenia -->
                    <div class="sidebar-card events-card">
                        <h3>Nadchodzące wydarzenia</h3>
                        <div class="events-list">
                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day">28</span>
                                    <span class="event-month">LUT</span>
                                </div>
                                <div class="event-info">
                                    <h4>Spotkanie zespołu</h4>
                                    <p>Omówienie postępów</p>
                                </div>
                            </div>
                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day">05</span>
                                    <span class="event-month">MAR</span>
                                </div>
                                <div class="event-info">
                                    <h4>Premiera prototypu</h4>
                                    <p>Testy z użytkownikami</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🚀 Podobne projekty -->
        <section class="similar-projects">
            <div class="container">
                <div class="section-header">
                    <h2>Podobne projekty</h2>
                    <a href="projekty.html" class="see-all-link">Zobacz wszystkie →</a>
                </div>
                <div class="projects-grid">
                    <div class="project-card">
                        <div class="project-image">
                            <img src="../photos/project-sample2.jpg" alt="TechEdu">
                        </div>
                        <div class="project-info">
                            <span class="project-category">💻 Technologia</span>
                            <h3>TechEdu</h3>
                            <p>Platforma do nauki programowania dla młodzieży</p>
                            <div class="project-stats">
                                <span>❤️ 89</span>
                                <span>👥 12</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="project-card">
                        <div class="project-image">
                            <img src="../photos/project-sample3.jpg" alt="GreenCity">
                        </div>
                        <div class="project-info">
                            <span class="project-category">🌱 Ekologia</span>
                            <h3>GreenCity</h3>
                            <p>Aplikacja do zarządzania odpadami w mieście</p>
                            <div class="project-stats">
                                <span>❤️ 156</span>
                                <span>👥 8</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="project-card">
                        <div class="project-image">
                            <img src="../photos/project-sample4.jpg" alt="ArtHub">
                        </div>
                        <div class="project-info">
                            <span class="project-category">🎨 Sztuka</span>
                            <h3>ArtHub</h3>
                            <p>Społeczność młodych artystów i twórców</p>
                            <div class="project-stats">
                                <span>❤️ 234</span>
                                <span>👥 25</span>
                            </div>
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
                <h3>Dołącz do projektu EcoFuture</h3>
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
</body>

</html>