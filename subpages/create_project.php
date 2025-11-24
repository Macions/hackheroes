<?php
ob_start();
session_start();

if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Musisz być zalogowany, aby utworzyć projekt'
    ]);
    exit();
}

include("global/connection.php");
include("global/nav_global.php");
include("global/log_action.php");

$userId = $_SESSION["user_id"];
$userEmail = $_SESSION["user_email"] ?? '';

function closeStatement($stmt)
{
    if ($stmt && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}

function validateInput($data, $maxLength = null)
{
    if (!isset($data)) {
        return '';
    }
    $cleaned = trim($data);
    if ($maxLength !== null && strlen($cleaned) > $maxLength) {
        throw new Exception("Przekroczono maksymalną długość: $maxLength znaków");
    }
    return $cleaned;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();

    $stmt = null;
    $categoryStmt = null;
    $projectCategoryStmt = null;
    $goalStmt = null;
    $skillStmt = null;
    $projectSkillStmt = null;
    $taskStmt = null;
    $thumbStmt = null;
    $memberStmt = null;

    try {
        $projectName = validateInput($_POST['projectName'] ?? '', 80);
        $shortDescription = validateInput($_POST['shortDescription'] ?? '', 300);
        $fullDescription = validateInput($_POST['fullDescription'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        $visibility = $_POST['visibility'] ?? 'public';
        $allowApplications = isset($_POST['allowApplications']) ? 1 : 0;
        $autoAccept = isset($_POST['autoAccept']) ? 1 : 0;
        $seoTags = validateInput($_POST['seoTags'] ?? '', 500);
        $location = 'Online';
        $country = validateInput($_POST['country'] ?? '', 100);

        if (empty($projectName)) {
            throw new Exception("Nazwa projektu jest wymagana");
        }

        if (empty($shortDescription)) {
            throw new Exception("Krótki opis projektu jest wymagany");
        }

        if (isset($_POST['locationType']) && $_POST['locationType'] === 'specific') {
            $specificLocation = validateInput($_POST['location'] ?? '', 200);
            if (!empty($specificLocation)) {
                $location = $specificLocation;
            }
        }

        if (!empty($deadline)) {
            $deadlineDate = DateTime::createFromFormat('Y-m-d', $deadline);
            $today = new DateTime();
            if (!$deadlineDate || $deadlineDate < $today) {
                throw new Exception("Nieprawidłowa data deadline. Data musi być w przyszłości.");
            }
        }

        if (!$conn->begin_transaction()) {
            throw new Exception("Nie można rozpocząć transakcji");
        }

        $stmt = $conn->prepare("
            INSERT INTO projects 
            (name, short_description, location, country, full_description, deadline, visibility, founder_id, allow_applications, auto_accept, seo_tags, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        if ($stmt === false) {
            throw new Exception("Błąd przygotowania zapytania");
        }

        if (!$stmt->bind_param("sssssssiiis", $projectName, $shortDescription, $location, $country, $fullDescription, $deadline, $visibility, $userId, $allowApplications, $autoAccept, $seoTags)) {
            throw new Exception("Błąd powiązania parametrów");
        }

        if (!$stmt->execute()) {
            throw new Exception("Błąd wykonania zapytania");
        }

        $projectId = $conn->insert_id;
        closeStatement($stmt);

        logAction($conn, $userId, $userEmail, "project_created", "ID: $projectId, Name: $projectName");

        if (isset($_POST['categories'])) {
            $categories = is_array($_POST['categories']) ? $_POST['categories'] : [$_POST['categories']];
            $allowedCategories = ['technology', 'social', 'education', 'ecology', 'business', 'art', 'media'];

            $categoryMap = [
                'technology' => 'Technologia',
                'social' => 'Społeczne',
                'education' => 'Edukacja',
                'ecology' => 'Ekologia',
                'business' => 'Biznes',
                'art' => 'Sztuka',
                'media' => 'Media'
            ];

            $categoryStmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
            $projectCategoryStmt = $conn->prepare("INSERT INTO project_categories (project_id, category_id) VALUES (?, ?)");

            foreach ($categories as $categoryKey) {
                if (!in_array($categoryKey, $allowedCategories)) {
                    continue;
                }

                $categoryName = $categoryMap[$categoryKey];

                $categoryStmt->bind_param("s", $categoryName);
                if (!$categoryStmt->execute()) {
                    throw new Exception("Błąd tworzenia kategorii");
                }

                $checkCat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $checkCat->bind_param("s", $categoryName);
                $checkCat->execute();
                $checkCat->bind_result($categoryId);
                $checkCat->fetch();
                $checkCat->close();

                if ($categoryId) {
                    $projectCategoryStmt->bind_param("ii", $projectId, $categoryId);
                    if (!$projectCategoryStmt->execute()) {
                        throw new Exception("Błąd przypisywania kategorii");
                    }
                }
            }
            closeStatement($categoryStmt);
            closeStatement($projectCategoryStmt);
        }

        if (isset($_POST['goals']) && is_array($_POST['goals'])) {
            $goalStmt = $conn->prepare("INSERT INTO goals (project_id, description) VALUES (?, ?)");

            foreach ($_POST['goals'] as $goal) {
                $goal = validateInput($goal, 500);
                if (!empty($goal)) {
                    $goalStmt->bind_param("is", $projectId, $goal);
                    if (!$goalStmt->execute()) {
                        throw new Exception("Błąd dodawania celu");
                    }
                }
            }
            closeStatement($goalStmt);
        }

        if (isset($_POST['skills'])) {
            $skills = is_array($_POST['skills']) ? $_POST['skills'] : [$_POST['skills']];
            $allowedSkills = ['programming', 'design', 'social-media', 'logistics', 'copywriting', 'video', 'ai'];

            $skillMap = [
                'programming' => 'Programowanie',
                'design' => 'Grafika',
                'social-media' => 'Social Media',
                'logistics' => 'Logistyka',
                'copywriting' => 'Copywriting',
                'video' => 'Obsługa kamer',
                'ai' => 'AI Tools'
            ];

            $skillStmt = $conn->prepare("INSERT IGNORE INTO skills (name) VALUES (?)");
            $projectSkillStmt = $conn->prepare("INSERT INTO project_skills (project_id, skill_id) VALUES (?, ?)");

            foreach ($skills as $skillKey) {
                if (!in_array($skillKey, $allowedSkills)) {
                    continue;
                }

                $skillName = $skillMap[$skillKey];

                $skillStmt->bind_param("s", $skillName);
                if (!$skillStmt->execute()) {
                    throw new Exception("Błąd tworzenia umiejętności");
                }

                $checkSkill = $conn->prepare("SELECT id FROM skills WHERE name = ?");
                $checkSkill->bind_param("s", $skillName);
                $checkSkill->execute();
                $checkSkill->bind_result($skillId);
                $checkSkill->fetch();
                $checkSkill->close();

                if ($skillId) {
                    $projectSkillStmt->bind_param("ii", $projectId, $skillId);
                    if (!$projectSkillStmt->execute()) {
                        throw new Exception("Błąd przypisywania umiejętności");
                    }
                }
            }
            closeStatement($skillStmt);
            closeStatement($projectSkillStmt);
        }

        if (isset($_POST['tasks']) && is_array($_POST['tasks'])) {
            $taskStmt = $conn->prepare("
                INSERT INTO tasks (project_id, name, description, priority)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($_POST['tasks'] as $task) {
                if (!empty(trim($task['name'] ?? ''))) {
                    $name = validateInput($task['name'], 200);
                    $description = validateInput($task['description'] ?? '', 1000);
                    $priority = $task['priority'] ?? 'medium';

                    if (!in_array($priority, ['low', 'medium', 'high'])) {
                        $priority = 'medium';
                    }

                    $taskStmt->bind_param("isss", $projectId, $name, $description, $priority);
                    if (!$taskStmt->execute()) {
                        throw new Exception("Błąd dodawania zadania");
                    }
                }
            }
            closeStatement($taskStmt);
        }

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'Plik jest zbyt duży (limit serwera)',
                    UPLOAD_ERR_FORM_SIZE => 'Plik jest zbyt duży (limit formularza)',
                    UPLOAD_ERR_PARTIAL => 'Plik został tylko częściowo wgrany',
                    UPLOAD_ERR_NO_FILE => 'Brak pliku',
                    UPLOAD_ERR_NO_TMP_DIR => 'Brak folderu tymczasowego',
                    UPLOAD_ERR_CANT_WRITE => 'Błąd zapisu na dysk',
                    UPLOAD_ERR_EXTENSION => 'Rozszerzenie PHP zatrzymało upload'
                ];
                $errorMsg = $uploadErrors[$_FILES['thumbnail']['error']] ?? 'Nieznany błąd uploadu';
                throw new Exception("Błąd uploadu miniaturki: $errorMsg");
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = mime_content_type($_FILES['thumbnail']['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Nieprawidłowy typ pliku. Dopuszczalne formaty: JPEG, PNG, GIF, WebP");
            }

            $maxFileSize = 5 * 1024 * 1024;
            if ($_FILES['thumbnail']['size'] > $maxFileSize) {
                throw new Exception("Plik miniaturki jest zbyt duży. Maksymalny rozmiar: 5MB");
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Konkurs/photos/projects/';

            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Nie można utworzyć folderu uploadu");
                }
            }

            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $fileName = "project_{$projectId}_" . time() . ".$ext";
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], $filePath)) {
                throw new Exception("Błąd zapisu pliku miniaturki");
            }

            $thumbnailUrl = "../photos/projects/$fileName";
            $thumbStmt = $conn->prepare("UPDATE projects SET thumbnail=? WHERE id=?");
            $thumbStmt->bind_param("si", $thumbnailUrl, $projectId);
            if (!$thumbStmt->execute()) {
                throw new Exception("Błąd aktualizacji miniaturki");
            }
            closeStatement($thumbStmt);
        }

        $memberStmt = $conn->prepare("
            INSERT INTO project_team (project_id, user_id, role)
            VALUES (?, ?, 'Założyciel')
        ");
        $memberStmt->bind_param("ii", $projectId, $userId);
        if (!$memberStmt->execute()) {
            throw new Exception("Błąd dodawania założyciela do zespołu");
        }
        closeStatement($memberStmt);

        if (!$conn->commit()) {
            throw new Exception("Błąd zatwierdzania transakcji");
        }

        ob_end_clean();

        echo json_encode([
            'success' => true,
            'message' => 'Projekt został utworzony pomyślnie!',
            'projectId' => $projectId,
            'redirect' => 'project.php?id=' . $projectId
        ]);
        exit();
    } catch (Exception $e) {
        closeStatement($stmt);
        closeStatement($categoryStmt);
        closeStatement($projectCategoryStmt);
        closeStatement($goalStmt);
        closeStatement($skillStmt);
        closeStatement($projectSkillStmt);
        closeStatement($taskStmt);
        closeStatement($thumbStmt);
        closeStatement($memberStmt);

        try {
            if ($conn && $conn instanceof mysqli && $conn->thread_id) {
                $conn->rollback();
            }
        } catch (Exception $rollbackError) {
        }

        ob_end_clean();

        logAction($conn, $userId, $userEmail, "project_creation_failed", "Error: " . $e->getMessage());

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

ob_end_flush();
logAction($conn, $userId, $userEmail, "project_creation_page_accessed", "");
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stwórz projekt - TeenCollab</title>
    <link rel="shortcut icon" href="../photos/website-logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="../styles/create_project_style.css">
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
        <div class="create-project-container">
            <div class="project-form-layout">
                <!-- Lewa kolumna - formularz -->
                <div class="form-column">
                    <div class="form-header">
                        <h1>Stwórz nowy projekt</h1>
                        <p>Wypełnij formularz, aby rozpocząć swoją przygodę z tworzeniem projektów</p>
                    </div>

                    <form id="createProjectForm" class="project-form" enctype="multipart/form-data">
                        <!-- Nazwa projektu -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Nazwa projektu</h2>
                                <span class="required">*</span>
                            </div>
                            <input type="text" id="projectName" name="projectName" maxlength="80" required
                                placeholder="Podaj krótki i treściwy tytuł projektu, np. 'EkoDom', 'SmartCity', 'AI Helper'">
                            <div class="char-counter">
                                <span id="nameCounter">0</span>/80 znaków
                            </div>
                        </section>

                        <!-- Krótki opis -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Krótki opis projektu</h2>
                                <span class="required">*</span>
                            </div>
                            <textarea id="shortDescription" name="shortDescription" maxlength="300" required
                                placeholder="Opisz swój projekt w kilku zdaniach - to będzie Twój główny 'pitch'"></textarea>
                            <div class="char-counter">
                                <span id="descCounter">0</span>/300 znaków
                            </div>
                        </section>

                        <!-- Pełny opis -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Pełny opis projektu</h2>
                                <span class="optional">opcjonalne</span>
                            </div>
                            <textarea id="fullDescription" name="fullDescription"
                                placeholder="Opisz szczegóły projektu: cele, kontekst, problemy, inspiracje..."></textarea>
                        </section>

                        <!-- Lokalizacja -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Lokalizacja projektu</h2>
                                <span class="optional">opcjonalne</span>
                            </div>
                            <div class="location-options">
                                <label class="radio-option">
                                    <input type="radio" name="locationType" value="online" checked>
                                    <span class="radio-checkmark"></span>
                                    <div class="option-content">
                                        <span class="option-title">🌐 Online</span>
                                        <span class="option-description">Projekt realizowany zdalnie</span>
                                    </div>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="locationType" value="specific">
                                    <span class="radio-checkmark"></span>
                                    <div class="option-content">
                                        <span class="option-title">📍 Konkretne miejsce</span>
                                        <span class="option-description">Projekt w określonej lokalizacji</span>
                                    </div>
                                </label>
                            </div>

                            <div class="specific-location" id="specificLocation" style="display: none;">
                                <select id="countrySelect" name="country"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem; margin-top: 1rem;">
                                    <option value="">Wybierz kraj</option>
                                </select>
                                <input type="text" name="location" id="projectLocation"
                                    placeholder="Podaj miasto lub adres, np. 'Warszawa', 'Kraków, Rynek Główny'"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 0.5rem; margin-top: 1rem;">
                            </div>

                        </section>

                        <!-- Kategorie -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Kategorie projektu</h2>
                            </div>
                            <div class="categories-grid">
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="technology">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">💻</span>
                                        Technologia
                                    </span>
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="social">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">👥</span>
                                        Społeczne
                                    </span>
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="education">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">🎓</span>
                                        Edukacja
                                    </span>
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="ecology">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">🌱</span>
                                        Ekologia
                                    </span>
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="business">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">💼</span>
                                        Biznes
                                    </span>
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="art">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">🎨</span>
                                        Sztuka
                                    </span>
                                </label>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories" value="media">
                                    <span class="checkmark"></span>
                                    <span class="category-label">
                                        <span class="category-icon">📱</span>
                                        Media
                                    </span>
                                </label>
                            </div>
                        </section>

                        <!-- Cele projektu -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Cele projektu</h2>
                            </div>
                            <div class="goals-container" id="goalsContainer">
                                <div class="goal-item">
                                    <input type="text" name="goals[]" placeholder="Dodaj pierwszy cel projektu">
                                    <button type="button" class="remove-goal" style="display: none;">×</button>
                                </div>
                            </div>
                            <button type="button" class="add-goal-btn" id="addGoalBtn">
                                <span>+ Dodaj kolejny cel</span>
                            </button>
                        </section>

                        <!-- Zespół -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Zespół projektu</h2>
                            </div>
                            <div class="team-section">
                                <div class="team-member">
                                    <div class="member-avatar">
                                        <img src="../photos/sample_person.png" alt="Twój avatar">
                                    </div>
                                    <div class="member-info">
                                        <span class="member-name">Jan Kowalski</span>
                                        <span class="member-role">Założyciel projektu</span>
                                    </div>
                                </div>
                                <p class="team-hint">Możesz dodać więcej członków po utworzeniu projektu</p>
                            </div>
                        </section>

                        <!-- Miniatura -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Miniatura projektu</h2>
                            </div>
                            <div class="thumbnail-upload">
                                <div class="upload-area" id="uploadArea">
                                    <div class="upload-icon">🖼️</div>
                                    <p>Kliknij aby dodać miniaturę</p>
                                    <span>Maksymalny rozmiar: 2MB</span>
                                    <input type="file" id="thumbnailUpload" accept="image/*" style="display: none;">
                                </div>
                                <div class="thumbnail-preview" id="thumbnailPreview" style="display: none;">
                                    <img id="previewImage" src="" alt="Podgląd miniatury">
                                    <button type="button" class="remove-thumbnail" id="removeThumbnail">×</button>
                                </div>
                            </div>
                        </section>

                        <!-- Deadline -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Termin zakończenia</h2>
                                <span class="optional">opcjonalne</span>
                            </div>
                            <input type="date" id="projectDeadline" name="deadline">
                        </section>

                        <!-- Wymagane umiejętności -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Wymagane umiejętności</h2>
                            </div>
                            <div class="skills-grid">
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="programming">
                                    <span class="checkmark"></span>
                                    Programowanie
                                </label>
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="design">
                                    <span class="checkmark"></span>
                                    Grafika
                                </label>
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="social-media">
                                    <span class="checkmark"></span>
                                    Social Media
                                </label>
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="logistics">
                                    <span class="checkmark"></span>
                                    Logistyka
                                </label>
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="copywriting">
                                    <span class="checkmark"></span>
                                    Copywriting
                                </label>
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="video">
                                    <span class="checkmark"></span>
                                    Obsługa kamer
                                </label>
                                <label class="skill-checkbox">
                                    <input type="checkbox" name="skills" value="ai">
                                    <span class="checkmark"></span>
                                    AI Tools
                                </label>
                            </div>
                            <div class="custom-skill">
                                <input type="text" id="customSkill" placeholder="Dodaj własną umiejętność">
                                <button type="button" id="addCustomSkill">+ Dodaj</button>
                            </div>
                        </section>

                        <!-- Zadania startowe -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Zadania startowe</h2>
                                <span class="optional">opcjonalne</span>
                            </div>
                            <div class="tasks-container" id="tasksContainer">
                                <!-- Zadania będą dodawane dynamicznie -->
                            </div>
                            <button type="button" class="add-task-btn" id="addTaskBtn">
                                <span>+ Dodaj zadanie</span>
                            </button>
                        </section>

                        <!-- Widoczność -->
                        <section class="form-section">
                            <div class="section-header">
                                <h2>Widoczność projektu</h2>
                            </div>
                            <div class="visibility-options">
                                <label class="radio-option">
                                    <input type="radio" name="visibility" value="public" checked>
                                    <span class="radio-checkmark"></span>
                                    <div class="option-content">
                                        <span class="option-title">Publiczny</span>
                                        <span class="option-description">Widoczny dla wszystkich użytkowników</span>
                                    </div>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="visibility" value="private">
                                    <span class="radio-checkmark"></span>
                                    <div class="option-content">
                                        <span class="option-title">Prywatny</span>
                                        <span class="option-description">Tylko dla członków projektu</span>
                                    </div>
                                </label>
                            </div>
                        </section>

                        <!-- Ustawienia zaawansowane -->
                        <section class="form-section">
                            <div class="advanced-toggle" id="advancedToggle">
                                <h2>Ustawienia zaawansowane</h2>
                                <span class="toggle-icon">▼</span>
                            </div>
                            <div class="advanced-settings" id="advancedSettings" style="display: none;">
                                <div class="setting-option">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="allowApplications">
                                        <span class="checkmark"></span>
                                        Pozwalaj użytkownikom składać zgłoszenia do projektu
                                    </label>
                                </div>
                                <div class="setting-option">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="autoAccept">
                                        <span class="checkmark"></span>
                                        Automatycznie przyjmuj nowych członków
                                    </label>
                                </div>
                                <div class="setting-option">
                                    <label>Tagi SEO</label>
                                    <input type="text" name="seoTags" placeholder="Dodaj tagi oddzielone przecinkami">
                                </div>
                            </div>
                        </section>

                        <!-- Przyciski akcji -->
                        <section class="form-actions">
                            <button type="button" class="btn-secondary" id="cancelBtn">Anuluj</button>
                            <button type="button" class="btn-secondary" id="saveDraftBtn">Zapisz jako szkic</button>
                            <button type="submit" class="btn-primary">Utwórz projekt</button>
                        </section>
                    </form>
                </div>

                <!-- Prawa kolumna - podgląd -->
                <div class="preview-column">
                    <div class="preview-card">
                        <h2>Podgląd projektu</h2>
                        <div class="preview-content">
                            <div class="preview-thumbnail" id="previewThumbnail">
                                <div class="no-thumbnail">Brak miniatury</div>
                            </div>
                            <div class="preview-details">
                                <h3 id="previewTitle">Nazwa projektu</h3>
                                <p id="previewDescription" class="preview-description">Krótki opis projektu pojawi się
                                    tutaj...</p>

                                <div class="preview-categories" id="previewCategories">
                                    <span class="no-categories">Brak kategorii</span>
                                </div>

                                <div class="preview-goals" id="previewGoals">
                                    <h4>Cele projektu</h4>
                                    <div class="goals-list">
                                        <span class="no-goals">Brak dodanych celów</span>
                                    </div>
                                </div>

                                <div class="preview-meta">
                                    <div class="meta-item">
                                        <span class="meta-label">Status:</span>
                                        <span class="meta-value">Aktywny</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Założyciel:</span>
                                        <span class="meta-value">Jan Kowalski</span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Widoczność:</span>
                                        <span class="meta-value" id="previewVisibility">Publiczny</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal zadania -->
    <div class="modal" id="taskModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dodaj zadanie</h3>
                <button class="modal-close" onclick="closeTaskModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nazwa zadania</label>
                    <input type="text" id="taskName" class="modal-input" placeholder="Wpisz nazwę zadania">
                </div>
                <div class="form-group">
                    <label>Opis zadania</label>
                    <textarea id="taskDescription" class="modal-textarea"
                        placeholder="Opisz szczegóły zadania"></textarea>
                </div>
                <div class="form-group">
                    <label>Priorytet</label>
                    <select id="taskPriority" class="modal-select">
                        <option value="low">Niski</option>
                        <option value="medium" selected>Średni</option>
                        <option value="high">Wysoki</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn secondary" onclick="closeTaskModal()">Anuluj</button>
                <button class="modal-btn primary" onclick="saveTask()">Dodaj zadanie</button>
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

    <script src="../scripts/create_project.js"></script>
</body>

</html>