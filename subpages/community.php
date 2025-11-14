<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Społeczność - TeenCollab</title>
    <meta name="description" content="Poznaj naszą niesamowitą społeczność młodych twórców i dołącz do nas!">
    <link rel="shortcut icon" href="photos/website-logo.jpg" type="image/x-icon">
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
                    <li><a href="index.html">Strona główna</a></li>
                    <li><a href="projekty.html">Projekty</a></li>
                    <li><a href="społeczność.html" class="active">Społeczność</a></li>
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
                <h1 class="hero-title">Nasza Społeczność</h1>
                <p class="hero-subtitle">Poznaj młodych twórców, którzy razem zmieniają świat na lepsze! 🌟</p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Aktywnych członków</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">100+</span>
                        <span class="stat-label">Zrealizowanych projektów</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">50+</span>
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
                <p class="section-subtitle">Poznaj naszych topowych aktywistów, którzy prowadzą najważniejsze inicjatywy</p>
                
                <div class="creators-grid">
                    <article class="creator-card featured">
                        <div class="creator-badge">🥇 #1</div>
                        <div class="creator-image">
                            <img src="../photos/creator-1.jpg" alt="Anna Kowalska - Liderka projektów ekologicznych">
                        </div>
                        <div class="creator-content">
                            <h3>Anna Kowalska</h3>
                            <span class="creator-role">Liderka Projektów Ekologicznych</span>
                            <p class="creator-achievements">Zorganizowała 15 akcji sprzątania lasów, zaangażowała 200+ wolontariuszy, zdobyła grant na rozwój zielonych inicjatyw</p>
                            <div class="creator-stats">
                                <div class="stat">
                                    <span class="number">15</span>
                                    <span class="label">Projektów</span>
                                </div>
                                <div class="stat">
                                    <span class="number">200+</span>
                                    <span class="label">Wolontariuszy</span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="creator-card featured">
                        <div class="creator-badge">🥈 #2</div>
                        <div class="creator-image">
                            <img src="../photos/creator-2.jpg" alt="Michał Nowak - Specjalista IT">
                        </div>
                        <div class="creator-content">
                            <h3>Michał Nowak</h3>
                            <span class="creator-role">Specjalista IT & Mentor</span>
                            <p class="creator-achievements">Stworzył platformę do koordynacji wolontariatu, przeszkolił 50+ osób z podstaw programowania, lider tech community</p>
                            <div class="creator-stats">
                                <div class="stat">
                                    <span class="number">8</span>
                                    <span class="label">Warsztatów</span>
                                </div>
                                <div class="stat">
                                    <span class="number">50+</span>
                                    <span class="label">Uczestników</span>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="creator-card featured">
                        <div class="creator-badge">🥉 #3</div>
                        <div class="creator-image">
                            <img src="../photos/creator-3.jpg" alt="Katarzyna Wiśniewska - Koordynatorka społeczna">
                        </div>
                        <div class="creator-content">
                            <h3>Katarzyna Wiśniewska</h3>
                            <span class="creator-role">Koordynatorka Projektów Społecznych</span>
                            <p class="creator-achievements">Zorganizowała pomoc dla 100+ seniorów, stworzyła program mentoringowy dla młodzieży, koordynowała 10 dużych eventów</p>
                            <div class="creator-stats">
                                <div class="stat">
                                    <span class="number">12</span>
                                    <span class="label">Inicjatyw</span>
                                </div>
                                <div class="stat">
                                    <span class="number">100+</span>
                                    <span class="label">Seniorów</span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Młode talenty -->
        <section class="young-talents">
            <div class="container">
                <h2 class="section-title">Młode Talenty</h2>
                <p class="section-subtitle">Świeża energia i nowe pomysły - przyszłość naszej społeczności</p>
                
                <div class="talents-grid">
                    <article class="talent-card">
                        <div class="talent-image">
                            <img src="../photos/talent-1.jpg" alt="Julia Nowak - młoda programistka">
                        </div>
                        <div class="talent-content">
                            <h3>Julia Nowak</h3>
                            <span class="talent-join-date">Dołączyła 3 miesiące temu</span>
                            <blockquote class="talent-quote">
                                "Chcę rozwijać swoje umiejętności front-endowe i tworzyć aplikacje, które pomagają lokalnym społecznościom"
                            </blockquote>
                            <div class="talent-goals">
                                <span class="goal-tag">🎯 Front-end</span>
                                <span class="goal-tag">🎯 UX Design</span>
                                <span class="goal-tag">🎯 JavaScript</span>
                            </div>
                        </div>
                    </article>

                    <article class="talent-card">
                        <div class="talent-image">
                            <img src="../photos/talent-2.jpg" alt="Piotr Kowalski - młody aktywista">
                        </div>
                        <div class="talent-content">
                            <h3>Piotr Kowalski</h3>
                            <span class="talent-join-date">Dołączył 2 miesiące temu</span>
                            <blockquote class="talent-quote">
                                "Interesuje mnie ekologia i zrównoważony rozwój. Chcę organizować akcje edukacyjne w szkołach"
                            </blockquote>
                            <div class="talent-goals">
                                <span class="goal-tag">🎯 Ekologia</span>
                                <span class="goal-tag">🎯 Edukacja</span>
                                <span class="goal-tag">🎯 Eventy</span>
                            </div>
                        </div>
                    </article>

                    <article class="talent-card">
                        <div class="talent-image">
                            <img src="../photos/talent-3.jpg" alt="Alicja Zielińska - młoda projektantka">
                        </div>
                        <div class="talent-content">
                            <h3>Alicja Zielińska</h3>
                            <span class="talent-join-date">Dołączyła 1 miesiąc temu</span>
                            <blockquote class="talent-quote">
                                "Uwielbiam grafiki i social media. Chcę pomagać w promocji projektów i dotrzeć do większej liczby młodych ludzi"
                            </blockquote>
                            <div class="talent-goals">
                                <span class="goal-tag">🎯 Grafika</span>
                                <span class="goal-tag">🎯 Social Media</span>
                                <span class="goal-tag">🎯 Marketing</span>
                            </div>
                        </div>
                    </article>

                    <article class="talent-card">
                        <div class="talent-image">
                            <img src="../photos/talent-4.jpg" alt="Mateusz Lewandowski - młody organizator">
                        </div>
                        <div class="talent-content">
                            <h3>Mateusz Lewandowski</h3>
                            <span class="talent-join-date">Dołączył 4 miesiące temu</span>
                            <blockquote class="talent-quote">
                                "Chcę rozwijać swoje umiejętności leadershipowe i organizować projekty, które realnie zmieniają moje miasto"
                            </blockquote>
                            <div class="talent-goals">
                                <span class="goal-tag">🎯 Leadership</span>
                                <span class="goal-tag">🎯 Organizacja</span>
                                <span class="goal-tag">🎯 Społeczność</span>
                            </div>
                        </div>
                    </article>
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
                        <p class="highlight-number">150</p>
                        <p class="highlight-text">nowych członków dołączyło w ostatnim roku</p>
                    </div>

                    <div class="highlight-card">
                        <div class="highlight-icon">💡</div>
                        <h3>Innowacyjne pomysły</h3>
                        <p class="highlight-number">45</p>
                        <p class="highlight-text">nowych projektów rozpoczętych w tym roku</p>
                    </div>


                    <div class="highlight-card">
                        <div class="highlight-icon">🌍</div>
                        <h3>Globalny zasięg</h3>
                        <p class="highlight-number">5</p>
                        <p class="highlight-text">międzynarodowych partnerstw nawiązanych</p>
                    </div>
                </div>

                <div class="monthly-stats">
                    <div class="stat-item">
                        <span class="stat-value">78%</span>
                        <span class="stat-label monthly">Aktywnych członków</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">92%</span>
                        <span class="stat-label monthly">Zadowolonych uczestników</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">15k+</span>
                        <span class="stat-label monthly">Godzin wolontariatu</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">25</span>
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
                    <p>Dołącz do tysięcy młodych ludzi, którzy razem tworzą lepszą przyszłość. Nie ma znaczenia, czy dopiero zaczynasz, czy masz już doświadczenie - każdy znajdzie tu swoje miejsce!</p>
                    <div class="cta-buttons">
                        <a href="dolacz.html" class="cta-button primary">Dołącz do nas!</a>
                        <a href="projekty.html" class="cta-button secondary">Zobacz projekty</a>
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