<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$hostname = "localhost";
$username = "root";
$password = "";
$database = "teencollab";

$conn = new mysqli($hostname, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['fullName'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $age = $_POST['age'] ?? '';
    $school = $_POST['school'] ?? '';
    $interests = $_POST['interests'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $goals = $_POST['goals'] ?? '';
    $acceptedTerms = isset($_POST['terms']) ? 1 : 0;
    $acceptedPrivacy = isset($_POST['privacy']) ? 1 : 0;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;

    // Walidacja podstawowa
    if (empty($fullName) || empty($email) || empty($password) || empty($goals)) {
        echo "<script>alert('Uzupełnij wymagane pola!');</script>";
        exit();
    }

    // Sprawdzenie unikalności email
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo "<script>alert('Ten email jest już używany!');</script>";
        exit();
    }

    // Hashowanie hasła
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Wstawienie do bazy
    $stmt = $conn->prepare("
        INSERT INTO users 
        (full_name, email, password_hash, age_class, school, interests, experience, goals, accepted_terms, accepted_privacy, newsletter, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        die("Błąd przygotowania zapytania: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssssssiii",
        $fullName,
        $email,
        $hashedPassword,
        $age,
        $school,
        $interests,
        $experience,
        $goals,
        $acceptedTerms,
        $acceptedPrivacy,
        $newsletter
    );

    if ($stmt->execute()) {
        echo "<script>alert('Rejestracja zakończona sukcesem! Możesz się teraz zalogować.');</script>";
    } else {
        echo "Błąd SQL: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dołącz do nas - TeenCollab</title>
    <meta name="description" content="Dołącz do społeczności TeenCollab i razem z nami twórz przyszłość!">
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/join_style.css">
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
                    <li class="nav-cta"><a href="dolacz.html" class="cta-button active">Dołącz</a></li>
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
                <h1 class="hero-title">Dołącz do TeenCollab</h1>
                <p class="hero-subtitle">Rozwijaj umiejętności, współpracuj z innymi i twórz projekty, które zmieniają
                    świat!</p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">500+</span>
                        <span class="stat-label">Członków</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">100+</span>
                        <span class="stat-label">Projektów</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">∞</span>
                        <span class="stat-label">Możliwości</span>
                    </div>
                </div>
            </div>
            <div class="hero-gradient"></div>
        </section>

        <!-- Wybór opcji -->
        <section class="auth-choice">
            <div class="container">
                <h2 class="section-title">Wybierz sposób dołączenia</h2>
                <p class="section-subtitle">Dołącz do naszej społeczności - wybierz odpowiednią opcję dla siebie</p>

                <div class="choice-cards">
                    <div class="choice-card" id="registerChoice">
                        <div class="choice-icon">🚀</div>
                        <h3>Nowe konto</h3>
                        <p>Nie masz jeszcze konta? Załóż je teraz i dołącz do naszej społeczności!</p>
                        <ul class="choice-features">
                            <li>Dostęp do wszystkich projektów</li>
                            <li>Możliwość współpracy z innymi</li>
                            <li>Wsparcie mentorów</li>
                            <li>Certyfikaty udziału</li>
                        </ul>
                        <button class="choice-button primary">Załóż konto</button>
                    </div>

                    <div class="choice-card" id="loginChoice">
                        <div class="choice-icon">🔐</div>
                        <h3>Mam już konto</h3>
                        <p>Posiadasz już konto w naszej społeczności? Zaloguj się poniżej.</p>
                        <ul class="choice-features">
                            <li>Szybki dostęp do projektów</li>
                            <li>Kontynuuj pracę nad zadaniami</li>
                            <li>Sprawdź postępy</li>
                            <li>Połącz się z zespołem</li>
                        </ul>
                        <button class="choice-button secondary">Zaloguj się</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Formularz rejestracji -->
        <section class="application-form register-form" id="registerForm" style="display: none;">
            <div class="container">
                <div class="form-header">
                    <h2 class="section-title">Załóż nowe konto</h2>
                    <p class="section-subtitle">Wypełnij formularz, aby dołączyć do naszej społeczności</p>
                    <button class="back-button" onclick="showChoice()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" />
                        </svg>
                        Wróć do wyboru
                    </button>
                </div>

                <form class="join-form" id="joinForm" method="post" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fullName">Imię i nazwisko *</label>
                            <input type="text" id="fullName" name="fullName" required>
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Hasło *</label>
                            <input type="password" id="password" name="password" required>
                            <small class="input-hint">Minimum 8 znaków</small>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Potwierdź hasło *</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                        </div>

                        <div class="form-group">
                            <label for="age">Wiek / Klasa</label>
                            <input type="text" id="age" name="age" placeholder="np. 16 lat / 2 klasa technikum">
                        </div>

                        <div class="form-group">
                            <label for="school">Szkoła</label>
                            <input type="text" id="school" name="school" placeholder="Nazwa Twojej szkoły">
                        </div>

                        <div class="form-group">
                            <label for="interests">Twoje zainteresowania</label>
                            <select id="interests" name="interests">
                                <option value="">Wybierz obszar zainteresowań</option>
                                <option value="programming">Programowanie</option>
                                <option value="design">Design/UX</option>
                                <option value="ecology">Ekologia</option>
                                <option value="social">Projekty społeczne</option>
                                <option value="other">Inne</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="experience">Doświadczenie (opcjonalnie)</label>
                            <textarea id="experience" name="experience" rows="3"
                                placeholder="Opisz swoje dotychczasowe doświadczenie, umiejętności lub projekty..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="goals">Twoje cele w projekcie *</label>
                            <textarea id="goals" name="goals" rows="3"
                                placeholder="Czego chcesz się nauczyć? Jakie projekty Cię interesują?"
                                required></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label class="checkbox-label">
                                <input type="checkbox" id="terms" name="terms" required>
                                <span class="checkmark"></span>
                                Akceptuję <a href="#" class="link">regulamin</a> projektu TeenCollab *
                            </label>
                        </div>

                        <div class="form-group full-width">
                            <label class="checkbox-label">
                                <input type="checkbox" id="privacy" name="privacy" required>
                                <span class="checkmark"></span>
                                Akceptuję <a href="#" class="link">politykę prywatności</a> *
                            </label>
                        </div>

                        <div class="form-group full-width">
                            <label class="checkbox-label">
                                <input type="checkbox" id="newsletter" name="newsletter">
                                <span class="checkmark"></span>
                                Chcę otrzymywać informacje o nowych projektach i wydarzeniach
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="submit-button">
                        <span>Załóż konto i dołącz</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </button>
                </form>
            </div>
        </section>

        <!-- Formularz logowania -->
        <section class="application-form login-form" id="loginForm" style="display: none;">
            <div class="container">
                <div class="form-header">
                    <h2 class="section-title">Zaloguj się</h2>
                    <p class="section-subtitle">Witaj z powrotem! Zaloguj się do swojego konta</p>
                    <button class="back-button" onclick="showChoice()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" />
                        </svg>
                        Wróć do wyboru
                    </button>
                </div>

                <form class="join-form" id="loginFormData">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="loginEmail">E-mail *</label>
                            <input type="email" id="loginEmail" name="loginEmail" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="loginPassword">Hasło *</label>
                            <input type="password" id="loginPassword" name="loginPassword" required>
                            <div class="form-options">
                                <label class="checkbox-label small">
                                    <input type="checkbox" id="remember" name="remember">
                                    <span class="checkmark"></span>
                                    Zapamiętaj mnie
                                </label>
                                <a href="#" class="link">Zapomniałeś hasła?</a>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-button">
                        <span>Zaloguj się</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" />
                        </svg>
                    </button>

                    <div class="form-footer">
                        <p>Nie masz jeszcze konta? <a href="#" class="link" onclick="showRegister()">Załóż je tutaj</a>
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <!-- Informacje dodatkowe -->
        <section class="benefits-section">
            <div class="container">
                <h2 class="section-title">Co zyskujesz dołączając do nas?</h2>

                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">🎓</div>
                        <h3>Praktyczne doświadczenie</h3>
                        <p>Ucz się przez działanie - pracuj nad realnymi projektami i zdobywaj cenne doświadczenie</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">📜</div>
                        <h3>Certyfikaty udziału</h3>
                        <p>Otrzymuj certyfikaty potwierdzające Twoje zaangażowanie i zdobyte umiejętności</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">👥</div>
                        <h3>Praca w zespole</h3>
                        <p>Naucz się współpracy w międzyszkolnych zespołach pod okiem doświadczonych mentorów</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">🌍</div>
                        <h3>Realny wpływ</h3>
                        <p>Twórz projekty, które mają prawdziwy wpływ na lokalną społeczność i środowisko</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Terminy i kontakt -->
        <section class="info-section">
            <div class="container">
                <div class="info-grid">
                    <div class="info-card">
                        <h3>📅 Terminy rekrutacji</h3>
                        <div class="info-content">
                            <p><strong>Rekrutacja ciągła</strong> - możesz dołączyć w dowolnym momencie!</p>
                            <ul class="info-list">
                                <li>Spotkania zespołów: co tydzień</li>
                                <li>Warsztaty: raz w miesiącu</li>
                                <li>Projekty długoterminowe: start co kwartał</li>
                            </ul>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>📞 Kontakt</h3>
                        <div class="info-content">
                            <p><strong>Koordynator projektu</strong></p>
                            <div class="contact-info">
                                <p>Anna Kowalska</p>
                                <p>📧 <a href="mailto:anna@teencollab.pl">anna@teencollab.pl</a></p>
                                <p>📱 +48 123 456 789</p>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3>💡 Najczęstsze pytania</h3>
                        <div class="info-content">
                            <details class="faq-item">
                                <summary>Czy potrzebuję doświadczenia w programowaniu?</summary>
                                <p>Nie! Wystarczą chęci do nauki. Oferujemy wsparcie mentorów na każdym etapie.</p>
                            </details>
                            <details class="faq-item">
                                <summary>Ile czasu trzeba poświęcić?</summary>
                                <p>Około 2-5 godzin tygodniowo, w zależności od zaangażowania w projekty.</p>
                            </details>
                            <details class="faq-item">
                                <summary>Czy udział jest płatny?</summary>
                                <p>Nie, udział w projekcie jest całkowicie bezpłatny.</p>
                            </details>
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

    <script>
        // Funkcje przełączania między formularzami
        function showRegister() {
            document.querySelector('.auth-choice').style.display = 'none';
            document.querySelector('.register-form').style.display = 'block';
            document.querySelector('.login-form').style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showLogin() {
            document.querySelector('.auth-choice').style.display = 'none';
            document.querySelector('.register-form').style.display = 'none';
            document.querySelector('.login-form').style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showChoice() {
            document.querySelector('.auth-choice').style.display = 'block';
            document.querySelector('.register-form').style.display = 'none';
            document.querySelector('.login-form').style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Burger menu
        const burgerMenu = document.getElementById('burger-menu');
        const navMenu = document.querySelector('.nav-menu');

        burgerMenu.addEventListener('click', () => {
            burgerMenu.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Event listeners dla przycisków wyboru
        document.getElementById('registerChoice').addEventListener('click', showRegister);
        document.getElementById('loginChoice').addEventListener('click', showLogin);

        // Obsługa formularza rejestracji
        const joinForm = document.getElementById('joinForm');

        joinForm.addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Walidacja hasła
            if (password.length < 8) {
                e.preventDefault();
                alert('Hasło musi mieć minimum 8 znaków!');
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Hasła nie są takie same!');
                return;
            }

            // Formularz może wysłać się normalnie do PHP
        });

        // Obsługa formularza logowania
        const loginForm = document.getElementById('loginFormData');
        loginForm.addEventListener('submit', function (e) {
            // e.preventDefault();

            const formData = new FormData(this);
            const formObject = Object.fromEntries(formData);

            console.log('Formularz logowania wysłany:', formObject);
            alert('Zalogowano pomyślnie! Przenoszenie do panelu użytkownika...');
            // Tutaj można dodać przekierowanie do dashboardu
        });

        // Płynne przewijanie do formularza
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                // e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
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
        document.querySelectorAll('.benefit-card, .info-card, .choice-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Dodaj opóźnienia dla lepszego efektu
        document.querySelectorAll('.benefit-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });

        document.querySelectorAll('.choice-card').forEach((card, index) => {
            card.style.transitionDelay = `${index * 0.1}s`;
        });
    </script>
</body>

</html>