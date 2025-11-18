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

// Pobierz dane projektu
try {
    $sql = "SELECT * FROM projects WHERE id = ? AND founder_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        throw new Exception("Błąd przygotowania zapytania: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $projectId, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Błąd wykonania zapytania: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    
    if (!$project) {
        throw new Exception("Projekt nie istnieje lub nie masz uprawnień do jego edycji");
    }
    
    $stmt->close();

    // Pobierz kategorie projektu
    $selectedCategories = [];
    $catStmt = $conn->prepare("
        SELECT c.name 
        FROM categories c 
        JOIN project_categories pc ON c.id = pc.category_id 
        WHERE pc.project_id = ?
    ");
    if ($catStmt) {
        $catStmt->bind_param("i", $projectId);
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        while ($row = $catResult->fetch_assoc()) {
            $selectedCategories[] = $row['name'];
        }
        $catStmt->close();
    }

    // Pobierz cele projektu
    $goals = [];
    $goalStmt = $conn->prepare("SELECT id, description FROM goals WHERE project_id = ?");
    if ($goalStmt) {
        $goalStmt->bind_param("i", $projectId);
        $goalStmt->execute();
        $goalResult = $goalStmt->get_result();
        while ($row = $goalResult->fetch_assoc()) {
            $goals[] = $row;
        }
        $goalStmt->close();
    }

    // Pobierz umiejętności projektu
    $selectedSkills = [];
    $skillStmt = $conn->prepare("
        SELECT s.name 
        FROM skills s 
        JOIN project_skills ps ON s.id = ps.skill_id 
        WHERE ps.project_id = ?
    ");
    if ($skillStmt) {
        $skillStmt->bind_param("i", $projectId);
        $skillStmt->execute();
        $skillResult = $skillStmt->get_result();
        while ($row = $skillResult->fetch_assoc()) {
            $selectedSkills[] = $row['name'];
        }
        $skillStmt->close();
    }

    // Pobierz zadania projektu
    $tasks = [];
    $taskStmt = $conn->prepare("SELECT id, name, description, priority FROM tasks WHERE project_id = ?");
    if ($taskStmt) {
        $taskStmt->bind_param("i", $projectId);
        $taskStmt->execute();
        $taskResult = $taskStmt->get_result();
        while ($row = $taskResult->fetch_assoc()) {
            $tasks[] = $row;
        }
        $taskStmt->close();
    }

} catch (Exception $e) {
    die("Błąd: " . $e->getMessage());
}

// Mapowanie kategorii
$categoryMap = [
    'technology' => 'Technologia',
    'social' => 'Społeczne',
    'education' => 'Edukacja',
    'ecology' => 'Ekologia',
    'business' => 'Biznes',
    'art' => 'Sztuka',
    'media' => 'Media'
];

// Mapowanie umiejętności
$skillMap = [
    'programming' => 'Programowanie',
    'design' => 'Grafika',
    'social-media' => 'Social Media',
    'logistics' => 'Logistyka',
    'copywriting' => 'Copywriting',
    'video' => 'Obsługa kamer',
    'ai' => 'AI Tools'
];

// Mapowanie priorytetów
$priorityMap = [
    'low' => 'Niski',
    'medium' => 'Średni',
    'high' => 'Wysoki'
];

// Obsługa formularza edycji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Podstawowe dane projektu
        $projectName = trim($_POST['projectName'] ?? '');
        $shortDescription = trim($_POST['shortDescription'] ?? '');
        $fullDescription = trim($_POST['fullDescription'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        $visibility = $_POST['visibility'] ?? 'public';
        $status = $_POST['status'] ?? 'active';
        $allowApplications = isset($_POST['allowApplications']) ? 1 : 0;
        $autoAccept = isset($_POST['autoAccept']) ? 1 : 0;
        $seoTags = trim($_POST['seoTags'] ?? '');

        // Aktualizacja projektu
        $updateStmt = $conn->prepare("
    UPDATE projects 
    SET name = ?, short_description = ?, full_description = ?, deadline = ?, 
        visibility = ?, status = ?, allow_applications = ?, auto_accept = ?, 
        seo_tags = ?, updated_at = NOW()
    WHERE id = ? AND founder_id = ?
");

if (!$updateStmt) {
    die("SQL ERROR: " . $conn->error);
}

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

        // Thumbnail
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['thumbnail']['type'], $allowed)) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Konkurs/photos/projects/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $fileName = "project_{$projectId}_" . time() . ".$ext";
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $filePath)) {
                    $thumbnailUrl = "../photos/projects/$fileName";
                    $thumbStmt = $conn->prepare("UPDATE projects SET thumbnail = ? WHERE id = ?");
                    $thumbStmt->bind_param("si", $thumbnailUrl, $projectId);
                    $thumbStmt->execute();
                    $thumbStmt->close();
                }
            }
        }

        $conn->commit();
        
        header("Location: project.php?id=" . $projectId . "&success=1");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Błąd podczas aktualizacji projektu: " . $e->getMessage();
    }
}
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
                    <li><a href="index.php">Strona główna</a></li>
                    <li><a href="projekty.php">Projekty</a></li>
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

            <form id="editProjectForm" class="edit-project-form" method="POST" enctype="multipart/form-data">
                <!-- Podstawowe informacje -->
                <section class="form-section">
                    <h2>Podstawowe informacje</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="projectName">Nazwa projektu *</label>
                            <input type="text" id="projectName" name="projectName" 
                                   value="<?php echo htmlspecialchars($project['name']); ?>" 
                                   maxlength="80" required>
                            <div class="char-counter">
                                <span id="nameCounter"><?php echo strlen($project['name']); ?></span>/80 znaków
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="projectStatus">Status projektu</label>
                            <select id="projectStatus" name="status">
                                <option value="active" <?php echo ($project['status'] == 'active') ? 'selected' : ''; ?>>Aktywny</option>
                                <option value="paused" <?php echo ($project['status'] == 'paused') ? 'selected' : ''; ?>>Wstrzymany</option>
                                <option value="completed" <?php echo ($project['status'] == 'completed') ? 'selected' : ''; ?>>Zakończony</option>
                                <option value="draft" <?php echo ($project['status'] == 'draft') ? 'selected' : ''; ?>>Szkic</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shortDescription">Krótki opis *</label>
                        <textarea id="shortDescription" name="shortDescription" maxlength="300" required><?php echo htmlspecialchars($project['short_description']); ?></textarea>
                        <div class="char-counter">
                            <span id="descCounter"><?php echo strlen($project['short_description']); ?></span>/300 znaków
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fullDescription">Pełny opis</label>
                        <textarea id="fullDescription" name="fullDescription" rows="6"><?php echo htmlspecialchars($project['full_description'] ?? ''); ?></textarea>
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

                <!-- Kategorie -->
                <section class="form-section">
                    <h2>Kategorie</h2>
                    <div class="categories-grid">
                        <?php foreach ($categoryMap as $key => $name): ?>
                            <label class="category-checkbox">
                                <input type="checkbox" name="categories[]" value="<?php echo $key; ?>"
                                    <?php echo in_array($name, $selectedCategories) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <span class="category-label">
                                    <span class="category-icon">
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
                                        echo $icons[$key] ?? '📁';
                                        ?>
                                    </span>
                                    <?php echo $name; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Umiejętności -->
                <section class="form-section">
                    <h2>Wymagane umiejętności</h2>
                    <div class="skills-grid">
                        <?php foreach ($skillMap as $key => $name): ?>
                            <label class="skill-checkbox">
                                <input type="checkbox" name="skills[]" value="<?php echo $key; ?>"
                                    <?php echo in_array($name, $selectedSkills) ? 'checked' : ''; ?>>
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
                                <input type="checkbox" name="allowApplications" 
                                    <?php echo $project['allow_applications'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Pozwalaj użytkownikom składać zgłoszenia do projektu
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="autoAccept"
                                    <?php echo $project['auto_accept'] ? 'checked' : ''; ?>>
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
                    <button type="submit" class="btn-primary">Zapisz zmiany</button>
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
                        <p>Platforma dla młodych zmieniaczy świata</p>
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