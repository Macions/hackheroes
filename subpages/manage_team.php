<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");
include("global/log_action.php"); // Dodaj include pliku z funkcją logowania

$projectId = $_GET['project_id'] ?? 0;
$currentUserId = $_SESSION['user_id'] ?? 0;
$userEmail = $_SESSION["user_email"] ?? '';

// Logowanie wejścia na stronę zarządzania zespołem
logAction($conn, $currentUserId, $userEmail, "team_management_page_accessed", "ID projektu: $projectId");

$stmt = $conn->prepare("SELECT founder_id FROM projects WHERE id = ?");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$stmt->bind_result($projectOwner);
$stmt->fetch();
$stmt->close();

$isOwner = ($currentUserId == $projectOwner);
if (!$isOwner) {
    logAction($conn, $currentUserId, $userEmail, "team_management_unauthorized", "ID projektu: $projectId - Brak uprawnień");
    die("Nie masz dostępu do zarządzania tym projektem.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_id'])) {
    $newUserId = (int) $_POST['add_user_id'];
    $role = $_POST['role'];

    // Pobierz nick użytkownika dla logu
    $stmt = $conn->prepare("SELECT nick FROM users WHERE id = ?");
    $stmt->bind_param("i", $newUserId);
    $stmt->execute();
    $stmt->bind_result($userNick);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM project_team WHERE project_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $projectId, $newUserId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count == 0) {
        $stmt = $conn->prepare("INSERT INTO project_team (project_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $projectId, $newUserId, $role);
        $stmt->execute();
        $stmt->close();

        // Logowanie dodania użytkownika do zespołu
        logAction($conn, $currentUserId, $userEmail, "team_member_added", "ID projektu: $projectId, ID użytkownika: $newUserId, Nick: $userNick, Rola: $role");
    }

    header("Location: manage_team.php?project_id=$projectId");
    exit;
}

if (isset($_GET['remove_user_id'])) {
    $removeUserId = (int) $_GET['remove_user_id'];

    // Pobierz nick użytkownika dla logu
    $stmt = $conn->prepare("SELECT nick FROM users WHERE id = ?");
    $stmt->bind_param("i", $removeUserId);
    $stmt->execute();
    $stmt->bind_result($userNick);
    $stmt->fetch();
    $stmt->close();

    if ($removeUserId != $projectOwner) {
        $stmt = $conn->prepare("DELETE FROM project_team WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $projectId, $removeUserId);
        $stmt->execute();
        $stmt->close();

        // Logowanie usunięcia użytkownika z zespołu
        logAction($conn, $currentUserId, $userEmail, "team_member_removed", "ID projektu: $projectId, ID użytkownika: $removeUserId, Nick: $userNick");
    }

    header("Location: manage_team.php?project_id=$projectId");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role_user_id'])) {
    $userId = (int) $_POST['change_role_user_id'];
    $newRole = $_POST['new_role'];

    // Pobierz nick użytkownika i starą rolę dla logu
    $stmt = $conn->prepare("SELECT u.nick, pt.role FROM users u JOIN project_team pt ON u.id = pt.user_id WHERE pt.project_id = ? AND pt.user_id = ?");
    $stmt->bind_param("ii", $projectId, $userId);
    $stmt->execute();
    $stmt->bind_result($userNick, $oldRole);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT role FROM project_team WHERE project_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $projectId, $currentUserId);
    $stmt->execute();
    $stmt->bind_result($currentUserRole);
    $stmt->fetch();
    $stmt->close();

    if ($userId != $projectOwner && ($currentUserId == $projectOwner || ($currentUserRole != 'Developer' || $targetRole != 'Developer'))) {
        $stmt = $conn->prepare("UPDATE project_team SET role = ? WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newRole, $projectId, $userId);
        $stmt->execute();
        $stmt->close();

        // Logowanie zmiany roli użytkownika
        logAction($conn, $currentUserId, $userEmail, "team_member_role_changed", "ID projektu: $projectId, ID użytkownika: $userId, Nick: $userNick, Rola: $oldRole -> $newRole");
    }

    header("Location: manage_team.php?project_id=$projectId");
    exit;
}

$stmt = $conn->prepare("
    SELECT pt.user_id, pt.role, u.nick, u.avatar
    FROM project_team pt
    JOIN users u ON pt.user_id = u.id
    WHERE pt.project_id = ?
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$teamMembers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie zespołem | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/manage_team_style.css">
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
            </div>
        </nav>
    </header>

    <main class="manage-team-container">
        <div class="container">
            <div class="page-header">
                <h1>Zarządzanie zespołem</h1>
                <a href="project.php?id=<?php echo $projectId; ?>" class="btn-back">← Powrót do projektu</a>
            </div>

            <!-- Sekcja dodawania nowego członka -->
            <section class="add-member-section">
                <h2>Dodaj nowego członka</h2>
                <form method="POST" class="add-member-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="add_user_id">Wybierz użytkownika:</label>
                            <select name="add_user_id" id="add_user_id" required>
                                <option value="">-- Wybierz użytkownika --</option>
                                <?php foreach ($allUsers as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['nick']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="role">Rola w zespole:</label>
                            <select name="role" id="role" required>
                                <option value="developer">Developer</option>
                                <option value="designer">Designer</option>
                                <option value="project_manager">Project Manager</option>
                                <option value="tester">Tester</option>
                                <option value="content_creator">Content Creator</option>
                                <option value="other">Inna</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Dodaj do zespołu</button>
                </form>
            </section>

            <!-- Lista obecnych członków -->
            <section class="team-members-section">
                <h2>Członkowie zespołu (<?php echo count($teamMembers); ?>)</h2>

                <?php if (empty($teamMembers)): ?>
                    <div class="empty-state">
                        <p>Brak członków w zespole. Dodaj pierwszą osobę!</p>
                    </div>
                <?php else: ?>
                    <div class="members-grid">
                        <?php foreach ($teamMembers as $member): ?>
                            <div class="member-card" data-user-id="<?php echo $member['user_id']; ?>">
                                <div class="member-info">
                                    <div class="member-avatar">
                                        <img src="<?php echo htmlspecialchars($member['avatar'] ?? '../photos/sample_person.png'); ?>"
                                            alt="<?php echo htmlspecialchars($member['nick']); ?>">
                                    </div>
                                    <div class="member-details">
                                        <h3 class="member-name"><?php echo htmlspecialchars($member['nick']); ?></h3>
                                        <span class="member-role"><?php echo htmlspecialchars($member['role']); ?></span>
                                    </div>
                                </div>
                                <div class="member-actions">
                                    <!-- Formularz zmiany roli -->
                                    <form method="POST" class="role-form">
                                        <input type="hidden" name="change_role_user_id"
                                            value="<?php echo $member['user_id']; ?>">
                                        <select name="new_role" class="role-select" onchange="this.form.submit()">
                                            <option value="developer" <?php echo $member['role'] === 'developer' ? 'selected' : ''; ?>>Developer</option>
                                            <option value="designer" <?php echo $member['role'] === 'designer' ? 'selected' : ''; ?>>Designer</option>
                                            <option value="project_manager" <?php echo $member['role'] === 'project_manager' ? 'selected' : ''; ?>>Project Manager</option>
                                            <option value="tester" <?php echo $member['role'] === 'tester' ? 'selected' : ''; ?>>
                                                Tester</option>
                                            <option value="content_creator" <?php echo $member['role'] === 'content_creator' ? 'selected' : ''; ?>>Content Creator</option>
                                            <option value="other" <?php echo $member['role'] === 'other' ? 'selected' : ''; ?>>
                                                Inna</option>
                                        </select>
                                    </form>

                                    <!-- Przycisk usuwania -->
                                    <a href="manage_team.php?project_id=<?php echo $projectId; ?>&remove_user_id=<?php echo $member['user_id']; ?>"
                                        class="btn-remove"
                                        onclick="return confirm('Czy na pewno chcesz usunąć tego członka z zespołu?')">
                                        🗑️ Usuń
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

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

    <script src="../scripts/manage_team.js"></script>
</body>

</html>