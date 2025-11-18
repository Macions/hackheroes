document.addEventListener("DOMContentLoaded", function () {
    initializeForm();
    setupEventListeners();
});

function initializeForm() {
    updateCharCounter("projectName", "nameCounter");
    updateCharCounter("shortDescription", "descCounter");
}

function setupEventListeners() {
    // Liczniki znaków
    document.getElementById("projectName").addEventListener("input", function () {
        updateCharCounter("projectName", "nameCounter");
    });

    document.getElementById("shortDescription").addEventListener("input", function () {
        updateCharCounter("shortDescription", "descCounter");
    });

    // Podgląd nowej miniatury
    document.getElementById("thumbnailUpload").addEventListener("change", function (e) {
        previewThumbnail(e);
    });

    // Walidacja formularza
    document.getElementById("editProjectForm").addEventListener("submit", function (e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    // Auto-save draft (opcjonalne)
    setupAutoSave();
}

function updateCharCounter(inputId, counterId) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    counter.textContent = input.value.length;
}

function previewThumbnail(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
        alert("Proszę wybrać plik obrazu");
        event.target.value = "";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert("Plik jest zbyt duży. Maksymalny rozmiar to 2MB.");
        event.target.value = "";
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const currentThumbnail = document.querySelector(".current-thumbnail");
        currentThumbnail.innerHTML = `<img src="${e.target.result}" alt="Nowa miniatura">`;
    };
    reader.readAsDataURL(file);
}

function validateForm() {
    const name = document.getElementById("projectName").value.trim();
    const description = document.getElementById("shortDescription").value.trim();

    if (!name) {
        alert("Proszę podać nazwę projektu");
        document.getElementById("projectName").focus();
        return false;
    }

    if (!description) {
        alert("Proszę podać krótki opis projektu");
        document.getElementById("shortDescription").focus();
        return false;
    }

    if (name.length < 3) {
        alert("Nazwa projektu musi mieć co najmniej 3 znaki");
        document.getElementById("projectName").focus();
        return false;
    }

    if (description.length < 10) {
        alert("Opis projektu musi mieć co najmniej 10 znaków");
        document.getElementById("shortDescription").focus();
        return false;
    }

    return true;
}

function setupAutoSave() {
    let saveTimeout;
    const form = document.getElementById("editProjectForm");
    
    form.addEventListener("input", function () {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(saveDraft, 2000);
    });
}

function saveDraft() {
    const formData = new FormData(document.getElementById("editProjectForm"));
    const projectData = Object.fromEntries(formData.entries());
    
    // Zapisz do localStorage
    localStorage.setItem('projectEditDraft_' + projectData.id, JSON.stringify(projectData));
    
    // Pokaz krótkie powiadomienie
    showNotification('Szkic zapisany', 'success');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// CSS dla powiadomień (można dodać do CSS)
const notificationStyles = `
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
`;

// Dodaj style do dokumentu
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

// Ładowanie zapisanego szkicu
function loadDraft() {
    const projectId = new URLSearchParams(window.location.search).get('id');
    const draft = localStorage.getItem('projectEditDraft_' + projectId);
    
    if (draft) {
        if (confirm('Znaleziono zapisany szkic. Czy chcesz go wczytać?')) {
            const data = JSON.parse(draft);
            // Wypełnij formularz danymi...
        }
    }
}

// Wywołaj przy ładowaniu strony
loadDraft();