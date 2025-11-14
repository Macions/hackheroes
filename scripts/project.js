// project.js
document.addEventListener('DOMContentLoaded', function() {
    initProjectPage();
    initTaskFilters();
    initJoinModal();
    initComments();
    initReactions();
});

function initProjectPage() {
    // Sprawdź czy użytkownik jest właścicielem projektu
    const isOwner = Math.random() > 0.7; // Symulacja - w rzeczywistości sprawdzać z backendu
    if (isOwner) {
        document.getElementById('ownerTools').style.display = 'block';
    }
}

function initTaskFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const taskCards = document.querySelectorAll('.task-card');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Aktualizuj aktywne przyciski
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Filtruj zadania
            taskCards.forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

function initJoinModal() {
    const joinBtn = document.getElementById('joinProjectBtn');
    const modal = document.getElementById('joinModal');
    
    joinBtn.addEventListener('click', function() {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });
}

function closeJoinModal() {
    const modal = document.getElementById('joinModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function submitApplication() {
    const textarea = document.querySelector('.modal-textarea');
    const motivation = textarea.value.trim();
    
    if (!motivation) {
        alert('Proszę opisać swoje motywacje do dołączenia do projektu');
        return;
    }
    
    // Symulacja wysłania zgłoszenia
    showNotification('Twoje zgłoszenie zostało wysłane! Otrzymasz odpowiedź wkrótce.', 'success');
    closeJoinModal();
    
    // Reset formularza
    textarea.value = '';
    document.querySelector('.modal-select').selectedIndex = 0;
}

function initComments() {
    const commentBtn = document.querySelector('.btn-comment');
    const commentInput = document.querySelector('.comment-input');
    
    commentBtn.addEventListener('click', function() {
        const comment = commentInput.value.trim();
        
        if (!comment) {
            alert('Proszę wpisać komentarz');
            return;
        }
        
        // Symulacja dodania komentarza
        addNewComment(comment);
        commentInput.value = '';
        
        showNotification('Komentarz został dodany!', 'success');
    });
}

function addNewComment(text) {
    const commentsList = document.querySelector('.comments-list');
    const newComment = document.createElement('div');
    newComment.className = 'comment';
    
    newComment.innerHTML = `
        <div class="comment-avatar">
            <img src="../photos/sample_person.png" alt="Twój avatar">
        </div>
        <div class="comment-content">
            <div class="comment-header">
                <span class="comment-author">Ty</span>
                <span class="comment-date">Teraz</span>
            </div>
            <p class="comment-text">${text}</p>
            <div class="comment-actions">
                <button class="comment-like">❤️ 0</button>
                <button class="comment-reply">Odpowiedz</button>
            </div>
        </div>
    `;
    
    commentsList.prepend(newComment);
}

function initReactions() {
    const likeBtn = document.querySelector('.like-btn');
    let liked = false;
    
    likeBtn.addEventListener('click', function() {
        liked = !liked;
        this.textContent = liked ? '❤️ Polubione' : '❤️ Polub';
        this.style.background = liked ? 'var(--primary)' : 'white';
        this.style.color = liked ? 'white' : 'inherit';
        
        showNotification(liked ? 'Dodałeś polubienie!' : 'Usunąłeś polubienie', 'info');
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? 'var(--primary)' : type === 'error' ? 'var(--danger)' : 'var(--secondary)'};
        color: white;
        border-radius: 0.75rem;
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Obsługa menu mobilnego
const burgerMenu = document.getElementById('burger-menu');
const navMenu = document.querySelector('.nav-menu');

if (burgerMenu) {
    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
}

// Zamknij menu po kliknięciu w link
document.querySelectorAll('.nav-menu a').forEach(link => {
    link.addEventListener('click', () => {
        burgerMenu.classList.remove('active');
        navMenu.classList.remove('active');
    });
});