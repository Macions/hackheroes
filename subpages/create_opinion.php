<?php
session_start();
include("../global/connection.php");
include("../global/nav_global.php");

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Pobierz projekty użytkownika do wyboru
$user_projects = [];
try {
    $projects_stmt = $conn->prepare("
        SELECT p.id, p.name 
        FROM projects p 
        WHERE p.founder_id = ? OR EXISTS (
            SELECT 1 FROM project_members pm 
            WHERE pm.project_id = p.id AND pm.user_id = ?
        )
        ORDER BY p.created_at DESC
    ");
    $projects_stmt->bind_param("ii", $user_id, $user_id);
    $projects_stmt->execute();
    $user_projects = $projects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $projects_stmt->close();
} catch (Exception $e) {
    $user_projects = [];
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    // Walidacja
    if (empty($project_id) || empty($rating) || empty($comment)) {
        $error = "Wszystkie pola są wymagane!";
    } elseif (strlen($comment) < 10) {
        $error = "Komentarz musi mieć co najmniej 10 znaków!";
    } else {
        try {
            // Sprawdź czy użytkownik ma dostęp do projektu
            $access_stmt = $conn->prepare("
                SELECT 1 FROM projects 
                WHERE id = ? AND (founder_id = ? OR EXISTS (
                    SELECT 1 FROM project_members 
                    WHERE project_id = ? AND user_id = ?
                ))
            ");
            $access_stmt->bind_param("iiii", $project_id, $user_id, $project_id, $user_id);
            $access_stmt->execute();
            $has_access = $access_stmt->get_result()->num_rows > 0;
            $access_stmt->close();

            if (!$has_access) {
                $error = "Nie masz dostępu do tego projektu!";
            } else {
                // Dodaj opinię do bazy
                $insert_stmt = $conn->prepare("
                    INSERT INTO reviews (user_id, project_id, rating, comment, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $insert_stmt->bind_param("iiis", $user_id, $project_id, $rating, $comment);

                if ($insert_stmt->execute()) {
                    $success = "Twoja opinia została dodana pomyślnie!";
                    // Reset form
                    $_POST = [];
                } else {
                    $error = "Wystąpił błąd podczas dodawania opinii.";
                }
                $insert_stmt->close();
            }
        } catch (Exception $e) {
            $error = "Wystąpił błąd: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj opinię - TeenCollab</title>
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

        .form-select,
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

        .form-select:focus,
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

        .no-projects {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
        }

        .no-projects h3 {
            font-family: "Inter", sans-serif;
            color: #6b7280;
            margin-bottom: 1rem;
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
                    <h1>Dodaj opinię</h1>
                    <p>Podziel się swoimi doświadczeniami i zainspiruj innych</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (empty($user_projects)): ?>
                    <div class="no-projects">
                        <h3>Nie masz jeszcze projektów do oceny</h3>
                        <p>Dołącz do projektu lub stwórz własny, aby móc dodać opinię.</p>
                        <div class="form-actions">
                            <a href="projects.php" class="btn-submit">Przeglądaj projekty</a>
                            <a href="create_project.php" class="btn-cancel">Stwórz projekt</a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" id="opinionForm">
                        <div class="form-group">
                            <label class="form-label" for="project_id">Wybierz projekt *</label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">-- Wybierz projekt --</option>
                                <?php foreach ($user_projects as $project): ?>
                                    <option value="<?php echo $project['id']; ?>" <?php echo ($_POST['project_id'] ?? '') == $project['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ocena projektu *</label>
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
                                placeholder="Napisz co sądzisz o projekcie, jakie były Twoje doświadczenia, co Ci się podobało..."
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
        document.getElementById('opinionForm').addEventListener('submit', (e) => {
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

        // Initialize existing rating if any
        const existingRating = ratingInput.value;
        if (existingRating) {
            stars.forEach((star, index) => {
                if (index < existingRating) {
                    star.classList.add('active');
                }
            });
        }

        // Initialize character count
        charCount.textContent = `${commentTextarea.value.length}/500 znaków`;
    </script>
</body>

</html>