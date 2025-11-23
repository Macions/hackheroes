document.addEventListener('DOMContentLoaded', function() {
    initializeManageTeam();
});

function initializeManageTeam() {

    initializeConfirmations();
    

    initializeForms();
    

    initializeAnimations();
}

function initializeConfirmations() {

    const removeButtons = document.querySelectorAll('.btn-remove');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Czy na pewno chcesz usunąć tego członka z zespołu?')) {
                e.preventDefault();
            }
        });
    });
}

function initializeForms() {

    const addMemberForm = document.querySelector('.add-member-form');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', function(e) {
            const userId = document.getElementById('add_user_id').value;
            const role = document.getElementById('role').value;
            
            if (!userId || !role) {
                e.preventDefault();
                showNotification('Proszę wypełnić wszystkie pola', 'error');
                return;
            }
            

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Dodawanie...';
            submitBtn.disabled = true;
            

            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    }
    

    const roleSelects = document.querySelectorAll('.role-select');
    roleSelects.forEach(select => {
        select.addEventListener('change', function() {
            const memberCard = this.closest('.member-card');
            const memberName = memberCard.querySelector('.member-name').textContent;
            const newRole = this.value;
            

            memberCard.style.transform = 'scale(1.02)';
            setTimeout(() => {
                memberCard.style.transform = '';
            }, 300);
            
            showNotification(`Zmieniono rolę ${memberName} na ${newRole}`, 'success');
        });
    });
}

function initializeAnimations() {

    const memberCards = document.querySelectorAll('.member-card');
    memberCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function showNotification(message, type = 'info') {

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-message">${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        z-index: 10000;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1rem;
        max-width: 400px;
    `;
    

    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8',
        warning: '#ffc107'
    };
    
    notification.style.background = colors[type] || colors.info;
    

    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.3s ease;
    `;
    
    closeBtn.addEventListener('mouseenter', function() {
        this.style.background = 'rgba(255,255,255,0.2)';
    });
    
    closeBtn.addEventListener('mouseleave', function() {
        this.style.background = 'none';
    });
    
    document.body.appendChild(notification);
    

    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}


const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .member-card {
        transition: all 0.3s ease;
    }
`;
document.head.appendChild(style);


window.addEventListener('error', function(e) {
    console.error('Błąd:', e.error);
    showNotification('Wystąpił nieoczekiwany błąd', 'error');
});