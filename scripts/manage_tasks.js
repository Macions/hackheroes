// Zarządzanie zadaniami - Skrypty
document.addEventListener('DOMContentLoaded', function() {
    // Animacje dla kart zadań
    const taskCards = document.querySelectorAll('.task-card');
    
    taskCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Potwierdzenie usuwania zadania
    const deleteButtons = document.querySelectorAll('.btn-remove');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Czy na pewno chcesz usunąć to zadanie?')) {
                e.preventDefault();
            }
        });
    });

    // Walidacja formularza
    const addTaskForm = document.querySelector('.add-task-form');
    
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function(e) {
            const nameInput = document.getElementById('name');
            const descriptionInput = document.getElementById('description');
            
            // Walidacja nazwy zadania
            if (nameInput.value.trim().length < 3) {
                e.preventDefault();
                showNotification('Nazwa zadania musi mieć co najmniej 3 znaki', 'error');
                nameInput.focus();
                return;
            }
            
            // Walidacja opisu (opcjonalna)
            if (descriptionInput.value.trim().length > 500) {
                e.preventDefault();
                showNotification('Opis zadania nie może przekraczać 500 znaków', 'error');
                descriptionInput.focus();
                return;
            }
            
            // Pokazanie stanu ładowania
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.classList.add('loading');
        });
    }

    // Funkcja pokazująca powiadomienia
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // Automatyczne ukrywanie po 5 sekundach
        setTimeout(() => {
            notification.remove();
        }, 5000);
        
        // Ręczne ukrywanie
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.remove();
        });
    }

    // Efekty wizualne dla priorytetów
    function highlightCriticalTasks() {
        const criticalTasks = document.querySelectorAll('.priority-critical');
        criticalTasks.forEach(task => {
            task.parentElement.parentElement.style.animation = 'pulse 2s infinite';
        });
    }
    
    // Dodanie stylu dla animacji pulsowania
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    `;
    document.head.appendChild(style);
    
    highlightCriticalTasks();
});