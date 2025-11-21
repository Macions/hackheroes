<?php
session_start();

$nav_cta_action = '';
// Sprawdzenie, czy użytkownik jest zalogowany
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $firstName = $_SESSION['first_name'] ?? 'Użytkownik';
    $userId = $_SESSION['user_id'] ?? 0;
    $userAvatar = $_SESSION['user_avatar'] ?? 'default-avatar.png';

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
} else {
    $nav_cta_action = '<li class="nav-cta"><a href="dolacz.html" class="cta-button active">Dołącz</a></li>';
}

?>
