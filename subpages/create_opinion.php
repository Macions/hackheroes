<?php
session_start();
include("global/connection.php");
include("global/nav_global.php");
include("global/log_action.php");


// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? '';
$error = '';
$success = '';

// Sprawdź, czy konto istnieje dłużej niż miesiąc
try {
    $user_stmt = $conn->prepare("
        SELECT created_at 
        FROM users 
        WHERE id = ? AND created_at <= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $has_account_long_enough = $user_stmt->get_result()->num_rows > 0;
    $user_stmt->close();
} catch (Exception $e) {
    $has_account_long_enough = false;
    error_log("Błąd przy sprawdzaniu wieku konta: " . $e->getMessage());
}

// Sprawdź, czy użytkownik już dodał opinię o stronie (project_id IS NULL)
$has_existing_review = false;
if ($has_account_long_enough) {
    try {
        $review_stmt = $conn->prepare("
            SELECT id FROM reviews 
            WHERE user_id = ?
        ");
        $review_stmt->bind_param("i", $user_id);
        $review_stmt->execute();
        $has_existing_review = $review_stmt->get_result()->num_rows > 0;
        $review_stmt->close();
    } catch (Exception $e) {
        error_log("Błąd przy sprawdzaniu istniejącej opinii: " . $e->getMessage());
    }
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $has_account_long_enough && !$has_existing_review) {
    $rating = $_POST['rating'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    if (empty($rating) || empty($comment)) {
        $error = "Wszystkie pola są wymagane!";
        logAction($conn, $user_id, $user_email, "review_attempt_failed", "Brak oceny lub komentarza");
    } elseif (strlen($comment) < 10) {
        $error = "Komentarz musi mieć co najmniej 10 znaków!";
        logAction($conn, $user_id, $user_email, "review_attempt_failed", "Komentarz za krótki");
    } else {
        try {
            $insert_stmt = $conn->prepare("
                INSERT INTO reviews (user_id, rating, comment, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $insert_stmt->bind_param("iis", $user_id, $rating, $comment);

            if ($insert_stmt->execute()) {
                $success = "Twoja opinia o stronie została dodana pomyślnie! Dziękujemy za feedback!";
                logAction($conn, $user_id, $user_email, "review_added", "Rating: $rating, Comment: $comment");
                $_POST = [];
                $has_existing_review = true;
            } else {
                $error = "Wystąpił błąd podczas dodawania opinii.";
                logAction($conn, $user_id, $user_email, "review_attempt_failed", "Błąd wykonania INSERT");
            }
            $insert_stmt->close();
        } catch (Exception $e) {
            $error = "Wystąpił błąd: " . $e->getMessage();
            logAction($conn, $user_id, $user_email, "review_attempt_failed", "Exception: " . $e->getMessage());
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj opinię o stronie - TeenCollab</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Additional styles for opinion form */
        .opinion-form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .opinion-form-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .opinion-form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #8b5cf6, #f59e0b);
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h1 {
            font-family: "Inter", sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1f2937, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            font-family: "Inter", sans-serif;
            font-size: 1.1rem;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            font-family: "Inter", sans-serif;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: "Inter", sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .star {
            font-size: 2.5rem;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
            background: none;
            border: none;
        }

        .star:hover,
        .star.active {
            color: #f59e0b;
            transform: scale(1.1);
        }

        .char-count {
            text-align: right;
            font-family: "Inter", sans-serif;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .char-count.warning {
            color: #ef4444;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-family: "Inter", sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.9);
            color: #6b7280;
            border: 2px solid #e5e7eb;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            font-family: "Inter", sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #f8fafc;
            border-color: #10b981;
            color: #10b981;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-family: "Inter", sans-serif;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            color: #1d4ed8;
        }

        .requirements-info {
            background: #fffbeb;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .requirements-info h3 {
            font-family: "Inter", sans-serif;
            color: #92400e;
            margin-bottom: 0.5rem;
        }

        .requirements-info ul {
            list-style: none;
            padding: 0;
        }

        .requirements-info li {
            font-family: "Inter", sans-serif;
            color: #92400e;
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        @keyframes shimmer {

            0%,
            100% {
                background-position: -200% 0;
            }

            50% {
                background-position: 200% 0;
            }
        }

        @media (max-width: 768px) {
            .opinion-form-container {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .opinion-form-card {
                padding: 2rem;
            }

            .form-header h1 {
                font-size: 2rem;
            }

            .rating-stars {
                gap: 0.25rem;
            }

            .star {
                font-size: 2rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-cancel {
                width: 100%;
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

    <main>
        <div class="opinion-form-container">
            <div class="opinion-form-card">
                <div class="form-header">
                    <h1>Twoja opinia o TeenCollab</h1>
                    <p>Podziel się swoimi doświadczeniami z korzystania z naszej platformy</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!$has_account_long_enough): ?>
                    <div class="requirements-info">
                        <h3>Wymagania do dodania opinii</h3>
                        <ul>
                            <li>Musisz posiadać konto na TeenCollab przez co najmniej 1 miesiąc</li>
                        </ul>
                        <p style="margin-top: 1rem; color: #92400e; font-family: 'Inter', sans-serif;">
                            ❌ Twoje konto nie spełnia jeszcze tego wymagania. Wróć za jakiś czas!
                        </p>
                    </div>
                    <div class="form-actions">
                        <a href="../index.php" class="btn-cancel">Powrót do strony głównej</a>
                    </div>
                <?php elseif ($has_existing_review && !$success): ?>
                    <div class="alert alert-info">
                        ❌ Już dodałeś opinię o naszej stronie. Dziękujemy za feedback!
                    </div>
                    <div class="form-actions">
                        <a href="../index.php" class="btn-cancel">Powrót do strony głównej</a>
                    </div>
                <?php else: ?>
                    <div class="requirements-info">
                        <h3>Twoje konto spełnia wymagania!</h3>
                        <p style="color: #92400e; font-family: 'Inter', sans-serif;">
                            ✅ Możesz dodać opinię o naszej platformie. Dziękujemy!
                        </p>
                    </div>

                    <form method="POST" id="opinionForm">
                        <div class="form-group">
                            <label class="form-label">Ogólna ocena strony *</label>
                            <div class="rating-stars" id="ratingStars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" class="star" data-rating="<?php echo $i; ?>">★</button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="rating" value="<?php echo $_POST['rating'] ?? ''; ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="comment">Twoja opinia *</label>
                            <textarea class="form-textarea" id="comment" name="comment"
                                placeholder="Napisz co sądzisz o naszej platformie, jakie są Twoje doświadczenia, co Ci się podoba, co możemy poprawić..."
                                required><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                            <div class="char-count" id="charCount">0/500 znaków</div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">Opublikuj opinię</button>
                            <a href="../index.php" class="btn-cancel">Anuluj</a>
                        </div>
                    </form>
                <?php endif; ?>
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

    <script>
        // Rating stars
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const commentTextarea = document.getElementById('comment');
        const charCount = document.getElementById('charCount');

        // Rating stars functionality
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.getAttribute('data-rating');
                ratingInput.value = rating;

                // Update stars appearance
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        // Character count for comment
        commentTextarea.addEventListener('input', () => {
            const length = commentTextarea.value.length;
            charCount.textContent = `${length}/500 znaków`;

            if (length > 450) {
                charCount.classList.add('warning');
            } else {
                charCount.classList.remove('warning');
            }

            if (length > 500) {
                commentTextarea.value = commentTextarea.value.substring(0, 500);
                charCount.textContent = '500/500 znaków (limit osiągnięty)';
            }
        });

        // Form validation
        document.getElementById('opinionForm')?.addEventListener('submit', (e) => {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Proszę wybrać ocenę klikając na gwiazdki!');
                return;
            }

            if (commentTextarea.value.length < 10) {
                e.preventDefault();
                alert('Komentarz musi mieć co najmniej 10 znaków!');
                return;
            }
        });

        // Initialize existing values if any
        const existingRating = ratingInput.value;
        if (existingRating) {
            stars.forEach((star, index) => {
                if (index < existingRating) {
                    star.classList.add('active');
                }
            });
        }

        // Initialize character count
        if (commentTextarea) {
            charCount.textContent = `${commentTextarea.value.length}/500 znaków`;
        }
    </script>
</body>

</html>