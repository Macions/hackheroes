// ====== ANIMACJA LICZNIKÓW ======
let stats_section = document.getElementById("stats");
let amount_of_project = document.querySelector("#stats article#amount_of_projects h2 span");
let amount_of_future_creators = document.querySelector("#stats article#amount_of_future_creators h2 span");

let amount_of_project_int = amount_of_project.innerText;
let amount_of_future_creators_int = amount_of_future_creators.innerText;
let projectAnimated = false;

function tryAnimateProjects() {
	if (projectAnimated) return;

	const rect = stats_section.getBoundingClientRect();
	const visiblePx = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0);

	if (visiblePx >= 30) {
		projectAnimated = true;

		const projectCount = parseInt(amount_of_project_int, 10) || 0;
		for (let i = 0; i <= projectCount; i++) {
			setTimeout(() => amount_of_project.innerText = i, 20 * i);
		}

		const futureCount = parseInt(amount_of_future_creators_int, 10) || 0;
		for (let j = 0; j <= futureCount; j++) {
			setTimeout(() => amount_of_future_creators.innerText = j, 20 * j);
		}

		window.removeEventListener("scroll", tryAnimateProjects);
		window.removeEventListener("resize", tryAnimateProjects);
	}
}

window.addEventListener("scroll", tryAnimateProjects);
window.addEventListener("resize", tryAnimateProjects);
tryAnimateProjects();

// ====== CAROUSEL PROJEKTÓW ======
const project_section = document.getElementById("projects");
const move_circles = document.querySelectorAll("#move_circles .circle");
let autoSlideInterval = null;

function updateProjectCarousel(nextIndex) {
	const offset = nextIndex * 100;
	project_section.style.transform = `translateX(-${offset}%)`;
	project_section.style.transition = "transform 0.5s ease"; // Dodaj płynną animację
	move_circles.forEach(c => c.classList.remove("active"));
	move_circles[nextIndex].classList.add("active");
}

// Kółka
move_circles.forEach((circle, index) => {
	circle.addEventListener("click", () => {
		updateProjectCarousel(index);
		resetAutoSlide(); // Reset autoplay po kliknięciu
	});
});

function startAutoSlide() {
	autoSlideInterval = setInterval(() => {
		const active_circle = document.querySelector("#move_circles .circle.active");
		let currentIndex = Array.from(move_circles).indexOf(active_circle);
		let nextIndex = (currentIndex + 1) % move_circles.length;
		updateProjectCarousel(nextIndex);
	}, 6000);
}

function resetAutoSlide() {
	clearInterval(autoSlideInterval);
	startAutoSlide();
}

// Uruchom auto-slide po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
	startAutoSlide();
});

// ====== CAROUSEL OPINII ======
let opinion_arrow_left = document.querySelector(".arrow.left");
let opinion_arrow_right = document.querySelector(".arrow.right");
let opinions = document.getElementById("opinions");

let current_opinion_index = 0;
let max_opinion_index = opinions.children.length / 2 - 1;
let opinionsAutoSlideInterval = null;

function updateOpinionsCarousel() {
	const offset = current_opinion_index * 100;
	opinions.style.transform = `translateX(-${offset}%)`;
	opinions.style.transition = "transform 0.5s ease"; // Dodaj płynną animację
}

function navigateOpinions(direction) {
	if (direction === 'next' && current_opinion_index < max_opinion_index) {
		current_opinion_index++;
	} else if (direction === 'prev' && current_opinion_index > 0) {
		current_opinion_index--;
	}
	updateOpinionsCarousel();
	resetOpinionsAutoSlide();
}

// Strzałki
opinion_arrow_left.addEventListener("click", () => navigateOpinions('prev'));
opinion_arrow_right.addEventListener("click", () => navigateOpinions('next'));

function startOpinionsAutoSlide() {
	opinionsAutoSlideInterval = setInterval(() => {
		if (current_opinion_index < max_opinion_index) {
			current_opinion_index++;
		} else {
			current_opinion_index = 0;
		}
		updateOpinionsCarousel();
	}, 10000);
}

function resetOpinionsAutoSlide() {
	clearInterval(opinionsAutoSlideInterval);
	startOpinionsAutoSlide();
}

// Uruchom auto-slide opinii po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
	startOpinionsAutoSlide();
});

// ====== FIX FOR MENU SCROLL ISSUE ======
function fixMenuScroll() {
	const header = document.querySelector('header');
	const main = document.querySelector('main');
	
	if (header && main) {
		const headerHeight = header.offsetHeight;
		
		// Dla desktop - dodaj padding aby treść nie była pod sticky header
		if (!isMobileDevice()) {
			main.style.paddingTop = '0';
			// Upewnij się że pierwsza sekcja ma odpowiedni margines
			const firstSection = main.querySelector('section');
			if (firstSection) {
				firstSection.style.scrollMarginTop = headerHeight + 'px';
			}
		} else {
			main.style.paddingTop = '0';
		}
	}
}

// ====== SCROLL TO TOP FIX ======
function scrollToTop() {
	window.scrollTo(0, 0);
	
	if (history.scrollRestoration) {
		history.scrollRestoration = 'manual';
	}
}

// ====== IMPROVED MOBILE MENU ======
function initMobileMenu() {
	const header = document.querySelector('header');
	const nav = document.querySelector('header nav');
	
	if (isMobileDevice()) {
		const menuButton = document.createElement('button');
		menuButton.className = 'mobile-menu-button';
		menuButton.innerHTML = '☰';
		menuButton.setAttribute('aria-label', 'Otwórz menu');
		header.insertBefore(menuButton, nav);

		menuButton.addEventListener('click', (e) => {
			e.stopPropagation();
			const ul = nav.querySelector('ul');
			ul.classList.toggle('mobile-open');
			menuButton.setAttribute('aria-expanded', ul.classList.contains('mobile-open'));
			
			// Zmień ikonę przy otwarciu/zamknięciu
			menuButton.innerHTML = ul.classList.contains('mobile-open') ? '✕' : '☰';
		});

		// Zamknij menu po kliknięciu gdzie indziej
		document.addEventListener('click', (e) => {
			if (!nav.contains(e.target) && !menuButton.contains(e.target)) {
				const ul = nav.querySelector('ul');
				ul.classList.remove('mobile-open');
				menuButton.setAttribute('aria-expanded', 'false');
				menuButton.innerHTML = '☰';
			}
		});

		// Zamknij menu po kliknięciu w link
		nav.querySelectorAll('a').forEach(link => {
			link.addEventListener('click', () => {
				const ul = nav.querySelector('ul');
				ul.classList.remove('mobile-open');
				menuButton.setAttribute('aria-expanded', 'false');
				menuButton.innerHTML = '☰';
			});
		});
	}
}

// ====== RESPONSIVE / MOBILE ======
function isMobileDevice() {
	return window.innerWidth <= 768;
}

function isTouchDevice() {
	return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
}

function throttle(func, limit) {
	let inThrottle;
	return function() {
		const args = arguments;
		const context = this;
		if (!inThrottle) {
			func.apply(context, args);
			inThrottle = true;
			setTimeout(() => inThrottle = false, limit);
		}
	};
}

function handleResponsiveImages() {
	const images = document.querySelectorAll('img[data-srcset]');
	images.forEach(img => {
		const srcset = img.getAttribute('data-srcset');
		if (window.innerWidth < 768 && srcset) {
			const mobileSrc = srcset.split(',')[0].split(' ')[0];
			img.src = mobileSrc;
		}
	});
}

function adjustFontSizes() {
	const width = window.innerWidth;
	const html = document.documentElement;
	if (width < 576) html.style.fontSize = '14px';
	else if (width < 768) html.style.fontSize = '15px';
	else html.style.fontSize = '16px';
}

// ====== ORIENTATION CHANGE ======
function handleOrientationChange() {
	if (window.orientation === 90 || window.orientation === -90) {
		document.body.classList.add('landscape');
	} else {
		document.body.classList.remove('landscape');
	}
	// Napraw menu po zmianie orientacji
	setTimeout(fixMenuScroll, 100);
}

// ====== TOUCH SUPPORT ======
function enhanceTouchSupport() {
	if (isTouchDevice()) {
		document.body.classList.add('touch-device');
		const buttons = document.querySelectorAll('a, button, .circle');
		buttons.forEach(btn => {
			btn.style.minHeight = '44px';
			btn.style.minWidth = '44px';
		});

		const style = document.createElement('style');
		style.textContent = `
			.touch-device *:hover {
				transform: none !important;
			}
		`;
		document.head.appendChild(style);
	}
}

// ====== SMOOTH SCROLL FOR ANCHOR LINKS ======
function initSmoothScroll() {
	document.querySelectorAll('a[href^="#"]').forEach(anchor => {
		anchor.addEventListener('click', function (e) {
			e.preventDefault();
			const target = document.querySelector(this.getAttribute('href'));
			if (target) {
				const headerHeight = document.querySelector('header').offsetHeight;
				const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight;
				
				window.scrollTo({
					top: targetPosition,
					behavior: 'smooth'
				});
			}
		});
	});
}

// ====== LAZY LOADING ======
function initLazyLoading() {
	const imageObserver = new IntersectionObserver((entries, observer) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				const img = entry.target;
				if (img.dataset.src) {
					img.src = img.dataset.src;
				}
				if (img.dataset.srcset) {
					img.srcset = img.dataset.srcset;
				}
				img.classList.remove('lazy');
				observer.unobserve(img);
			}
		});
	});

	document.querySelectorAll('img[data-src]').forEach(img => {
		imageObserver.observe(img);
	});
}

// ====== PERFORMANCE OPTIMIZATIONS ======
function initPerformanceOptimizations() {
	// Throttle scroll events
	window.addEventListener('scroll', throttle(tryAnimateProjects, 100));
	
	// Optimize resize events
	let resizeTimeout;
	window.addEventListener('resize', () => {
		clearTimeout(resizeTimeout);
		resizeTimeout = setTimeout(() => {
			adjustFontSizes();
			fixMenuScroll();
		}, 250);
	});
}

// ====== INIT ======
function initResponsiveFeatures() {
	handleResponsiveImages();
	adjustFontSizes();
	initMobileMenu();
	handleOrientationChange();
	fixMenuScroll();
	scrollToTop();
	initSmoothScroll();
	initLazyLoading();
	initPerformanceOptimizations();

	window.addEventListener('resize', throttle(() => {
		handleResponsiveImages();
		adjustFontSizes();
		fixMenuScroll();
	}, 250));

	window.addEventListener('orientationchange', handleOrientationChange);
	
	// Napraw po załadowaniu obrazków
	window.addEventListener('load', () => {
		fixMenuScroll();
		adjustFontSizes();
	});
}

// ====== ERROR HANDLING ======
function initErrorHandling() {
	window.addEventListener('error', (e) => {
		console.error('JavaScript Error:', e.error);
	});
	
	window.addEventListener('unhandledrejection', (e) => {
		console.error('Unhandled Promise Rejection:', e.reason);
	});
}

// ====== MAIN INITIALIZATION ======
document.addEventListener('DOMContentLoaded', () => {
	initResponsiveFeatures();
	enhanceTouchSupport();
	initErrorHandling();
	
	// Initial carousel setup
	updateOpinionsCarousel();
	
	console.log('TeenCollab - Strona załadowana pomyślnie');
});

// ====== CLEANUP ON PAGE UNLOAD ======
window.addEventListener('beforeunload', () => {
	if (autoSlideInterval) clearInterval(autoSlideInterval);
	if (opinionsAutoSlideInterval) clearInterval(opinionsAutoSlideInterval);
});