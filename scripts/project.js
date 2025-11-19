document.addEventListener("DOMContentLoaded", function () {
	initializeProjectPage();
	setupEventListeners();

	const followBtn = document.getElementById("followBtn");
	if (followBtn) {
		followBtn.addEventListener("click", toggleFollow);
	}
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

function toggleFollow() {
	const projectId = projectData.id;
	const followBtn = document.getElementById("followBtn");

	fetch("follow_project.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: `project_id=${projectId}`,
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				// Aktualizuj tekst i styl przycisku
				if (data.action === "follow") {
					followBtn.innerHTML =
						"<span title='Przestań obserwować'>Obserwujesz</span>";
					followBtn.classList.add("following");
					showNotification(data.message, "success");
				} else {
					followBtn.innerHTML = "<span>❤️ Obserwuj</span>";
					followBtn.classList.remove("following");
					showNotification(data.message, "info");
				}
			} else {
				showNotification(data.message, "error");
			}
		})
		.catch((error) => {
			console.error("Error:", error);
			showNotification("Wystąpił błąd!", "error");
		});
}

function showNotification(message, type) {
	// Tworzenie i wyświetlanie powiadomienia
	const notification = document.createElement("div");
	notification.className = `notification notification-${type}`;
	notification.textContent = message;

	document.body.appendChild(notification);

	setTimeout(() => {
		notification.remove();
	}, 3000);
}

document
	.getElementById("btnAddComment")
	.addEventListener("click", function (e) {
		e.preventDefault();
		const comment = document.getElementById("commentInput").value.trim();
		if (!comment) return alert("Komentarz nie może być pusty!");

		fetch("add_comment.php", {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body:
				"project_id=<?php echo $projectId; ?>&comment=" +
				encodeURIComponent(comment),
		})
			.then((response) => response.json())
			.then((data) => {
				if (data.success) {
					// Dodaj komentarz do listy bez przeładowania
					const list = document.querySelector(".comments-list");
					const newComment = document.createElement("div");
					newComment.classList.add("comment-item");
					newComment.innerHTML = `
                <div class="comment-avatar">
                    <img src="<?php echo $userAvatarUrl; ?>" alt="Twój avatar">
                </div>
                <div class="comment-content">
                    <h4>Ty</h4>
                    <p>${comment.replace(/\n/g, "<br>")}</p>
                    <span class="comment-date">Właśnie teraz</span>
                </div>
            `;
					list.prepend(newComment);
					document.getElementById("commentInput").value = "";
				} else {
					alert("Błąd przy dodawaniu komentarza!");
				}
			})
			.catch(() => alert("Coś poszło nie tak..."));
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
