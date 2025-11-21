<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Konfiguracja bazy danych
$hostname = "localhost";
$username = "root";
$password = "";
$database = "teencollab";

$conn = new mysqli($hostname, $username, $password, $database);
if ($conn->connect_error) {
    die("Błąd połączenia z bazą: " . $conn->connect_error);
}

$nav_cta_action = '';
$benefits = 'Co zyskujesz dołączając do nas';

if (isset($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];

    // Przygotowanie zapytania
    $stmt = $conn->prepare("SELECT first_name, avatar FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Błąd przygotowania zapytania: " . $conn->error);
    }

    $stmt->bind_param("s", $email); // 's' bo email jest stringiem
    $stmt->execute();
    $stmt->bind_result($firstName, $userAvatar); // Wartości zostaną przypisane tutaj
    $stmt->fetch();
    $stmt->close();
}


// Funkcja logowania akcji - POPRAWIONA
function logAction($conn, $userId, $email, $action)
{
    // POPRAWIONE: Bezpieczne sprawdzenie istnienia tabeli
    try {
        $tableCheck = $conn->query("SELECT 1 FROM logs LIMIT 1");
        if ($tableCheck === false) {
            // Tabela nie istnieje, pomiś logowanie
            return;
        }
        $tableCheck->close();
    } catch (Exception $e) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // POPRAWIONE: Dostosowane do struktury tabeli logs
    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, email, action, details, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, '', ?, ?, NOW())
    ");

    // Sprawdź czy prepare się powiodło
    if ($stmt === false) {
        error_log("Błąd przygotowania zapytania: " . $conn->error);
        return;
    }

    // POPRAWIONE: 5 parametrów dla 5 placeholderów
    $stmt->bind_param("issss", $userId, $email, $action, $ip, $agent);
    if (!$stmt->execute()) {
        error_log("Błąd wykonania zapytania: " . $stmt->error);
    }
    $stmt->close();
}

/* ============================================================
                        SPRAWDZENIE LOGINU
   ============================================================ */
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {

    $email = $_SESSION['user_email'];

    // POPRAWIONE: Używamy first_name i last_name
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Błąd przygotowania zapytania: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId, $dbFirstName, $dbLastName);
    $stmt->fetch();
    $stmt->close();

    // Użyj first_name z bazy danych
    $firstName = $dbFirstName ?: "Użytkowniku";
    $benefits = "Co u nas zyskujesz?";

    logAction($conn, $userId, $email, 'login');

    $nav_cta_action = <<<HTML
<li class="nav-user-dropdown">
    <div class="user-menu-trigger">
        <img src="$userAvatar" alt="Avatar" class="user-avatar">
        <span class="user-greeting">Cześć, $firstName!</span>
        <span class="dropdown-arrow">▼</span>
    </div>
    <div class="user-dropdown-menu">
        <a href="profil.php?id=$userId" class="dropdown-item">
            <span class="dropdown-icon">👤</span> Mój profil
        </a>
        <a href="konto.php" class="dropdown-item">
            <span class="dropdown-icon">⚙️</span> Ustawienia konta
        </a>
        <div class="dropdown-divider"></div>
        <a href="logout.php" class="dropdown-item logout-item">
            <span class="dropdown-icon">🚪</span> Wyloguj się
        </a>
    </div>
</li>
HTML;

    echo "<script>window.loggedFlag = true;</script>";

} else {

    /* ============================================================
                                REJESTRACJA
       ============================================================ */

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['loginButton'])) {

        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $nick = trim($_POST['nick'] ?? '');
        $phoneNumber = trim($_POST['phone'] ?? ''); // Tutaj jest $phoneNumber
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $age = $_POST['age'] ?? '';
        $school = $_POST['school'] ?? '';
        $interests = $_POST['interests'] ?? '';
        $experience = $_POST['experience'] ?? '';
        $goals = $_POST['goals'] ?? '';
        $acceptedTerms = isset($_POST['terms']) ? 1 : 0;
        $acceptedPrivacy = isset($_POST['privacy']) ? 1 : 0;
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;

        if (empty($firstName) || empty($nick) || empty($lastName) || empty($email) || empty($password) || empty($goals)) {
            echo "<script>alert('Uzupełnij wymagane pola!');</script>";
        } else {

            $check = $conn->prepare("SELECT id FROM users WHERE email=?");
            if ($check === false) {
                die("Błąd przygotowania zapytania: " . $conn->error);
            }

            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                echo "<script>window.emailExistsFlag = true;</script>";
            } else {

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // POPRAWIONE: Używamy first_name i last_name zamiast full_name
                $stmt = $conn->prepare("
                INSERT INTO users 
                (first_name, last_name, nick, email, phone, password_hash, age_class, school, interests, experience, goals, accepted_terms, accepted_privacy, newsletter, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

                if ($stmt === false) {
                    die("Błąd przygotowania zapytania: " . $conn->error);
                }

                // POPRAWIONE: Zmieniono $phone na $phoneNumber - 14 parametrów dla 14 wartości
                $stmt->bind_param(
                    "ssssssssssiiii",
                    $firstName,        // first_name
                    $lastName,         // last_name
                    $nick,             // nick
                    $email,            // email
                    $phoneNumber,      // phone - POPRAWIONE: było $phone, ma być $phoneNumber
                    $hashedPassword,   // password_hash
                    $age,              // age_class
                    $school,           // school
                    $interests,        // interests
                    $experience,       // experience
                    $goals,            // goals
                    $acceptedTerms,    // accepted_terms
                    $acceptedPrivacy,  // accepted_privacy
                    $newsletter        // newsletter
                );

                if ($stmt->execute()) {
                    $newUserId = $stmt->insert_id;
                    logAction($conn, $newUserId, $email, 'registration');

                    echo "<script>alert('Rejestracja udana! Możesz się zalogować.');</script>";
                } else {
                    echo "Błąd SQL: " . $stmt->error;
                }

                $stmt->close();
            }

            $check->close();
        }
    }

    /* ============================================================
                             LOGOWANIE
       ============================================================ */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginButton'])) {

        $email = $_POST['loginEmail'] ?? '';
        $password = $_POST['loginPassword'] ?? '';

        if ($email && $password) {
            // POPRAWIONE: Używamy first_name i last_name
            $stmt = $conn->prepare("SELECT id, password_hash, first_name, last_name FROM users WHERE email=?");
            if ($stmt === false) {
                die("Błąd przygotowania zapytania: " . $conn->error);
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            // POPRAWIONE: Cztery zmienne dla czterech kolumn
            $stmt->bind_result($userId, $hashedPassword, $dbFirstName, $dbLastName);

            if ($stmt->num_rows === 1) {
                $stmt->fetch();

                if (password_verify($password, $hashedPassword)) {

                    $_SESSION['user_email'] = $email;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $userId;

                    $firstName = $dbFirstName; // Ustawiamy first_name dla wyświetlenia

                    logAction($conn, $userId, $email, 'login');

                    echo "<script>alert('Zalogowano!'); window.location.reload();</script>";
                } else {
                    echo "<script>alert('Błędny email lub hasło');</script>";
                }
            } else {
                echo "<script>alert('Błędny email lub hasło');</script>";
            }

            $stmt->close();
        }
    }

    $nav_cta_action = '<li class="nav-cta"><a href="dolacz.html" class="cta-button active">Dołącz</a></li>';
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

        <section class="logged">
            <div class="container">
                <h2 class="section-title">Cześć <?php echo $firstName; ?></h2>
                <p class="section-subtitle">Cieszymy się że jesteś w gronie naszych kreatorów przyszłości!</p>

                <div class="logged-cards">
                    <div class="logged-card">
                        <div class="logged-icon">📋</div>
                        <h3>Twoje projekty</h3>
                        <p>Przeglądaj i zarządzaj swoimi projektami</p>
                        <ul class="logged-features">
                            <li>Przeglądaj swoje projekty</li>
                            <li>Zarządzaj członkami zespołu</li>
                            <li>Śledź postępy prac</li>
                            <li>Dodawaj nowe zadania</li>
                        </ul>
                        <button class="logged-button primary" onclick="window.location.href='projekty.html'">Przejdź do
                            projektów</button>
                    </div>

                    <div class="logged-card">
                        <div class="logged-icon">👥</div>
                        <h3>Społeczność</h3>
                        <p>Połącz się z innymi twórcami i mentorami</p>
                        <ul class="logged-features">
                            <li>Znajdź współpracowników</li>
                            <li>Dołącz do dyskusji</li>
                            <li>Uczestnicz w wydarzeniach</li>
                            <li>Dziel się doświadczeniami</li>
                        </ul>
                        <button class="logged-button primary" onclick="window.location.href='społeczność.html'">Odkryj
                            społeczność</button>
                    </div>

                    <div class="logged-card">
                        <div class="logged-icon">➕</div>
                        <h3>Nowy projekt</h3>
                        <p>Rozpocznij nowy projekt i zgromadź zespół</p>
                        <ul class="logged-features">
                            <li>Stwórz nowy projekt</li>
                            <li>Zdefiniuj cele i zadania</li>
                            <li>Zaprosz członków zespołu</li>
                            <li>Ustal harmonogram</li>
                        </ul>
                        <button class="logged-button secondary"
                            onclick="window.location.href='create-project.html'">Utwórz projekt</button>
                    </div>

                    <div class="logged-card">
                        <div class="logged-icon">👤</div>
                        <h3>Twoje konto</h3>
                        <p>Zarządzaj swoim profilem i ustawieniami</p>
                        <ul class="logged-features">
                            <li>Edytuj profil</li>
                            <li>Zmień hasło</li>
                            <li>Ustawienia powiadomień</li>
                            <li>Twoje osiągnięcia</li>
                        </ul>
                        <button class="logged-button secondary" onclick="window.location.href='account.html'">Przejdź do
                            konta</button>
                    </div>
                </div>
            </div>
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
                            <label for="firstName">Imię *</label>
                            <input type="text" id="firstName" name="firstName" required>
                        </div>

                        <div class="form-group">
                            <label for="lastName">Nazwisko *</label>
                            <input type="text" id="lastName" name="lastName" required>
                        </div>

                        <div class="form-group">
                            <label for="nick">Nick *</label>
                            <input type="text" id="nick" name="nick" required>
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Numer telefonu</label>
                            <input type="phone" id="phone" name="phone">
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
                                <option value="programowanie">Programowanie</option>
                                <option value="grafika_komputerowa">Grafika komputerowa</option>
                                <option value="projektowanie_stron">Projektowanie stron WWW</option>
                                <option value="robotyka">Robotyka</option>
                                <option value="sztuczna_inteligencja">AI i uczenie maszynowe</option>
                                <option value="tworzenie_gier">Tworzenie gier</option>
                                <option value="aplikacje_mobilne">Aplikacje mobilne</option>
                                <option value="nauka_badania">Nauka i badania</option>
                                <option value="ekologia">Ekologia i ochrona środowiska</option>
                                <option value="projekty_spoleczne">Projekty społeczne</option>
                                <option value="edukacja">Edukacja i nauczanie</option>
                                <option value="sztuka">Sztuka i kreatywność</option>
                                <option value="muzyka">Muzyka i dźwięk</option>
                                <option value="fotografia">Fotografia i wideo</option>
                                <option value="biznes">Biznes i przedsiębiorczość</option>
                                <option value="dziennikarstwo">Dziennikarstwo i pisanie</option>
                                <option value="sport">Sport i aktywność fizyczna</option>
                                <option value="wolontariat">Wolontariat i pomoc</option>
                                <option value="inne">Inne</option>
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

                <form class="join-form" id="loginFormData" method="POST">
                    <div class="form-grid">
                        <h6 class="email_exist">Konto o podanym adresie e-mail już istnieje. Zaloguj się.</h4>
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

                    <button type="submit" class="submit-button" name="loginButton">
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
                <h2 class="section-title"><?php echo $benefits; ?></h2>

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
                    <div class="info-card recrutation">
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

        const nickInput = document.getElementById('nick');
        const nickFeedback = document.createElement('small');
        nickFeedback.style.display = 'block';
        nickFeedback.style.marginTop = '4px';
        nickInput.parentNode.appendChild(nickFeedback);

        let nickTimeout = null;

        nickInput.addEventListener('input', function () {
            const nick = this.value.trim();

            if (nickTimeout) clearTimeout(nickTimeout);

            // Małe opóźnienie żeby nie spamować bazy
            nickTimeout = setTimeout(() => {
                if (nick.length < 3) {
                    nickFeedback.textContent = 'Nick za krótki';
                    nickFeedback.style.color = 'red';
                    return;
                }

                fetch(`check_nick.php?nick=${encodeURIComponent(nick)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'taken') {
                            nickFeedback.textContent = 'Ten nick jest już zajęty';
                            nickFeedback.style.color = 'red';
                        } else if (data.status === 'available') {
                            nickFeedback.textContent = 'Ten nick jest dostępny';
                            nickFeedback.style.color = 'green';
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        nickFeedback.textContent = 'Błąd sprawdzania nicku';
                        nickFeedback.style.color = 'red';
                    });
            }, 500);
        });

        // Funkcja wyświetlająca alert o istnieniu emaila
        function emailExist() {
            showLogin();
            let emailAlert = document.querySelector('.email_exist');
            emailAlert.style.display = 'block';
        }

        function loggedFlagF() {
            const loggedSection = document.querySelector('.logged');
            loggedSection.style.display = 'block';

            const authChoice = document.querySelector('.auth-choice');
            let recrutationCard = document.querySelector('.info-card.recrutation');
            recrutationCard.style.display = 'none';
            authChoice.style.display = 'none';
        }

        // Po załadowaniu strony sprawdzamy flagę
        window.addEventListener('DOMContentLoaded', () => {
            if (window.emailExistsFlag) {
                emailExist();

                window.emailExistsFlag = false; // reset
            }
            if (window.loggedFlag) {
                loggedFlagF();
                window.loggedFlag = false; // reset
            }
        });

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
            // alert('Zalogowano pomyślnie! Przenoszenie do panelu użytkownika...');
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