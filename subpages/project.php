<?php
session_start();
include("global/connection.php");

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

// Pobierz ID projektu z URL
$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    header("Location: projekty.php");
    exit();
}

// Sprawdź połączenie z bazą danych
if (!$conn) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Formatowanie daty
function formatDate($dateString)
{
    if (!$dateString || $dateString == '0000-00-00')
        return 'Nie ustawiono';
    $date = new DateTime($dateString);
    return $date->format('d.m.Y');
}

function formatDateTime($dateString)
{
    if (!$dateString || $dateString == '0000-00-00 00:00:00')
        return 'Nie ustawiono';
    $date = new DateTime($dateString);
    return $date->format('d.m.Y H:i');
}

// Funkcja do zwiększania licznika wyświetleń
function incrementProjectViews($conn, $projectId, $userId)
{
    $viewKey = 'project_view_' . $projectId;

    if (!isset($_SESSION[$viewKey])) {
        // Zwiększ licznik
        $updateSql = "UPDATE projects SET views_counter = views_counter + 1, updated_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);

        if ($updateStmt) {
            $updateStmt->bind_param("i", $projectId);
            $updateStmt->execute();
            $updateStmt->close();

            // Oznacz jako obejrzane w tej sesji
            $_SESSION[$viewKey] = true;

            // Pobierz zaktualizowaną liczbę wyświetleń
            $selectSql = "SELECT views_counter FROM projects WHERE id = ?";
            $selectStmt = $conn->prepare($selectSql);
            if ($selectStmt) {
                $selectStmt->bind_param("i", $projectId);
                $selectStmt->execute();
                $result = $selectStmt->get_result();
                $row = $result->fetch_assoc();
                $selectStmt->close();
                return $row['views_counter'];
            }
        }
    }

    // Jeśli już było liczone, zwróć aktualną wartość
    $currentSql = "SELECT views_counter FROM projects WHERE id = ?";
    $currentStmt = $conn->prepare($currentSql);
    if ($currentStmt) {
        $currentStmt->bind_param("i", $projectId);
        $currentStmt->execute();
        $result = $currentStmt->get_result();
        $row = $result->fetch_assoc();
        $currentStmt->close();
        return $row['views_counter'];
    }

    return 0;
}

// Użycie:
$currentViews = incrementProjectViews($conn, $projectId, $userId);

// Mapowanie priorytetów
$priorityMap = [
    'low' => 'Niski',
    'medium' => 'Średni',
    'high' => 'Wysoki'
];

// Mapowanie statusów z wartościami domyślnymi
$statusMap = [
    'active' => 'Aktywny',
    'completed' => 'Zakończony',
    'paused' => 'Wstrzymany',
    'draft' => 'Szkic'
];

// Mapowanie widoczności z wartościami domyślnymi
$visibilityMap = [
    'public' => 'Publiczny',
    'private' => 'Prywatny'
];

// Bezpieczne pobieranie wartości z mapowań
function getStatus($status, $statusMap)
{
    if (!$status)
        return 'Aktywny';
    return $statusMap[$status] ?? 'Aktywny';
}

function getVisibility($visibility, $visibilityMap)
{
    if (!$visibility)
        return 'Publiczny';
    return $visibilityMap[$visibility] ?? 'Publiczny';
}

// Pobierz dane projektu
try {
    // Podstawowe informacje o projekcie
    $sql = "
        SELECT p.*, u.nick as founder_name, u.email as founder_email
        FROM projects p 
        LEFT JOIN users u ON p.founder_id = u.id 
        WHERE p.id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Błąd przygotowania zapytania: " . $conn->error);
    }

    $stmt->bind_param("i", $projectId);

    if (!$stmt->execute()) {
        throw new Exception("Błąd wykonania zapytania: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        throw new Exception("Projekt nie istnieje");
    }

    $stmt->close();

    // ZWIĘKSZ LICZNIK WYŚWIETLEŃ
    incrementProjectViews($conn, $projectId, $userId);

    // Pobierz kategorie projektu
    $categories = [];
    $catSql = "
        SELECT c.name 
        FROM categories c 
        JOIN project_categories pc ON c.id = pc.category_id 
        WHERE pc.project_id = ?
    ";
    $catStmt = $conn->prepare($catSql);

    if ($catStmt) {
        $catStmt->bind_param("i", $projectId);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        while ($row = $catResult->fetch_assoc()) {
            $categories[] = $row['name'];
        }
        $catStmt->close();
    }

    // Pobierz cele projektu
    $goals = [];
    $goalStmt = $conn->prepare("SELECT description FROM goals WHERE project_id = ?");
    if ($goalStmt) {
        $goalStmt->bind_param("i", $projectId);
        $goalStmt->execute();
        $goalResult = $goalStmt->get_result();
        while ($row = $goalResult->fetch_assoc()) {
            $goals[] = $row['description'];
        }
        $goalStmt->close();
    }

    // Pobierz liczbę polubień projektu
    $likeCount = 0;

    // Pobierz liczbę polubień
    $likeCountStmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE project_id = ?");
    $likeCountStmt->bind_param("i", $projectId);
    $likeCountStmt->execute();
    $likeCountResult = $likeCountStmt->get_result();
    $likeCountData = $likeCountResult->fetch_assoc();
    $likeCount = $likeCountData['like_count'] ?? 0;
    $likeCountStmt->close();

    // POPRAWIONE: Sprawdź czy użytkownik obserwuje projekt - używaj $conn zamiast $pdo
    $isFollowing = false;
    if (isset($_SESSION['user_id'])) {
        $followStmt = $conn->prepare("SELECT id FROM follows WHERE user_id = ? AND project_id = ?");
        $followStmt->bind_param("ii", $_SESSION['user_id'], $projectId);
        $followStmt->execute();
        $followResult = $followStmt->get_result();
        $isFollowing = $followResult->fetch_assoc() !== null;
        $followStmt->close();
    }

    $userAvatarUrlStmt = $conn->prepare('SELECT avatar FROM users WHERE id = ?');
    $userAvatarUrlStmt->bind_param('s', $_SESSION['user_id']);
    $userAvatarUrlStmt->execute();

    $userAvatarUrlResult = $userAvatarUrlStmt->get_result();
    $userAvatarUrlRow = $userAvatarUrlResult->fetch_assoc(); // tutaj fetch_assoc na wyniku, nie na stmt
    $userAvatarUrl = $userAvatarUrlRow['avatar'] ?? 'd'; // domyślny avatar, jeśli brak

    $userAvatarUrlStmt->close();


    // Pobierz umiejętności projektu
    $skills = [];
    $skillSql = "
        SELECT s.name 
        FROM skills s 
        JOIN project_skills ps ON s.id = ps.skill_id 
        WHERE ps.project_id = ?
    ";
    $skillStmt = $conn->prepare($skillSql);
    if ($skillStmt) {
        $skillStmt->bind_param("i", $projectId);
        $skillStmt->execute();
        $skillResult = $skillStmt->get_result();
        while ($row = $skillResult->fetch_assoc()) {
            $skills[] = $row['name'];
        }
        $skillStmt->close();
    }

    // Pobierz zadania projektu
    $tasks = [];
    $taskStmt = $conn->prepare("SELECT name, description, priority FROM tasks WHERE project_id = ?");
    if ($taskStmt) {
        $taskStmt->bind_param("i", $projectId);
        $taskStmt->execute();
        $taskResult = $taskStmt->get_result();
        while ($row = $taskResult->fetch_assoc()) {
            $tasks[] = $row;
        }
        $taskStmt->close();
    }

    // Pobierz członków zespołu (właściciel + członkowie z project_team)
    $teamMembers = [];

    // Najpierw pobierz właściciela
    $ownerSql = "SELECT id, nick, email FROM users WHERE id = ?";
    $ownerStmt = $conn->prepare($ownerSql);
    if ($ownerStmt) {
        $ownerStmt->bind_param("i", $project['founder_id']);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result();
        $owner = $ownerResult->fetch_assoc();
        if ($owner) {
            $owner['role'] = 'Założyciel';
            $owner['joined_at'] = $project['created_at'];
            $teamMembers[] = $owner;
        }
        $ownerStmt->close();
    }

    // Potem pobierz pozostałych członków z project_team
    $teamSql = " SELECT u.id, u.nick, u.email, u.avatar, pt.role, pt.joined_at FROM project_team pt JOIN users u ON pt.user_id = u.id WHERE pt.project_id = ? AND pt.user_id != ?

    ";
    $teamStmt = $conn->prepare($teamSql);
    if ($teamStmt) {
        $teamStmt->bind_param("ii", $projectId, $project['founder_id']);
        $teamStmt->execute();
        $teamResult = $teamStmt->get_result();
        while ($row = $teamResult->fetch_assoc()) {
            $teamMembers[] = $row;
        }
        $teamStmt->close();
    }

    // Sprawdź czy użytkownik jest właścicielem projektu
    $isOwner = ($project['founder_id'] == $userId);

    // Sprawdź czy użytkownik jest członkiem zespołu
    $isMember = false;
    foreach ($teamMembers as $member) {
        if ($member['id'] == $userId) {
            $isMember = true;
            break;
        }
    }

    $comments = [];
    $commentStmt = $conn->prepare("SELECT c.comment, c.created_at, u.nick, u.avatar 
                               FROM comments c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE c.project_id = ? 
                               ORDER BY c.created_at ASC");
    if ($commentStmt) {
        $commentStmt->bind_param("i", $projectId);
        $commentStmt->execute();
        $commentResult = $commentStmt->get_result();
        while ($row = $commentResult->fetch_assoc()) {
            $comments[] = $row;
        }
        $commentStmt->close();
    }



} catch (Exception $e) {
    die("Błąd: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> - Projekt | TeenCollab</title>
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
                    <li><a href="index.php">Strona główna</a></li>
                    <li><a href="projekty.php">Projekty</a></li>
                    <li><a href="społeczność.php">Społeczność</a></li>
                    <li><a href="o-projekcie.php">O projekcie</a></li>
                    <li class="nav-cta"><a href="konto.php">Moje konto</a></li>
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
                        <?php if ($project['thumbnail']): ?>
                            <img src="<?php echo htmlspecialchars($project['thumbnail']); ?>"
                                alt="<?php echo htmlspecialchars($project['name']); ?>">
                        <?php else: ?>
                            <img src="../photos/project-sample.jpg" alt="<?php echo htmlspecialchars($project['name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="hero-info">
                        <div class="project-status status-<?php echo $project['status'] ?? 'active'; ?>">
                            <span class="status-dot"></span>
                            <?php echo getStatus($project['status'] ?? '', $statusMap); ?>
                        </div>
                        <h1 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h1>
                        <p class="project-tagline"><?php echo htmlspecialchars($project['short_description']); ?></p>

                        <div class="project-categories">
                            <?php foreach ($categories as $category): ?>
                                <span class="category-tag"><?php echo htmlspecialchars($category); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="hero-actions">
                            <?php if (!$isMember && !$isOwner): ?>
                                <button class="btn-primary btn-join" id="joinProjectBtn">
                                    <span>Dołącz do projektu</span>
                                </button>
                            <?php elseif ($isMember): ?>
                                <button class="btn-secondary" disabled>
                                    <span>✅ Jesteś członkiem</span>
                                </button>
                            <?php endif; ?>
                            <button class="btn-secondary <?php echo $isFollowing ? 'following' : ''; ?>" id="followBtn">
                                <span><?php echo $isFollowing ? 'Obserwujesz' : '❤️ Obserwuj'; ?></span>
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
                                <img src="<?php echo $userAvatarUrl; ?>"
                                    alt="<?php echo htmlspecialchars($project['founder_name']); ?>">
                            </div>
                            <div class="creator-info">
                                <h3 class="creator-name"><?php echo htmlspecialchars($project['founder_name']); ?></h3>
                                <p class="creator-role">Założyciel projektu</p>
                                <div class="creator-meta">
                                    <span class="meta-item">📅 Projekt utworzony:
                                        <?php echo formatDate($project['created_at']); ?></span>
                                    <span class="meta-item">👥 <?php echo count($teamMembers); ?> członków
                                        zespołu</span>
                                </div>
                                <a href="profil.php?id=<?php echo $project['founder_id']; ?>"
                                    class="creator-link">Zobacz profil twórcy →</a>
                            </div>
                        </div>
                    </section>

                    <!-- 📝 Pełny opis projektu -->
                    <?php if ($project['full_description']): ?>
                        <section class="content-section description-section">
                            <div class="section-header">
                                <h2>O projekcie</h2>
                            </div>
                            <div class="project-description">
                                <?php echo nl2br(htmlspecialchars($project['full_description'])); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- 🎯 Cele projektu -->
                    <?php if (!empty($goals)): ?>
                        <section class="content-section goals-section">
                            <div class="section-header">
                                <h2>Cele projektu</h2>
                            </div>
                            <div class="goals-list">
                                <?php foreach ($goals as $goal): ?>
                                    <div class="goal-item">
                                        <div class="goal-header">
                                            <span class="goal-icon">🎯</span>
                                            <span class="goal-text"><?php echo htmlspecialchars($goal); ?></span>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: 0%"></div>
                                        </div>
                                        <span class="progress-text">0% ukończono</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- 🔧 Lista zadań -->
                    <?php if (!empty($tasks)): ?>
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
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-card" data-status="open" data-priority="<?php echo $task['priority']; ?>">
                                        <div class="task-main">
                                            <h3 class="task-title"><?php echo htmlspecialchars($task['name']); ?></h3>
                                            <?php if ($task['description']): ?>
                                                <p class="task-description"><?php echo htmlspecialchars($task['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="task-meta">
                                            <span class="task-priority priority-<?php echo $task['priority']; ?>">
                                                <?php echo $priorityMap[$task['priority']] ?? 'Średni'; ?>
                                            </span>
                                            <span class="task-status status-open">Otwarte</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- 👥 Zespół projektu -->
                    <section class="content-section team-section">
                        <div class="section-header">
                            <h2>Nasz zespół</h2>
                            <span class="section-subtitle"><?php echo count($teamMembers); ?> członków</span>
                        </div>
                        <div class="team-grid">
                            <?php foreach ($teamMembers as $member): ?>
                                <div class="team-member-card">
                                    <div class="member-avatar">
                                        <img src="<?php echo $userAvatarUrl; ?>"
                                            alt="<?php echo htmlspecialchars($member['nick']); ?>">
                                    </div>
                                    <div class="member-info">
                                        <h3 class="member-name"><?php echo htmlspecialchars($member['nick']); ?></h3>
                                        <p class="member-role"><?php echo htmlspecialchars($member['role']); ?></p>
                                        <span class="member-tenure">
                                            W zespole od <?php echo formatDate($member['joined_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (!$isMember && !$isOwner && $project['allow_applications']): ?>
                                <div class="team-join-card">
                                    <div class="join-icon">➕</div>
                                    <h3>Dołącz do zespołu!</h3>
                                    <p>Szukamy nowych członków</p>
                                    <button class="btn-secondary btn-apply" id="applyBtn">Aplikuj do projektu</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- 💬 Sekcja komentarzy -->
                    <section class="content-section comments-section">
                        <div class="section-header">
                            <h2>Dyskusja</h2>
                            <div class="comments-stats">
                                <span class="stat-item">💬 <?php echo count($comments); ?> komentarzy</span>
                                <span class="stat-item">👁️ <?php echo $currentViews; ?> wyświetleń</span>
                            </div>
                        </div>

                        <?php if ($isMember || $isOwner): ?>
                            <div class="comment-form">
                                <div class="comment-avatar">
                                    <img src="<?php echo $userAvatarUrl; ?>" alt="Twój avatar">
                                </div>
                                <div class="comment-input-container">
                                    <textarea id="commentInput" class="comment-input"
                                        placeholder="Podziel się swoją opinią lub zadaj pytanie..."></textarea>
                                    <div class="comment-actions">
                                        <button id="btnAddComment" class="btn-primary btn-comment">Dodaj komentarz</button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="comment-restricted">
                                <p>💬 Dołącz do projektu, aby uczestniczyć w dyskusji</p>
                            </div>
                        <?php endif; ?>

                        <div class="comments-list">
                            <?php if (!empty($comments)): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-avatar">
                                            <img src="<?php echo $comment['avatar'] ?? 'default-avatar.jpg'; ?>"
                                                alt="<?php echo htmlspecialchars($comment['nick']); ?>">
                                        </div>
                                        <div class="comment-content">
                                            <h4><?php echo htmlspecialchars($comment['nick']); ?></h4>
                                            <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                            <span
                                                class="comment-date"><?php echo formatDateTime($comment['created_at']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-comments">
                                    <p>Brak komentarzy. Bądź pierwszy, który skomentuje!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <script>
                        document.getElementById('btnAddComment')?.addEventListener('click', function (e) {
                            e.preventDefault();
                            const comment = document.getElementById('commentInput').value.trim();
                            if (!comment) return alert('Komentarz nie może być pusty!');

                            fetch('add_comment.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'project_id=<?php echo $projectId; ?>&comment=' + encodeURIComponent(comment)
                            })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        const list = document.querySelector('.comments-list');
                                        const newComment = document.createElement('div');
                                        newComment.classList.add('comment-item');
                                        newComment.innerHTML = `
                <div class="comment-avatar">
                    <img src="<?php echo $userAvatarUrl; ?>" alt="Twój avatar">
                </div>
                <div class="comment-content">
                    <h4>Ty</h4>
                    <p>${comment.replace(/\n/g, '<br>')}</p>
                    <span class="comment-date">Właśnie teraz</span>
                </div>
            `;
                                        list.prepend(newComment);
                                        document.getElementById('commentInput').value = '';
                                        document.querySelector('.stat-item').innerText = `💬 ${list.querySelectorAll('.comment-item').length} komentarzy`;
                                    } else {
                                        alert('Błąd przy dodawaniu komentarza!');
                                    }
                                })
                                .catch(() => alert('Coś poszło nie tak...'));
                        });
                    </script>


                </div>

                <!-- Prawa kolumna - sidebar -->
                <div class="sidebar-column">
                    <!-- 🏷️ Tagi projektu -->
                    <?php if ($project['seo_tags']): ?>
                        <div class="sidebar-card tags-card">
                            <h3>Tagi projektu</h3>
                            <div class="tags-cloud">
                                <?php
                                $tags = explode(',', $project['seo_tags']);
                                foreach ($tags as $tag):
                                    $tag = trim($tag);
                                    if ($tag):
                                        ?>
                                        <span class="project-tag"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php
                                    endif;
                                endforeach;
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- ❤️ Reakcje -->
                    <div class="sidebar-card reactions-card">
                        <h3>Reakcje</h3>
                        <div class="reactions-stats">
                            <div class="reaction-item">
                                <span class="reaction-icon">❤️</span>
                                <span class="reaction-count"><?php echo $likeCount; ?></span>
                            </div>
                            <div class="reaction-item">
                                <span class="reaction-icon">👁️</span>
                                <span class="reaction-count"><?php echo $currentViews; ?></span>
                            </div>
                            <div class="reaction-item">
                                <span class="reaction-icon">💬</span>
                                <span class="reaction-count">0</span>
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
                                <span class="info-value status-<?php echo $project['status'] ?? 'active'; ?>">
                                    <?php echo getStatus($project['status'] ?? '', $statusMap); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Data utworzenia:</span>
                                <span class="info-value"><?php echo formatDate($project['created_at']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Członkowie:</span>
                                <span class="info-value"><?php echo count($teamMembers); ?> osób</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Widoczność:</span>
                                <span class="info-value">
                                    <?php echo getVisibility($project['visibility'] ?? '', $visibilityMap); ?>
                                </span>
                            </div>
                            <?php if ($project['deadline'] && $project['deadline'] != '0000-00-00'): ?>
                                <div class="info-item">
                                    <span class="info-label">Termin:</span>
                                    <span class="info-value"><?php echo formatDate($project['deadline']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <span class="info-label">Ostatnia aktywność:</span>
                                <span
                                    class="info-value"><?php echo formatDateTime($project['updated_at'] ?? $project['created_at']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 🔒 Narzędzia (dla właściciela) -->
                    <?php if ($isOwner): ?>
                        <div class="sidebar-card tools-card">
                            <h3>Narzędzia projektu</h3>
                            <div class="tools-list">
                                <a href="edit_project.php?id=<?php echo $projectId; ?>" class="tool-btn">✏️ Edytuj
                                    projekt</a>
                                <a href="manage_team.php?project_id=<?php echo $projectId; ?>" class="tool-btn">👥 Zarządzaj
                                    zespołem</a>
                                <a href="manage_tasks.php?project_id=<?php echo $projectId; ?>" class="tool-btn">✅ Zarządzaj
                                    zadaniami</a>

                                <!-- Usuwanie projektu przez formularz POST -->
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="delete_project_id" value="<?php echo $projectId; ?>">
                                    <button type="submit" class="tool-btn danger"
                                        onclick="return confirm('Na pewno chcesz usunąć projekt?');">🗑️ Usuń
                                        projekt</button>
                                </form>
                            </div>
                        </div>

                        <?php
                        // Obsługa usuwania projektu
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project_id'])) {
                            $deleteId = (int) $_POST['delete_project_id'];

                            // Tu wstaw kod do usuwania projektu z bazy danych
                            $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
                            $stmt->execute([$deleteId]);

                            // Przekierowanie po usunięciu
                            header("Location: projects_list.php");
                            exit;
                        }
                        ?>
                    <?php endif; ?>


                    <!-- 🛠️ Wymagane umiejętności -->
                    <?php if (!empty($skills)): ?>
                        <div class="sidebar-card skills-card">
                            <h3>Wymagane umiejętności</h3>
                            <div class="skills-list">
                                <?php foreach ($skills as $skill): ?>
                                    <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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

    <!-- Modal dołączania do projektu -->
    <div class="modal" id="joinModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dołącz do projektu "<?php echo htmlspecialchars($project['name']); ?>"</h3>
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
    <script>
        // Przekazanie danych do JavaScript
        const projectData = {
            id: <?php echo $projectId; ?>,
            name: "<?php echo addslashes($project['name']); ?>",
            isOwner: <?php echo $isOwner ? 'true' : 'false'; ?>,
            isMember: <?php echo $isMember ? 'true' : 'false'; ?>,
            allowApplications: <?php echo $project['allow_applications'] ? 'true' : 'false'; ?>,
            autoAccept: <?php echo $project['auto_accept'] ? 'true' : 'false'; ?>
        };
    </script>
</body>

</html>