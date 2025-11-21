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
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --text: #1f2937;
            --text-light: #6b7280;
            --background: #ffffff;
            --surface: #f8fafc;
            --border: #e5e7eb;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--background);
            font-size: 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Header styles */
        header {
            background: var(--background);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--text);
        }

        .nav-brand img {
            width: 3rem;
            height: 3rem;
            border-radius: 0.5rem;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }

        .nav-menu a:hover {
            color: var(--primary);
        }

        .nav-menu a.active {
            color: var(--primary);
        }

        .nav-menu a.active::after {
            content: "";
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary);
        }

        .nav-cta a {
            background: var(--primary);
            color: white !important;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }

        .nav-cta a:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Button styles */
        .btn-primary,
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Notifications Styles */
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 1.5rem;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .notifications-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .unread-badge {
            background: var(--danger);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            padding: 1.5rem;
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .notification-item.unread {
            border-left-color: var(--primary);
            background: white;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .notification-icon {
            font-size: 1.5rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .type-info {
            background: #dbeafe;
            color: var(--primary);
        }

        .type-warning {
            background: #fef3c7;
            color: var(--accent);
        }

        .type-success {
            background: #d1fae5;
            color: var(--success);
        }

        .type-error {
            background: #fee2e2;
            color: var(--danger);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .notification-message {
            color: var(--text);
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .notification-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .btn-mark-read {
            background: var(--success);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            text-decoration: none;
        }

        .btn-mark-read:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .notification-link {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .notification-link:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }

        .empty-state h3 {
            color: var(--text-light);
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }

        .empty-state p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        /* Footer styles */
        footer {
            background: var(--text);
            color: white;
            padding: 3rem 0;
            margin-top: 4rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .footer-brand img {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
        }

        .footer-brand h3 {
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .footer-brand p {
            opacity: 0.8;
            font-size: 0.875rem;
        }

        .footer-copyright {
            opacity: 0.8;
        }

        /* Responsywność */
        @media (max-width: 768px) {
            .notifications-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }

            .notification-item {
                flex-direction: column;
                gap: 1rem;
            }

            .notification-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .footer-content {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .notifications-container {
                padding: 2rem 1rem;
            }

            .notification-item {
                padding: 1rem;
            }
        }
    </style>
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
                    <li><a href="notifications.php" class="active">Powiadomienia</a></li>
                    <li class="nav-cta"><a href="konto.php">Moje konto</a></li>
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