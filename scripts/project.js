document.addEventListener("DOMContentLoaded", function () {
	initializeProjectPage();
	setupEventListeners();
});

function initializeProjectPage() {
	// Sprawdź czy użytkownik jest właścicielem lub członkiem
	if (projectData.isOwner) {
		console.log("Jesteś właścicielem tego projektu");
	} else if (projectData.isMember) {
		console.log("Jesteś członkiem tego projektu");
	}
}

function setupEventListeners() {
	// Przycisk dołączania do projektu
	const joinBtn = document.getElementById("joinProjectBtn");
	const applyBtn = document.getElementById("applyBtn");

	if (joinBtn) {
		joinBtn.addEventListener("click", openJoinModal);
	}

	if (applyBtn) {
		applyBtn.addEventListener("click", openJoinModal);
	}

	// Filtrowanie zadań
	const filterBtns = document.querySelectorAll(".filter-btn");
	filterBtns.forEach((btn) => {
		btn.addEventListener("click", function () {
			filterTasks(this.dataset.filter);

			// Aktualizacja aktywnych przycisków
			filterBtns.forEach((b) => b.classList.remove("active"));
			this.classList.add("active");
		});
	});

	// Obsługa like'ów
	const likeBtn = document.querySelector(".like-btn");
	if (likeBtn) {
		likeBtn.addEventListener("click", toggleLike);
	}

	// Obsługa udostępniania
	const shareBtn = document.querySelector(".share-btn");
	if (shareBtn) {
		shareBtn.addEventListener("click", shareProject);
	}
}

function openJoinModal() {
	if (!projectData.allowApplications) {
		alert("Ten projekt nie przyjmuje obecnie nowych zgłoszeń.");
		return;
	}

	document.getElementById("joinModal").style.display = "flex";
}

function closeJoinModal() {
	document.getElementById("joinModal").style.display = "none";
}

function submitApplication() {
	const motivation = document.querySelector(".modal-textarea").value.trim();
	const role = document.querySelector(".modal-select").value;
	const availability = document.querySelectorAll(".modal-select")[1].value;

	if (!motivation) {
		alert("Proszę opisać swoje motywacje do dołączenia do projektu.");
		return;
	}

	if (!role) {
		alert("Proszę wybrać rolę.");
		return;
	}

	if (!availability) {
		alert("Proszę wybrać poziom zaangażowania.");
		return;
	}

	// Tutaj wyślij zgłoszenie do serwera
	const formData = new FormData();
	formData.append("project_id", projectData.id);
	formData.append("motivation", motivation);
	formData.append("role", role);
	formData.append("availability", availability);

	fetch("apply_to_project.php", {
		method: "POST",
		body: formData,
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				alert("Twoje zgłoszenie zostało wysłane!");
				closeJoinModal();
			} else {
				alert("Błąd: " + data.message);
			}
		})
		.catch((error) => {
			console.error("Error:", error);
			alert("Wystąpił błąd podczas wysyłania zgłoszenia.");
		});
}

function filterTasks(filter) {
	const tasks = document.querySelectorAll(".task-card");

	tasks.forEach((task) => {
		switch (filter) {
			case "all":
				task.style.display = "flex";
				break;
			case "open":
				task.style.display = task.dataset.status === "open" ? "flex" : "none";
				break;
			case "in-progress":
				task.style.display =
					task.dataset.status === "in-progress" ? "flex" : "none";
				break;
			case "done":
				task.style.display = task.dataset.status === "done" ? "flex" : "none";
				break;
		}
	});
}

function toggleLike() {
	const likeBtn = document.querySelector(".like-btn");
	const likeCount = document.querySelector(".reaction-item .reaction-count");

	fetch("toggle_like.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
		},
		body: JSON.stringify({
			project_id: projectData.id,
		}),
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				likeBtn.textContent = data.liked ? "❤️ Lubisz" : "❤️ Polub";
				likeCount.textContent = data.likeCount;
			}
		})
		.catch((error) => {
			console.error("Error:", error);
		});
}

function shareProject() {
	const url = window.location.href;
	const title = projectData.name;

	if (navigator.share) {
		navigator
			.share({
				title: title,
				text: "Zobacz ten projekt na TeenCollab:",
				url: url,
			})
			.catch((error) => console.log("Błąd udostępniania:", error));
	} else {
		// Fallback - kopiowanie do schowka
		navigator.clipboard.writeText(url).then(() => {
			alert("Link skopiowany do schowka!");
		});
	}
}

// Obsługa modalów
document.addEventListener("click", function (event) {
	if (event.target.classList.contains("modal")) {
		event.target.style.display = "none";
	}
});

document.addEventListener("keydown", function (event) {
	if (event.key === "Escape") {
		document.querySelectorAll(".modal").forEach((modal) => {
			modal.style.display = "none";
		});
	}
});

// Menu burger
const burgerMenu = document.getElementById("burger-menu");
const navMenu = document.querySelector(".nav-menu");

if (burgerMenu && navMenu) {
	burgerMenu.addEventListener("click", () => {
		burgerMenu.classList.toggle("active");
		navMenu.classList.toggle("active");
	});
}
