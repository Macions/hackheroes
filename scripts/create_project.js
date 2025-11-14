document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupEventListeners();
    setupLivePreview();
});

function initializeForm() {
    updateCharCounter('projectName', 'nameCounter');
    updateCharCounter('shortDescription', 'descCounter');
}

function setupEventListeners() {
    document.getElementById('projectName').addEventListener('input', function() {
        updateCharCounter('projectName', 'nameCounter');
        updatePreview();
    });

    document.getElementById('shortDescription').addEventListener('input', function() {
        updateCharCounter('shortDescription', 'descCounter');
        updatePreview();
    });

    document.getElementById('addGoalBtn').addEventListener('click', addGoalField);
    document.getElementById('addTaskBtn').addEventListener('click', openTaskModal);
    document.getElementById('addCustomSkill').addEventListener('click', addCustomSkill);

    document.getElementById('uploadArea').addEventListener('click', function() {
        document.getElementById('thumbnailUpload').click();
    });

    document.getElementById('thumbnailUpload').addEventListener('change', handleThumbnailUpload);
    document.getElementById('removeThumbnail').addEventListener('click', removeThumbnail);

    document.getElementById('advancedToggle').addEventListener('click', toggleAdvancedSettings);

    document.querySelectorAll('input[name="visibility"]').forEach(radio => {
        radio.addEventListener('change', updatePreview);
    });

    document.getElementById('createProjectForm').addEventListener('submit', handleFormSubmit);
    document.getElementById('cancelBtn').addEventListener('click', handleCancel);
    document.getElementById('saveDraftBtn').addEventListener('click', handleSaveDraft);
}

function setupLivePreview() {
    document.querySelectorAll('input[name="categories"]').forEach(checkbox => {
        checkbox.addEventListener('change', updatePreview);
    });
}

function updateCharCounter(inputId, counterId) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    counter.textContent = input.value.length;
}

function addGoalField() {
    const container = document.getElementById('goalsContainer');
    const goalCount = container.children.length;
    
    const goalDiv = document.createElement('div');
    goalDiv.className = 'goal-item';
    
    goalDiv.innerHTML = `
        <input type="text" name="goals[]" placeholder="Dodaj kolejny cel projektu">
        <button type="button" class="remove-goal">×</button>
    `;
    
    container.appendChild(goalDiv);
    
    goalDiv.querySelector('input').addEventListener('input', updatePreview);
    goalDiv.querySelector('.remove-goal').addEventListener('click', function() {
        goalDiv.remove();
        updatePreview();
    });
    
    goalDiv.querySelector('.remove-goal').style.display = 'block';
}

function openTaskModal() {
    document.getElementById('taskModal').style.display = 'flex';
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
    document.getElementById('taskName').value = '';
    document.getElementById('taskDescription').value = '';
    document.getElementById('taskPriority').value = 'medium';
}

function saveTask() {
    const name = document.getElementById('taskName').value.trim();
    const description = document.getElementById('taskDescription').value.trim();
    const priority = document.getElementById('taskPriority').value;
    
    if (!name) {
        alert('Proszę podać nazwę zadania');
        return;
    }
    
    const container = document.getElementById('tasksContainer');
    const taskDiv = document.createElement('div');
    taskDiv.className = 'task-item';
    
    const priorityClass = `priority-${priority}`;
    const priorityText = {
        'low': 'Niski',
        'medium': 'Średni',
        'high': 'Wysoki'
    }[priority];
    
    taskDiv.innerHTML = `
        <div class="task-header">
            <span class="task-name">${name}</span>
            <span class="task-priority ${priorityClass}">${priorityText}</span>
        </div>
        ${description ? `<div class="task-description">${description}</div>` : ''}
        <button type="button" class="remove-goal" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(taskDiv);
    closeTaskModal();
}

function addCustomSkill() {
    const input = document.getElementById('customSkill');
    const skill = input.value.trim();
    
    if (!skill) {
        alert('Proszę podać nazwę umiejętności');
        return;
    }
    
    const skillsGrid = document.querySelector('.skills-grid');
    const checkbox = document.createElement('label');
    checkbox.className = 'skill-checkbox';
    checkbox.innerHTML = `
        <input type="checkbox" name="skills" value="custom-${skill.toLowerCase()}">
        <span class="checkmark"></span>
        ${skill}
    `;
    
    skillsGrid.appendChild(checkbox);
    input.value = '';
}

function handleThumbnailUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('Proszę wybrać plik obrazu');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        alert('Plik jest zbyt duży. Maksymalny rozmiar to 2MB.');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImage').src = e.target.result;
        document.getElementById('thumbnailPreview').style.display = 'block';
        document.getElementById('uploadArea').style.display = 'none';
        
        const previewThumbnail = document.getElementById('previewThumbnail');
        previewThumbnail.innerHTML = `<img src="${e.target.result}" alt="Podgląd miniatury">`;
    };
    reader.readAsDataURL(file);
}

function removeThumbnail() {
    document.getElementById('thumbnailUpload').value = '';
    document.getElementById('thumbnailPreview').style.display = 'none';
    document.getElementById('uploadArea').style.display = 'block';
    
    const previewThumbnail = document.getElementById('previewThumbnail');
    previewThumbnail.innerHTML = '<div class="no-thumbnail">Brak miniatury</div>';
}

function toggleAdvancedSettings() {
    const settings = document.getElementById('advancedSettings');
    const toggleIcon = document.querySelector('.toggle-icon');
    
    if (settings.style.display === 'none') {
        settings.style.display = 'block';
        toggleIcon.style.transform = 'rotate(180deg)';
    } else {
        settings.style.display = 'none';
        toggleIcon.style.transform = 'rotate(0deg)';
    }
}

function updatePreview() {
    const title = document.getElementById('projectName').value || 'Nazwa projektu';
    const description = document.getElementById('shortDescription').value || 'Krótki opis projektu pojawi się tutaj...';
    
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewDescription').textContent = description;
    
    updateCategoriesPreview();
    updateGoalsPreview();
    updateVisibilityPreview();
}

function updateCategoriesPreview() {
    const container = document.getElementById('previewCategories');
    const selectedCategories = Array.from(document.querySelectorAll('input[name="categories"]:checked'))
        .map(cb => cb.nextElementSibling.nextElementSibling.textContent.trim());
    
    if (selectedCategories.length === 0) {
        container.innerHTML = '<span class="no-categories">Brak kategorii</span>';
        return;
    }
    
    container.innerHTML = selectedCategories.map(cat => 
        `<span class="category-tag">${cat}</span>`
    ).join('');
}

function updateGoalsPreview() {
    const container = document.querySelector('.goals-list');
    const goals = Array.from(document.querySelectorAll('input[name="goals[]"]'))
        .map(input => input.value.trim())
        .filter(goal => goal.length > 0);
    
    if (goals.length === 0) {
        container.innerHTML = '<span class="no-goals">Brak dodanych celów</span>';
        return;
    }
    
    container.innerHTML = goals.map(goal => 
        `<div class="goal-preview">${goal}</div>`
    ).join('');
}

function updateVisibilityPreview() {
    const selected = document.querySelector('input[name="visibility"]:checked');
    document.getElementById('previewVisibility').textContent = 
        selected.value === 'public' ? 'Publiczny' : 'Prywatny';
}

function handleFormSubmit(event) {
    event.preventDefault();
    
    if (!validateForm()) {
        return;
    }
    
    const formData = new FormData(event.target);
    const projectData = Object.fromEntries(formData);
    
    console.log('Tworzenie projektu:', projectData);
    alert('Projekt został utworzony pomyślnie!');
}

function handleCancel() {
    if (confirm('Czy na pewno chcesz anulować tworzenie projektu? Wszystkie niezapisane zmiany zostaną utracone.')) {
        window.location.href = 'projekty.html';
    }
}

function handleSaveDraft() {
    const formData = new FormData(document.getElementById('createProjectForm'));
    const projectData = Object.fromEntries(formData);
    
    console.log('Zapisywanie szkicu:', projectData);
    alert('Szkic projektu został zapisany!');
}

function validateForm() {
    const name = document.getElementById('projectName').value.trim();
    const description = document.getElementById('shortDescription').value.trim();
    
    if (!name) {
        alert('Proszę podać nazwę projektu');
        document.getElementById('projectName').focus();
        return false;
    }
    
    if (!description) {
        alert('Proszę podać krótki opis projektu');
        document.getElementById('shortDescription').focus();
        return false;
    }
    
    return true;
}

document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
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