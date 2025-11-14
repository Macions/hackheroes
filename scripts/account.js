let currentEditField = '';

function openEditModal(field, currentValue) {
    currentEditField = field;
    const modal = document.getElementById('editModal');
    const input = document.getElementById('modalInput');
    const title = document.getElementById('modalTitle');
    
    const fieldNames = {
        'fullName': 'Imię i nazwisko',
        'nick': 'Nick',
        'email': 'E-mail',
        'phone': 'Telefon'
    };
    
    title.textContent = `Edytuj ${fieldNames[field]}`;
    input.value = currentValue;
    input.focus();
    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    currentEditField = '';
}

function saveEdit() {
    const newValue = document.getElementById('modalInput').value;
    
    if (newValue.trim()) {
        console.log(`Zapisano ${currentEditField}:`, newValue);
        alert('Zmiany zostały zapisane!');
        closeEditModal();
    } else {
        alert('Wartość nie może być pusta!');
    }
}

function openAvatarModal() {
    document.getElementById('avatarModal').style.display = 'flex';
}

function closeAvatarModal() {
    document.getElementById('avatarModal').style.display = 'none';
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function saveAvatar() {
    const fileInput = document.getElementById('avatarUpload');
    if (fileInput.files && fileInput.files[0]) {
        console.log('Zapisano nowe zdjęcie profilowe');
        document.getElementById('profileAvatar').src = document.getElementById('avatarPreview').src;
        alert('Zdjęcie profilowe zostało zmienione!');
        closeAvatarModal();
    } else {
        alert('Wybierz zdjęcie przed zapisaniem!');
    }
}

function openDangerModal(action) {
    const messages = {
        'delete': 'Czy na pewno chcesz usunąć konto? Tej operacji nie można cofnąć.',
        'logout': 'Czy na pewno chcesz się wylogować ze wszystkich urządzeń?',
        'permissions': 'Czy na pewno chcesz cofnąć wszystkie uprawnienia?'
    };
    
    if (confirm(messages[action])) {
        console.log(`Wykonano akcję: ${action}`);
        alert(`Akcja "${action}" została wykonana.`);
    }
}

document.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

const burgerMenu = document.getElementById('burger-menu');
const navMenu = document.querySelector('.nav-menu');

burgerMenu.addEventListener('click', () => {
    burgerMenu.classList.toggle('active');
    navMenu.classList.toggle('active');
});