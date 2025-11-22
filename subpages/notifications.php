<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");


// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

// Oznacz wszystkie jako przeczytane
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Oznacz jedno jako przeczytane
if (isset($_GET['mark_read'])) {
    $notificationId = $_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $stmt->close();

    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect']);
        exit();
    } else {
        header("Location: notifications.php");
        exit();
    }
}

// Pobierz powiadomienia
$notifications = [];
$stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY is_read ASC, created_at DESC 
    LIMIT 50
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Pobierz liczbę nieprzeczytanych
$unreadCount = 0;
$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$unreadCount = $countStmt->get_result()->fetch_assoc()['count'];
$countStmt->close();

// Funkcja do wyświetlania czasu w formie "x temu"
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => ['rok', 'lata', 'lat'],
        'm' => ['miesiąc', 'miesiące', 'miesięcy'],
        'w' => ['tydzień', 'tygodnie', 'tygodni'],
        'd' => ['dzień', 'dni', 'dni'],
        'h' => ['godzinę', 'godziny', 'godzin'],
        'i' => ['minutę', 'minuty', 'minut'],
        's' => ['sekundę', 'sekundy', 'sekund']
    ];

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v[($diff->$k == 1) ? 0 : (($diff->$k % 10 >= 2 && $diff->$k % 10 <= 4 && ($diff->$k % 100 < 10 || $diff->$k % 100 >= 20)) ? 1 : 2)];
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);

    return $string ? implode(', ', $string) . ' temu' : 'przed chwilą';
}

// Funkcje wysyłania powiadomień
function sendNotification($conn, $userId, $title, $message, $type = 'info', $relatedUrl = null)
{
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_url) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $userId, $title, $message, $type, $relatedUrl);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function sendTaskAssignmentNotification($conn, $userId, $taskId, $taskName)
{
    $message = "Zostałeś przypisany do zadania: " . $taskName;
    $url = "task_details.php?task_id=" . $taskId;
    return sendNotification($conn, $userId, '🎯 Nowe zadanie', $message, 'info', $url);
}

function sendTaskStatusNotification($conn, $userId, $taskId, $taskName, $newStatus)
{
    $message = "Status zadania '{$taskName}' zmieniono na: " . $newStatus;
    $url = "task_details.php?task_id=" . $taskId;
    return sendNotification($conn, $userId, '🔄 Zmiana statusu', $message, 'info', $url);
}

function sendDeadlineNotification($conn, $userId, $taskId, $taskName, $deadline)
{
    $message = "Zadanie '{$taskName}' ma termin: " . date('d.m.Y', strtotime($deadline));
    $url = "task_details.php?task_id=" . $taskId;
    return sendNotification($conn, $userId, '⏰ Zbliżający się deadline', $message, 'warning', $url);
}

function sendCommentNotification($conn, $userId, $taskId, $taskName, $commentAuthor)
{
    $message = "{$commentAuthor} dodał komentarz do zadania: {$taskName}";
    $url = "task_details.php?task_id=" . $taskId;
    return sendNotification($conn, $userId, '💬 Nowy komentarz', $message, 'info', $url);
}

function sendProjectInvitation($conn, $userId, $projectId, $projectName, $inviterName)
{
    $message = "{$inviterName} zaprosił Cię do projektu: {$projectName}";
    $url = "project.php?id=" . $projectId;
    return sendNotification($conn, $userId, '👥 Zaproszenie do projektu', $message, 'success', $url);
}
?>


<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Powiadomienia - TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/notifications_style.css">
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
                    <li><a href="notifications.php" class="active">Powiadomienia</a></li>
                    <?php echo $nav_cta_action; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <div class="notifications-container">
            <div class="notifications-header">
                <h1>Powiadomienia</h1>
                <div class="header-actions">
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn btn-secondary">
                                Oznacz wszystkie jako przeczytane
                            </button>
                        </form>
                    <?php endif; ?>
                    <span class="unread-badge"><?php echo $unreadCount; ?> nieprzeczytanych</span>
                </div>
            </div>

            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <h3>Brak powiadomień</h3>
                        <p>Nie masz jeszcze żadnych powiadomień.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-icon type-<?php echo $notification['type']; ?>">
                                <?php
                                $icons = [
                                    'info' => 'ℹ️',
                                    'warning' => '⚠️',
                                    'success' => '✅',
                                    'error' => '❌'
                                ];
                                echo $icons[$notification['type']] ?? '📢';
                                ?>
                            </div>

                            <div class="notification-content">
                                <h4 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="notification-time">
                                    <?php echo time_elapsed_string($notification['created_at']); ?>
                                </span>
                            </div>

                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="btn-mark-read"
                                        title="Oznacz jako przeczytane">
                                        ✓
                                    </a>
                                <?php endif; ?>

                                <?php if ($notification['related_url']): ?>
                                    <a href="<?php echo $notification['related_url']; ?>" class="btn btn-primary btn-sm">
                                        Przejdź
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
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
    </footer>
</body>

</html>