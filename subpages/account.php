<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"]; // musi być liczba


$stmt = $conn->prepare("SELECT first_name, last_name, email, created_at, avatar, nick, phone, verification_status FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $firstName = $user['first_name'];
    $lastName = $user['last_name'];
    $email = $user['email'];
    $joinDate = date("d.m.Y", strtotime($user['created_at']));
    $userAvatar = $user['avatar'];
    $nick = $user['nick'];
    $phone = $user['phone'];
    $verificationStatus = $user['verification_status'];
} else {
    header("Location: join.php");
    exit();
}


$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM project_team WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$amountOfProjects = $result->fetch_assoc()['count'] ?? 0;


$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM project_team WHERE user_id = ? AND role = 'owner'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$amountOfOwnProjects = $result->fetch_assoc()['count'] ?? 0;


$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM badges WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$amountOfBadges = $result->fetch_assoc()['count'] ?? 0;


$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM comments WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_assoc();
$count = $row['count'] ?? 0;

if ($amountOfBadges > 99) {
    $addedCommentsAmmount = '<span title="' . $count . '">99+</span>';
} else {
    $addedCommentsAmmount = '<span>' . $count . '</span>';
}


$engagement = $amountOfProjects + $count; // tutaj dodajesz same liczby



$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM logs WHERE user_id = ? AND action = 'login'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$loginCount = $result->fetch_assoc()['count'] ?? 0;


$stmt = $conn->prepare("SELECT created_at FROM logs WHERE user_id = ? AND action = 'update' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$lastChange = $result->fetch_assoc()['created_at'] ?? 'Brak danych';


$stmt = $conn->prepare("SELECT created_at FROM logs WHERE user_id = ? AND action = 'login' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$lastLogin = $result->fetch_assoc()['created_at'] ?? null;
$stmt->close();

if ($lastLogin) {
    $timeOnly = date('m.d.Y', strtotime($lastLogin));       // godzina:minuta
    $fullDate = date('m.d.Y H:i:s', strtotime($lastLogin)); // pełna data
} else {
    $timeOnly = 'Brak danych';
    $fullDate = '';
}


$stmt = $conn->prepare("SELECT details FROM logs WHERE user_id = ? AND action = 'project_edit' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$lastEditedProject = $result->fetch_assoc()['details'] ?? 'Brak';





$badgesStmt = $conn->prepare("SELECT name, description, emoji FROM badges WHERE user_id = ? ORDER BY created_at DESC");
$badgesStmt->bind_param("i", $userId);
$badgesStmt->execute();
$badgesResult = $badgesStmt->get_result();
$userBadges = $badgesResult->fetch_all(MYSQLI_ASSOC);
$badgesStmt->close();


?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje konto - TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/account_style.css">
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
                    <li><a href="about.php">O projekcie</a></li>
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
        <div class="account-container">
            <section class="profile-header">
                <div class="profile-avatar-section">
                    <div class="avatar-container">
                        <img src="<?php echo $userAvatar; ?>" alt="Twoje zdjęcie profilowe" class="profile-avatar"
                            id="profileAvatar">
                        <button class="change-avatar-btn" onclick="openAvatarModal()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M4 16L8 12L18 22H2L4 16Z" stroke="currentColor" stroke-width="2" />
                                <path d="M15 5L19 9" stroke="currentColor" stroke-width="2" />
                                <path d="M18 2L22 6" stroke="currentColor" stroke-width="2" />
                                <path
                                    d="M21 13V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H11"
                                    stroke="currentColor" stroke-width="2" />
                            </svg>
                            Zmień zdjęcie
                        </button>
                    </div>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo $firstName . " " . $lastName; ?></h1>
                    <p class="profile-role">Kreator Przyszłości</p>
                    <p class="profile-join-date">Dołączył: <?php echo $joinDate; ?></p>
                </div>
            </section>

            <div class="account-layout">
                <div class="account-sidebar">
                    <section class="stats-section">
                        <h2>Statystyki</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $amountOfProjects; ?></span>
                                <span class="stat-label">Projektów</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $amountOfOwnProjects; ?></span>
                                <span class="stat-label">Własnych</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $amountOfBadges; ?></span>
                                <span class="stat-label">Odznak</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $engagement; ?></span>
                                <span class="stat-label">Zaangażowanie</span>
                            </div>
                        </div>
                    </section>

                    <!-- Podsumowanie konta -->
                    <section class="summary-section">
                        <h2>Podsumowanie</h2>
                        <div class="summary-list">
                            <div class="summary-item">
                                <span>Konto utworzone:</span>
                                <span><?php echo $joinDate; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Liczba logowań:</span>
                                <span><?php echo $loginCount; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Ostatnia zmiana:</span>
                                <span><?php echo $lastChange; ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Status weryfikacji:</span>
                                <span class="verified"><?php echo $verificationStatus; ?></span>
                            </div>
                        </div>
                    </section>

                    <!-- Aktywność -->
                    <section class="activity-section">
                        <h2>Ostatnia aktywność</h2>
                        <div class="activity-list">
                            <div class="activity-item">
                                <span>Ostatnie logowanie</span>
                                <span
                                    title="<?= htmlspecialchars($fullDate) ?>"><?= htmlspecialchars($timeOnly) ?></span>
                            </div>
                            <?php

                            ?>
                            <div class="activity-item">
                                <span>Ostatnio edytowany projekt</span>
                                <span><?php echo $lastEditedProject ?></span>
                            </div>
                            <div class="activity-item">
                                <span>Dodane komentarze</span>
                                <?php echo $addedCommentsAmmount; ?>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Główna zawartość -->
                <div class="account-content">
                    <!-- Dane użytkownika -->
                    <section class="data-section">
                        <div class="section-header">
                            <h2>Dane użytkownika</h2>
                        </div>
                        <div class="data-grid">
                            <div class="data-item">
                                <label>Imię i nazwisko</label>
                                <div class="data-value">
                                    <span><?php echo $firstName . " " . $lastName; ?></span>
                                    <button class="edit-btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13"
                                                stroke="currentColor" stroke-width="2" />
                                            <path
                                                d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z"
                                                stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>Nick</label>
                                <div class="data-value">
                                    <span><?php echo $nick; ?></span>
                                    <button class="edit-btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13"
                                                stroke="currentColor" stroke-width="2" />
                                            <path
                                                d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z"
                                                stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>E-mail</label>
                                <div class="data-value">
                                    <span><?php echo $email; ?></span>
                                    <button class="edit-btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13"
                                                stroke="currentColor" stroke-width="2" />
                                            <path
                                                d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z"
                                                stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>Telefon</label>
                                <div class="data-value">
                                    <span><?php echo $phone; ?></span>
                                    <button class="edit-btn">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path
                                                d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13"
                                                stroke="currentColor" stroke-width="2" />
                                            <path
                                                d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z"
                                                stroke="currentColor" stroke-width="2" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>Rola</label>
                                <div class="data-value">
                                    <span>Kreator Przyszłości</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Moje projekty -->
                    <section class="projects-section">
                        <div class="section-header">
                            <h2>Moje projekty</h2>
                        </div>
                        <div class="projects-list">
                            <?php
                            $myProjectsStmt = $conn->prepare("SELECT * FROM projects p JOIN project_team pt ON p.id = pt.project_id WHERE pt.user_id = ?");
                            $myProjectsStmt->bind_param("i", $userId);
                            $myProjectsStmt->execute();
                            $myProjects = $myProjectsStmt->get_result();

                            if ($myProjects->num_rows > 0) {
                                while ($project = $myProjects->fetch_assoc()) {
                                    echo '<div class="project-item">
                                                <a href = "project.php?id=' . htmlspecialchars($project['id']) . '">
                                                <h3>' . htmlspecialchars($project['name']) . '</h3></a>
                                                <p>' . htmlspecialchars($project['short_description']) . '</p>
                                                <span class="project-role">Rola: ' . htmlspecialchars($project['role']) . '</span>
                                            </div>';
                                }
                            } else {
                                echo '<p>Nie jesteś jeszcze członkiem żadnego projektu.</p>';
                            }
                            ?>
                        </div>
                    </section>

                    <!-- Ustawienia konta -->
                    <section class="settings-section">
                        <div class="section-header">
                            <h2>Ustawienia konta</h2>
                        </div>
                        <div class="settings-grid">
                            <div class="setting-group">
                                <h3>Zmiana hasła</h3>
                                <div class="password-fields">
                                    <input type="password" placeholder="Aktualne hasło">
                                    <input type="password" placeholder="Nowe hasło">
                                    <input type="password" placeholder="Powtórz nowe hasło">
                                    <button class="save-btn">Zapisz hasło</button>
                                </div>
                            </div>
                            <div class="setting-group">
                                <h3>Powiadomienia</h3>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" data-setting="new_tasks_email">
                                        <span class="checkmark"></span>
                                        Powiadomienie e-mail o nowych zadaniach
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" data-setting="new_comments_email">
                                        <span class="checkmark"></span>
                                        Powiadomienie e-mail o komentarzach
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" data-setting="system_email">
                                        <span class="checkmark"></span>
                                        Powiadomienia e-mail systemowe
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Preferencje projektów -->
                    <section class="preferences-section">
                        <div class="section-header">
                            <h2>Preferencje projektów</h2>
                        </div>
                        <div class="preference-item">
                            <label>Domyślna rola</label>
                            <select>
                                <option value="Uczestnik">Uczestnik</option>
                                <option value="Developer">Developer</option>
                                <option value="Designer">Designer</option>
                                <option value="Lider">Lider</option>
                            </select>
                        </div>
                        <div class="preference-item">
                            <label>Poziom zaangażowania</label>
                            <select>
                                <option value="Aktywnie uczestniczę">Aktywnie uczestniczę</option>
                                <option value="Mogę być przypisany do zadań">Mogę być przypisany do zadań</option>
                                <option value="Obserwuję">Obserwuję</option>
                            </select>
                        </div>
                    </section>

                    <!-- Odznaki -->
                    <section class="badges-section">
                        <div class="section-header">
                            <h2>Odznaki i osiągnięcia</h2>
                        </div>
                        <div class="badges-grid">
                            <?php if (!empty($userBadges)): ?>
                                <?php foreach ($userBadges as $badge): ?>
                                    <div class="badge-item">
                                        <div class="badge-icon"><?php echo htmlspecialchars($badge['emoji']); ?></div>
                                        <div class="badge-info">
                                            <h4><?php echo htmlspecialchars($badge['name']); ?></h4>
                                            <p><?php echo htmlspecialchars($badge['description']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-badges">
                                    <div class="badge-icon">🔒</div>
                                    <div class="badge-info">
                                        <h4>Brak odznak</h4>
                                        <p>Bądź aktywny w projektach, aby zdobyć swoje pierwsze odznaki!</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Strefa niebezpieczna -->
                    <section class="danger-section">
                        <div class="section-header">
                            <h2>Strefa niebezpieczna</h2>
                        </div>
                        <div class="danger-actions">
                            <button class="danger-btn" onclick="openDeleteModal()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 6H5H21" stroke="currentColor" stroke-width="2" />
                                    <path
                                        d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z"
                                        stroke="currentColor" stroke-width="2" />
                                </svg>
                                Usuń konto
                            </button>
                        </div>
                    </section>

                    <!-- Modal potwierdzenia usunięcia konta -->
                    <div id="deleteModal" class="danger-modal">
                        <div class="danger-modal-content">
                            <div class="danger-modal-icon">⚠️</div>
                            <h3 class="danger-modal-title">Czy na pewno chcesz usunąć konto?</h3>
                            <p class="danger-modal-text">
                                Ta operacja jest <strong>nieodwracalna</strong>. Wszystkie Twoje dane, projekty i
                                osiągnięcia zostaną trwale usunięte.
                                <br><br>
                                <strong>Tej akcji nie można cofnąć!</strong>
                            </p>
                            <div class="danger-modal-actions">
                                <button class="danger-modal-btn cancel" onclick="closeDeleteModal()">Anuluj</button>
                                <button class="danger-modal-btn confirm" onclick="confirmDelete()"
                                    id="confirmDeleteBtn">
                                    Tak, usuń konto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal edycji danych -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Edytuj dane</h3>
                <button class="modal-close" onclick="closeEditModal()">×</button>
            </div>
            <div class="modal-body">
                <input type="text" id="modalInput" class="modal-input">
            </div>
            <div class="modal-footer">
                <button class="modal-btn secondary" onclick="closeEditModal()">Anuluj</button>
                <button class="modal-btn primary" onclick="saveEdit()">Zapisz</button>
            </div>
        </div>
    </div>

    <!-- Modal zmiany avatara -->
    <div class="modal" id="avatarModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Zmień zdjęcie profilowe</h3>
                <button class="modal-close" onclick="closeAvatarModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="avatar-preview">
                    <img src="<?php echo $userAvatar ?>" alt="Podgląd" id="avatarPreview">
                </div>

                <div class="avatar-feedback hidden" id="avatarFeedback"></div>

                <div class="avatar-upload-area">
                    <input type="file" id="avatarUpload" accept="image/*">
                    <label for="avatarUpload" class="upload-btn" id="uploadBtn">
                        <span>Wybierz zdjęcie</span>
                    </label>
                </div>

                <div class="file-info hidden" id="fileInfo"></div>

                <div class="avatar-requirements">
                    <strong>Wymagania:</strong>
                    <ul>
                        <li>Format: JPG, PNG, GIF</li>
                        <li>Maksymalny rozmiar: 5MB</li>
                        <li>Rekomendowane: 200x200 px</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn secondary" onclick="closeAvatarModal()">Anuluj</button>
                <button class="modal-btn primary" id="saveAvatarBtn" disabled>Zapisz zdjęcie</button>
            </div>
        </div>
    </div>

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

    <script src="../scripts/account.js"></script>

    <body data-user-id="<?php echo $userId; ?>" data-user-email="<?php echo $email; ?>">
    </body>

</html>