<?php
session_start();
include("global/connection.php");
include("global/log_action.php"); // Funkcja logowania

// Sprawdzenie zalogowania
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: join.php");
    exit();
}

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

$projectId = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$projectId) {
    header("Location: projects.php");
    exit();
}

try {
    // Pobranie projektu
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND founder_id = ?");
    if (!$stmt)
        throw new Exception("Błąd przygotowania zapytania: " . $conn->error);
    $stmt->bind_param("ii", $projectId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();

    if (!$project)
        throw new Exception("Projekt nie istnieje lub brak uprawnień");

    logAction($conn, $userId, $userEmail, "project_edit_page_accessed", "ID projektu: $projectId");

    // Pobranie kategorii
    $selectedCategories = [];
    $stmt = $conn->prepare("
        SELECT c.name 
        FROM categories c
        JOIN project_categories pc ON c.id = pc.category_id
        WHERE pc.project_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $selectedCategories[] = $row['name'];
        }
        $stmt->close();
    }

    // Pobranie umiejętności
    $selectedSkills = [];
    $stmt = $conn->prepare("
        SELECT s.name 
        FROM skills s
        JOIN project_skills ps ON s.id = ps.skill_id
        WHERE ps.project_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $selectedSkills[] = $row['name'];
        }
        $stmt->close();
    }

    // Pobranie celów
    $goals = [];
    $stmt = $conn->prepare("SELECT id, description, status FROM goals WHERE project_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $goals[] = $row;
        }
        $stmt->close();
    }

    // Obsługa formularza
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Dodawanie nowego celu
        if (isset($_POST['add_goal'])) {
            $new_goal = trim($_POST['new_goal'] ?? '');
            if ($new_goal) {
                $stmt = $conn->prepare("INSERT INTO goals (project_id, description, status) VALUES (?, ?, 0)");
                $stmt->bind_param("is", $projectId, $new_goal);
                $stmt->execute();
                $stmt->close();
                logAction($conn, $userId, $userEmail, "project_goal_added", "ID projektu: $projectId, Cel: " . substr($new_goal, 0, 50));
                header("Location: edit_project.php?id=$projectId");
                exit();
            }
        }

        // Aktualizacja projektu i celów
        if (isset($_POST['save_project'])) {
            $conn->begin_transaction();
            try {
                $projectName = trim($_POST['projectName'] ?? '');
                $shortDescription = trim($_POST['shortDescription'] ?? '');
                $fullDescription = trim($_POST['fullDescription'] ?? '');
                $deadline = $_POST['deadline'] ?? null;
                $visibility = $_POST['visibility'] ?? 'public';
                $status = $_POST['status'] ?? 'active';
                $allowApplications = isset($_POST['allowApplications']) ? 1 : 0;
                $autoAccept = isset($_POST['autoAccept']) ? 1 : 0;
                $seoTags = trim($_POST['seoTags'] ?? '');

                // Zbieranie informacji o zmianach
                $changes = [];

                if ($projectName !== $project['name']) {
                    $changes[] = "Nazwa: '{$project['name']}' -> '$projectName'";
                }
                if ($shortDescription !== $project['short_description']) {
                    $changes[] = "Krótki opis zmieniony";
                }
                if ($fullDescription !== ($project['full_description'] ?? '')) {
                    $changes[] = "Pełny opis zmieniony";
                }
                if ($deadline !== $project['deadline']) {
                    $changes[] = "Termin: '{$project['deadline']}' -> '$deadline'";
                }
                if ($visibility !== $project['visibility']) {
                    $changes[] = "Widoczność: '{$project['visibility']}' -> '$visibility'";
                }
                if ($status !== $project['status']) {
                    $changes[] = "Status: '{$project['status']}' -> '$status'";
                }
                if ($allowApplications != $project['allow_applications']) {
                    $changes[] = "Zgłoszenia: " . ($allowApplications ? 'WŁĄCZONE' : 'WYŁĄCZONE');
                }
                if ($autoAccept != $project['auto_accept']) {
                    $changes[] = "Auto-akceptacja: " . ($autoAccept ? 'WŁĄCZONA' : 'WYŁĄCZONA');
                }
                if ($seoTags !== ($project['seo_tags'] ?? '')) {
                    $changes[] = "Tagi SEO zmienione";
                }

                // Aktualizacja projektu
                $updateStmt = $conn->prepare("
                    UPDATE projects
                    SET name=?, short_description=?, full_description=?, deadline=?,
                        visibility=?, status=?, allow_applications=?, auto_accept=?,
                        seo_tags=?, updated_at=NOW()
                    WHERE id=? AND founder_id=?
                ");
                if (!$updateStmt)
                    throw new Exception($conn->error);
                $updateStmt->bind_param(
                    "ssssiiisiii",
                    $projectName,
                    $shortDescription,
                    $fullDescription,
                    $deadline,
                    $visibility,
                    $status,
                    $allowApplications,
                    $autoAccept,
                    $seoTags,
                    $projectId,
                    $userId
                );
                $updateStmt->execute();
                $updateStmt->close();

                // Aktualizacja istniejących celów
                $goalChanges = [];
                if (isset($_POST['goal_ids']) && is_array($_POST['goal_ids'])) {
                    foreach ($_POST['goal_ids'] as $goal_id) {
                        $goal_text = $_POST['goal_text'][$goal_id] ?? '';
                        $completed = isset($_POST['completed_goals'][$goal_id]) ? 1 : 0;

                        if (!empty($goal_text)) {
                            // Znajdź oryginalny cel
                            $originalGoal = null;
                            foreach ($goals as $goal) {
                                if ($goal['id'] == $goal_id) {
                                    $originalGoal = $goal;
                                    break;
                                }
                            }

                            if ($originalGoal) {
                                if ($goal_text !== $originalGoal['description']) {
                                    $goalChanges[] = "Cel $goal_id: opis zmieniony";
                                }
                                if ($completed != $originalGoal['status']) {
                                    $statusText = $completed ? 'ukończony' : 'w trakcie';
                                    $goalChanges[] = "Cel $goal_id: status -> $statusText";
                                }
                            }

                            $stmt = $conn->prepare("UPDATE goals SET description = ?, status = ? WHERE id = ?");
                            $stmt->bind_param("sii", $goal_text, $completed, $goal_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }

                // Dodawanie nowego celu (jeśli został wpisany)
                $newGoalAdded = false;
                if (!empty(trim($_POST['new_goal'] ?? ''))) {
                    $new_goal = trim($_POST['new_goal']);
                    $stmt = $conn->prepare("INSERT INTO goals (project_id, description, status) VALUES (?, ?, 0)");
                    $stmt->bind_param("is", $projectId, $new_goal);
                    $stmt->execute();
                    $stmt->close();
                    $newGoalAdded = true;
                    $changes[] = "Dodano nowy cel: " . substr($new_goal, 0, 50);
                }

                // Logowanie szczegółowych zmian
                $changeDetails = "";
                if (!empty($changes)) {
                    $changeDetails .= "Zmiany w projekcie: " . implode(", ", $changes) . ". ";
                }
                if (!empty($goalChanges)) {
                    $changeDetails .= "Zmiany w celach: " . implode(", ", $goalChanges) . ". ";
                }
                if ($newGoalAdded) {
                    logAction($conn, $userId, $userEmail, "project_goal_added", "ID projektu: $projectId, Cel: " . substr($new_goal, 0, 50));
                }

                if (!empty($changeDetails)) {
                    logAction($conn, $userId, $userEmail, "project_updated", "ID projektu: $projectId. " . $changeDetails);
                } else {
                    logAction($conn, $userId, $userEmail, "project_updated", "ID projektu: $projectId (brak zmian)");
                }

                // Upload miniatury
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                    if (in_array($_FILES['thumbnail']['type'], $allowed)) {
                        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Konkurs/photos/projects/';
                        if (!file_exists($uploadDir))
                            mkdir($uploadDir, 0755, true);
                        $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                        $fileName = "project_{$projectId}_" . time() . ".$ext";
                        $filePath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $filePath)) {
                            $thumbUrl = "../photos/projects/$fileName";
                            $stmt = $conn->prepare("UPDATE projects SET thumbnail=? WHERE id=?");
                            $stmt->bind_param("si", $thumbUrl, $projectId);
                            $stmt->execute();
                            $stmt->close();
                            logAction($conn, $userId, $userEmail, "project_thumbnail_updated", "ID projektu: $projectId, Plik: $fileName");
                        }
                    }
                }

                $conn->commit();
                header("Location: project.php?id=$projectId&success=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                logAction($conn, $userId, $userEmail, "project_edit_failed", "ID projektu: $projectId, Błąd: " . $e->getMessage());
                $error = "Błąd podczas aktualizacji projektu: " . $e->getMessage();
            }
        }
    }

} catch (Exception $e) {
    die("Błąd: " . $e->getMessage());
}

// Mapy kategorii, umiejętności, priorytetów
$categoryMap = [
    'technology' => 'Technologia',
    'social' => 'Społeczne',
    'education' => 'Edukacja',
    'ecology' => 'Ekologia',
    'business' => 'Biznes',
    'art' => 'Sztuka',
    'media' => 'Media'
];

$skillMap = [
    'programming' => 'Programowanie',
    'design' => 'Grafika',
    'social-media' => 'Social Media',
    'logistics' => 'Logistyka',
    'copywriting' => 'Copywriting',
    'video' => 'Obsługa kamer',
    'ai' => 'AI Tools'
];

$priorityMap = ['low' => 'Niski', 'medium' => 'Średni', 'high' => 'Wysoki'];
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj projekt - <?php echo htmlspecialchars($project['name']); ?> | TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/edit_project_style.css">
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
                    <li><a href="project.php?id=<?php echo $projectId; ?>">Powrót do projektu</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <div class="edit-project-container">
            <div class="edit-header">
                <h1>Edytuj projekt</h1>
                <p>Aktualizuj informacje o swoim projekcie "<?php echo htmlspecialchars($project['name']); ?>"</p>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">Projekt został pomyślnie zaktualizowany!</div>
                <?php endif; ?>
            </div>

            <!-- JEDEN główny formularz -->
            <form id="editProjectForm" class="edit-project-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_project" value="1">

                <!-- Podstawowe informacje -->
                <section class="form-section">
                    <h2>Podstawowe informacje</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="projectName">Nazwa projektu *</label>
                            <input type="text" id="projectName" name="projectName"
                                value="<?php echo htmlspecialchars($project['name']); ?>" maxlength="80" required>
                            <div class="char-counter">
                                <span id="nameCounter"><?php echo strlen($project['name']); ?></span>/80 znaków
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="projectStatus">Status projektu</label>
                            <select id="projectStatus" name="status">
                                <option value="active" <?php echo ($project['status'] == 'active') ? 'selected' : ''; ?>>
                                    Aktywny</option>
                                <option value="paused" <?php echo ($project['status'] == 'paused') ? 'selected' : ''; ?>>
                                    Wstrzymany</option>
                                <option value="completed" <?php echo ($project['status'] == 'completed') ? 'selected' : ''; ?>>Zakończony</option>
                                <option value="draft" <?php echo ($project['status'] == 'draft') ? 'selected' : ''; ?>>
                                    Szkic</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shortDescription">Krótki opis *</label>
                        <textarea id="shortDescription" name="shortDescription" maxlength="300"
                            required><?php echo htmlspecialchars($project['short_description']); ?></textarea>
                        <div class="char-counter">
                            <span id="descCounter"><?php echo strlen($project['short_description']); ?></span>/300
                            znaków
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fullDescription">Pełny opis</label>
                        <textarea id="fullDescription" name="fullDescription"
                            rows="6"><?php echo htmlspecialchars($project['full_description'] ?? ''); ?></textarea>
                    </div>
                </section>

                <!-- Miniatura -->
                <section class="form-section">
                    <h2>Miniatura projektu</h2>
                    <div class="thumbnail-upload">
                        <div class="current-thumbnail">
                            <?php if ($project['thumbnail']): ?>
                                <img src="<?php echo htmlspecialchars($project['thumbnail']); ?>" alt="Aktualna miniatura">
                            <?php else: ?>
                                <div class="no-thumbnail">Brak miniatury</div>
                            <?php endif; ?>
                        </div>
                        <div class="upload-controls">
                            <input type="file" id="thumbnailUpload" name="thumbnail" accept="image/*">
                            <label for="thumbnailUpload" class="btn-secondary">Zmień miniaturę</label>
                            <span class="file-info">Maksymalny rozmiar: 2MB</span>
                        </div>
                    </div>
                </section>

                <!-- 🎯 Cele projektu - TERAZ W GŁÓWNYM FORMULARZU -->
                <section class="form-section">
                    <h2>Cele projektu</h2>
                    <div class="goals-management">
                        <!-- Dodawanie nowego celu -->
                        <div class="goal-input-group">
                            <input type="text" name="new_goal" placeholder="Dodaj nowy cel..." maxlength="255">
                            <button type="submit" name="add_goal" class="btn-primary btn-sm">Dodaj cel</button>
                        </div>

                        <!-- Lista istniejących celów -->
                        <div class="goals-list">
                            <?php if (!empty($goals)): ?>
                                <?php foreach ($goals as $goal): ?>
                                    <?php
                                    $goalId = $goal['id'];
                                    $goalDesc = htmlspecialchars($goal['description']);
                                    $checked = $goal['status'] == 1 ? 'checked' : '';
                                    ?>
                                    <div class="goal-item">
                                        <div class="goal-content">
                                            <div class="goal-checkbox">
                                                <input type="checkbox" name="completed_goals[<?= $goalId ?>]"
                                                    id="goal_<?= $goalId ?>" <?= $checked ?>>
                                                <label for="goal_<?= $goalId ?>" class="checkmark"></label>
                                            </div>

                                            <input type="text" name="goal_text[<?= $goalId ?>]" value="<?= $goalDesc ?>"
                                                class="goal-input" placeholder="Opis celu...">

                                            <input type="hidden" name="goal_ids[]" value="<?= $goalId ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-goals">
                                    <p>🎯 Nie masz jeszcze żadnych celów. Dodaj pierwszy cel!</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </section>

                <!-- Kategorie -->
                <section class="form-section">
                    <h2>Kategorie</h2>
                    <div class="categories-grid">
                        <?php
                        $icons = [
                            'technology' => '💻',
                            'social' => '👥',
                            'education' => '🎓',
                            'ecology' => '🌱',
                            'business' => '💼',
                            'art' => '🎨',
                            'media' => '📱'
                        ];

                        foreach ($categoryMap as $key => $name) {
                            $isChecked = in_array($key, $selectedCategories) ? 'checked' : '';
                            $categoryIcon = $icons[$key] ?? '📁';
                            ?>
                            <label class="category-checkbox">
                                <input type="checkbox" name="categories[]" value="<?= $key ?>" <?= $isChecked ?>>
                                <span class="checkmark"></span>
                                <span class="category-label">
                                    <span class="category-icon"><?= $categoryIcon ?></span>
                                    <?= $name ?>
                                </span>
                            </label>
                        <?php } ?>
                    </div>
                </section>

                <!-- Umiejętności -->
                <section class="form-section">
                    <h2>Wymagane umiejętności</h2>
                    <div class="skills-grid">
                        <?php foreach ($skillMap as $key => $name): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" value="<?php echo $key; ?>" <?php echo in_array($key, $selectedSkills) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <?php echo $name; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Ustawienia zaawansowane -->
                <section class="form-section">
                    <h2>Ustawienia zaawansowane</h2>
                    <div class="advanced-settings">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="projectDeadline">Termin zakończenia</label>
                                <input type="date" id="projectDeadline" name="deadline"
                                    value="<?php echo $project['deadline'] ?? ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="projectVisibility">Widoczność</label>
                                <select id="projectVisibility" name="visibility">
                                    <option value="public" <?php echo ($project['visibility'] == 'public') ? 'selected' : ''; ?>>Publiczny</option>
                                    <option value="private" <?php echo ($project['visibility'] == 'private') ? 'selected' : ''; ?>>Prywatny</option>
                                </select>
                            </div>
                        </div>

                        <div class="settings-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allowApplications" <?php echo (int) $project['allow_applications'] === 1 ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Pozwalaj użytkownikom składać zgłoszenia do projektu
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="autoAccept" <?php echo $project['auto_accept'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Automatycznie przyjmuj nowych członków
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="seoTags">Tagi SEO</label>
                            <input type="text" id="seoTags" name="seoTags"
                                value="<?php echo htmlspecialchars($project['seo_tags'] ?? ''); ?>"
                                placeholder="Dodaj tagi oddzielone przecinkami">
                        </div>
                    </div>
                </section>

                <!-- Przyciski akcji -->
                <section class="form-actions">
                    <a href="project.php?id=<?php echo $projectId; ?>" class="btn-secondary">Anuluj</a>
                    <button type="submit" class="btn-primary" name="save_project">Zapisz zmiany</button>
                </section>
            </form>
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

    <script src="../scripts/edit_project.js"></script>
</body>

</html>