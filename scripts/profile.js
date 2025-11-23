
document.addEventListener('DOMContentLoaded', function() {
    initProfilePage();
    initNavigation();
});

function initProfilePage() {

    const isOwner = Math.random() > 0.7; // Symulacja - w rzeczywistości sprawdzać z backendu
    if (isOwner) {
        document.getElementById('privateSection').style.display = 'block';
    }
    

    const followBtn = document.querySelector('.btn-secondary');
    let isFollowing = false;
    
    followBtn.addEventListener('click', function() {
        isFollowing = !isFollowing;
        this.innerHTML = isFollowing ? 
            '<span>✅ Obserwujesz</span>' : 
            '<span>❤️ Obserwuj</span>';
        this.style.background = isFollowing ? 'var(--primary)' : '';
        this.style.color = isFollowing ? 'white' : '';
        this.style.borderColor = isFollowing ? 'var(--primary)' : '';
    });
}

function initNavigation() {
    const burgerMenu = document.getElementById('burger-menu');
    const navMenu = document.querySelector('.nav-menu');


    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        navMenu.classList.toggle('active');
    });


    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            burgerMenu.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
}


function sendMessage() {
    alert('Funkcja wysyłania wiadomości będzie dostępna wkrótce!');
}


function editProfile() {
    alert('Przekierowanie do edycji profilu...');
}