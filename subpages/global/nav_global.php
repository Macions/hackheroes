<?php
$currentPage = basename($_SERVER['PHP_SELF']);

if ($currentPage == 'index.php') {
    include("subpages/global/connection.php");
    $urlToAvatarPhoto = 'photos/avatars/default_avatar.png';
} else {
    include("global/connection.php");
    $urlToAvatarPhoto = '../photos/avatars/default_avatar.png';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nav_cta_action = '';
$prefix = ($currentPage == 'index.php') ? 'subpages/' : '';

// Sprawdzenie, czy użytkownik jest zalogowany
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $firstName = $_SESSION['first_name'] ?? 'Użytkownik';
    $userId = $_SESSION['user_id'] ?? 0;

    // Domyślny avatar
    $userAvatar = $urlToAvatarPhoto;

    if ($userId) {
        $stmt = $conn->prepare("SELECT avatar, first_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!empty($row['avatar'])) {
            $userAvatar = $row['avatar'];
            if ($currentPage == 'index.php') {
                $userAvatar = str_replace('../', '', $userAvatar);
            }
        }

        if (!empty($row['first_name'])) {
            $firstName = $row['first_name'];
        }

        $stmt->close();
    }

    $nav_cta_action = <<<HTML
<li class="nav-user-dropdown">
    <div class="user-menu-trigger">
        <img src="$userAvatar" alt="Avatar" class="user-avatar">
        <span class="user-greeting">Cześć, $firstName!</span>
        <span class="dropdown-arrow">▼</span>
    </div>
    <div class="user-dropdown-menu">
        <a href="{$prefix}account.php" class="dropdown-item">
            <span class="dropdown-icon">👤</span> Mój profil
        </a>
        <div class="dropdown-divider"></div>
        <a href="{$prefix}logout.php" class="dropdown-item logout-item">
            <span class="dropdown-icon">🚪</span> Wyloguj się
        </a>
    </div>
</li>
HTML;
} else {
    $nav_cta_action = '<li class="nav-cta"><a href="' . $prefix . 'join.php" class="cta-button active">Dołącz</a></li>';
}
