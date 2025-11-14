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
                    <li><a href="index.html">Strona główna</a></li>
                    <li><a href="projekty.html">Projekty</a></li>
                    <li><a href="społeczność.html">Społeczność</a></li>
                    <li><a href="o-projekcie.html">O projekcie</a></li>
                    <li class="nav-cta"><a href="konto.html" class="cta-button active">Moje konto</a></li>
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
            <!-- Nagłówek profilu -->
            <section class="profile-header">
                <div class="profile-avatar-section">
                    <div class="avatar-container">
                        <img src="../photos/sample_person.png" alt="Zdjęcie profilowe" class="profile-avatar" id="profileAvatar">
                        <button class="change-avatar-btn" onclick="openAvatarModal()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M4 16L8 12L18 22H2L4 16Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M15 5L19 9" stroke="currentColor" stroke-width="2"/>
                                <path d="M18 2L22 6" stroke="currentColor" stroke-width="2"/>
                                <path d="M21 13V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V5C3 3.89543 3.89543 3 5 3H11" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Zmień zdjęcie
                        </button>
                    </div>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name">Jan Kowalski</h1>
                    <p class="profile-role">Kreator Przyszłości</p>
                    <p class="profile-join-date">Dołączył: 15 marca 2024</p>
                </div>
            </section>

            <div class="account-layout">
                <!-- Lewa kolumna -->
                <div class="account-sidebar">
                    <!-- Statystyki użytkownika -->
                    <section class="stats-section">
                        <h2>Statystyki</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number">12</span>
                                <span class="stat-label">Projektów</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">5</span>
                                <span class="stat-label">Własnych</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">8</span>
                                <span class="stat-label">Odznak</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">85%</span>
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
                                <span>15.03.2024</span>
                            </div>
                            <div class="summary-item">
                                <span>Liczba logowań:</span>
                                <span>147</span>
                            </div>
                            <div class="summary-item">
                                <span>Ostatnia zmiana:</span>
                                <span>2 dni temu</span>
                            </div>
                            <div class="summary-item">
                                <span>Status weryfikacji:</span>
                                <span class="verified">Zweryfikowany</span>
                            </div>
                        </div>
                    </section>

                    <!-- Aktywność -->
                    <section class="activity-section">
                        <h2>Ostatnia aktywność</h2>
                        <div class="activity-list">
                            <div class="activity-item">
                                <span>Ostatnie logowanie</span>
                                <span>Dzisiaj, 14:30</span>
                            </div>
                            <div class="activity-item">
                                <span>Edytowany projekt</span>
                                <span>"Zielone Miasto"</span>
                            </div>
                            <div class="activity-item">
                                <span>Dodane komentarze</span>
                                <span>3</span>
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
                                    <span>Jan Kowalski</span>
                                    <button class="edit-btn" onclick="openEditModal('fullName', 'Jan Kowalski')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2"/>
                                            <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>Nick</label>
                                <div class="data-value">
                                    <span>janek_dev</span>
                                    <button class="edit-btn" onclick="openEditModal('nick', 'janek_dev')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2"/>
                                            <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>E-mail</label>
                                <div class="data-value">
                                    <span>jan.kowalski@email.com</span>
                                    <button class="edit-btn" onclick="openEditModal('email', 'jan.kowalski@email.com')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2"/>
                                            <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="data-item">
                                <label>Telefon</label>
                                <div class="data-value">
                                    <span>+48 123 456 789</span>
                                    <button class="edit-btn" onclick="openEditModal('phone', '+48 123 456 789')">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2"/>
                                            <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2"/>
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
                            <div class="project-item">
                                <div class="project-info">
                                    <h3>Zielone Miasto</h3>
                                    <div class="project-meta">
                                        <span class="status active">Aktywny</span>
                                        <span class="role">Lider projektu</span>
                                    </div>
                                </div>
                                <button class="project-btn">Przejdź do projektu</button>
                            </div>
                            <div class="project-item">
                                <div class="project-info">
                                    <h3>EduApp dla szkół</h3>
                                    <div class="project-meta">
                                        <span class="status completed">Ukończony</span>
                                        <span class="role">Developer</span>
                                    </div>
                                </div>
                                <button class="project-btn">Przejdź do projektu</button>
                            </div>
                            <div class="project-item">
                                <div class="project-info">
                                    <h3>Portal społecznościowy</h3>
                                    <div class="project-meta">
                                        <span class="status active">Aktywny</span>
                                        <span class="role">Designer</span>
                                    </div>
                                </div>
                                <button class="project-btn">Przejdź do projektu</button>
                            </div>
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
                                        <input type="checkbox" checked>
                                        <span class="checkmark"></span>
                                        E-mail o nowych zadaniach
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" checked>
                                        <span class="checkmark"></span>
                                        Powiadomienia o komentarzach
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox">
                                        <span class="checkmark"></span>
                                        Powiadomienia systemowe
                                    </label>
                                </div>
                            </div>
                            <div class="setting-group">
                                <h3>Motyw</h3>
                                <div class="theme-buttons">
                                    <button class="theme-btn active">Jasny</button>
                                    <button class="theme-btn">Ciemny</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Preferencje projektów -->
                    <section class="preferences-section">
                        <div class="section-header">
                            <h2>Preferencje projektów</h2>
                        </div>
                        <div class="preferences-grid">
                            <div class="preference-item">
                                <label>Domyślna rola</label>
                                <select>
                                    <option>Uczestnik</option>
                                    <option>Developer</option>
                                    <option>Designer</option>
                                    <option>Lider</option>
                                </select>
                            </div>
                            <div class="preference-item">
                                <label>Widok projektów</label>
                                <select>
                                    <option>Siatka</option>
                                    <option>Lista</option>
                                </select>
                            </div>
                            <div class="preference-item">
                                <label>Poziom zaangażowania</label>
                                <select>
                                    <option>Aktywnie uczestniczę</option>
                                    <option>Mogę być przypisany do zadań</option>
                                    <option>Obserwuję</option>
                                </select>
                            </div>
                            <div class="preference-item full-width">
                                <label class="checkbox-label">
                                    <input type="checkbox" checked>
                                    <span class="checkmark"></span>
                                    Automatyczne zapisywanie szkiców
                                </label>
                            </div>
                        </div>
                    </section>

                    <!-- Odznaki -->
                    <section class="badges-section">
                        <div class="section-header">
                            <h2>Odznaki i osiągnięcia</h2>
                        </div>
                        <div class="badges-grid">
                            <div class="badge-item">
                                <div class="badge-icon">🏆</div>
                                <div class="badge-info">
                                    <h4>Aktywny Twórca</h4>
                                    <p>Za udział w 10+ projektach</p>
                                </div>
                            </div>
                            <div class="badge-item">
                                <div class="badge-icon">🚀</div>
                                <div class="badge-info">
                                    <h4>Pierwszy projekt</h4>
                                    <p>Za ukończenie pierwszego projektu</p>
                                </div>
                            </div>
                            <div class="badge-item">
                                <div class="badge-icon">💎</div>
                                <div class="badge-info">
                                    <h4>Top Contributor</h4>
                                    <p>Za wyjątkowe zaangażowanie</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Strefa niebezpieczna -->
                    <section class="danger-section">
                        <div class="section-header">
                            <h2>Strefa niebezpieczna</h2>
                        </div>
                        <div class="danger-actions">
                            <button class="danger-btn" onclick="openDangerModal('delete')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 6H5H21" stroke="currentColor" stroke-width="2"/>
                                    <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Usuń konto
                            </button>
                            <button class="danger-btn" onclick="openDangerModal('logout')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2"/>
                                    <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="2"/>
                                    <path d="M21 12H9" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Wyloguj ze wszystkich urządzeń
                            </button>
                            <button class="danger-btn" onclick="openDangerModal('permissions')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M2 12C2 12 5 6 12 6C19 6 22 12 22 12C22 12 19 18 12 18C5 18 2 12 2 12Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Cofnij wszystkie uprawnienia
                            </button>
                        </div>
                    </section>
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
                    <img src="../photos/sample_person.png" alt="Podgląd" id="avatarPreview">
                </div>
                <input type="file" id="avatarUpload" accept="image/*" onchange="previewAvatar(this)">
                <label for="avatarUpload" class="upload-btn">Wybierz zdjęcie</label>
            </div>
            <div class="modal-footer">
                <button class="modal-btn secondary" onclick="closeAvatarModal()">Anuluj</button>
                <button class="modal-btn primary" onclick="saveAvatar()">Zapisz zdjęcie</button>
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
                        <p>Platforma dla młodych zmieniaczy świata</p>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p>©2025 TeenCollab | Made with ❤️ by M.Cz.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="../scripts/account.js"></script>
</body>

</html>