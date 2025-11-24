document.addEventListener("DOMContentLoaded", function () {
	setupEventListeners();

	const followBtn = document.getElementById("followBtn");
	if (followBtn) {
		followBtn.addEventListener("click", toggleFollow);
	}
});

function setupEventListeners() {
	const joinBtn = document.getElementById("joinProjectBtn");
	const applyBtn = document.getElementById("applyBtn");

	if (joinBtn) {
		joinBtn.addEventListener("click", openJoinModal);
	}

	if (applyBtn) {
		applyBtn.addEventListener("click", openJoinModal);
	}

	const filterBtns = document.querySelectorAll(".filter-btn");
	filterBtns.forEach((btn) => {
		btn.addEventListener("click", function () {
			filterTasks(this.dataset.filter);

			filterBtns.forEach((b) => b.classList.remove("active"));
			this.classList.add("active");
		});
	});

	const likeBtn = document.querySelector(".like-btn");
	if (likeBtn) {
		likeBtn.addEventListener("click", toggleLike);
	}

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
	if (!USER_LOGGED_IN) {
		alert("Musisz się zalogować, aby polubić projekt!");
		return;
	}

	const likeBtn = document.querySelector(".like-btn");
	const likeCount = document.querySelector(".reaction-item .reaction-count");

	fetch("toggle_like.php", {
		method: "POST",
		headers: { "Content-Type": "application/json" },
		body: JSON.stringify({ project_id: projectData.id }),
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				likeBtn.textContent = data.liked ? "❤️ Lubisz" : "❤️ Polub";
				likeCount.textContent = data.likeCount;
			}
		})
		.catch((error) => console.error("Error:", error));
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
		navigator.clipboard.writeText(url).then(() => {
			alert("Link skopiowany do schowka!");
		});
	}
}

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
	if (!USER_LOGGED_IN) {
		alert("Musisz się zalogować, aby obserwować projekt!");
		return;
	}

	const followBtn = document.getElementById("followBtn");

	fetch("follow_project.php", {
		method: "POST",
		headers: { "Content-Type": "application/x-www-form-urlencoded" },
		body: `project_id=${projectData.id}`,
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
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
	const notification = document.createElement("div");
	notification.className = `notification notification-${type}`;
	notification.textContent = message;

	document.body.appendChild(notification);

	setTimeout(() => {
		notification.remove();
	}, 3000);
}
document.addEventListener("DOMContentLoaded", () => {
	const btn = document.getElementById("btnAddComment");
	if (!btn) return;

	btn.addEventListener("click", function (e) {
		e.preventDefault();

		const commentInput = document.getElementById("commentInput");
		if (!commentInput) return;

		const comment = commentInput.value.trim();
		if (!comment) return alert("Komentarz nie może być pusty!");

		fetch("add_comment.php", {
			method: "POST",
			body: new URLSearchParams({
				project_id: PROJECT_ID,
				comment: comment,
			}),
		})
			.then((res) => res.json())
			.then((data) => {
				if (data.success) {
					commentInput.value = "";
					addCommentToTop(comment); // funkcja do wstawienia komentarza na górę listy
				} else {
					alert("Nie udało się dodać komentarza."); // uniwersalny komunikat
				}
			})
			.catch((err) => {
				console.error("Fetch error:", err);
				alert("Błąd serwera. Sprawdź konsolę.");
			});
	});
});

function addCommentToTop(commentText) {
	const commentsList = document.querySelector(".comments-list");
	if (!commentsList) return;

	const newComment = document.createElement("div");
	newComment.className = "comment-item";
	newComment.innerHTML = `
		<div class="comment-avatar">
			<img src="${USER_AVATAR_URL}" alt="Ty">
		</div>
		<div class="comment-content">
			<h4>Ty</h4>
			<p>${commentText}</p>
			<span class="comment-date">Teraz</span>
		</div>
	`;
	commentsList.prepend(newComment);
}

document.addEventListener("DOMContentLoaded", function () {
	const requestForms = document.querySelectorAll(".action-form");

	requestForms.forEach((form) => {
		form.addEventListener("submit", function (e) {
			e.preventDefault();

			const button = this.querySelector("button");
			const originalText = button.innerHTML;
			const isAccept = button.classList.contains("btn-primary");
			const action = isAccept ? "akceptowania" : "odrzucania";

			button.classList.add("loading");
			button.disabled = true;
			button.innerHTML = '<span class="btn-icon">⏳</span> Przetwarzanie...';

			const allButtons =
				this.closest(".request-actions").querySelectorAll("button");
			allButtons.forEach((btn) => (btn.disabled = true));

			fetch(this.action, {
				method: "POST",
				body: new FormData(this),
			})
				.then((response) => {
					if (response.redirected) {
						window.location.href = response.url;
					} else {
						return response.text();
					}
				})
				.then((data) => {
					showNotification(`Pomyślnie ${action} zgłoszenie!`, "success");
				})
				.catch((error) => {
					console.error("Error:", error);
					showNotification(`Błąd podczas ${action} zgłoszenia`, "error");

					button.classList.remove("loading");
					button.disabled = false;
					button.innerHTML = originalText;
					allButtons.forEach((btn) => (btn.disabled = false));
				});
		});
	});
});

const notificationStyles = `
.custom-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    color: white;
    z-index: 10000;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideInRight 0.3s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 400px;
    backdrop-filter: blur(10px);
}

.notification-success {
    background: linear-gradient(135deg, #10b981, #059669);
}

.notification-error {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.notification-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
}

.notification-close {
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
    flex-shrink: 0;
}

.notification-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

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
`;

const styleSheet = document.createElement("style");
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);

function showNotification(message, type = "info") {
	const existingNotifications = document.querySelectorAll(
		".custom-notification"
	);
	existingNotifications.forEach((notification) => notification.remove());

	const notification = document.createElement("div");
	notification.className = `custom-notification notification-${type}`;
	notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;

	document.body.appendChild(notification);

	setTimeout(() => {
		if (notification.parentElement) {
			notification.remove();
		}
	}, 5000);
}

// Modal wysyłania wiadomości do zespołu
function openMessageModal() {
	if (!projectData.isOwner) return;

	const modal = document.createElement("div");
	modal.className = "modal message-modal";
	modal.style.display = "block";
	modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>📨 Wyślij wiadomość do zespołu</h3>
                <button class="modal-close" onclick="closeModal(this)">×</button>
            </div>
            <form method="POST" class="message-form">
                <div class="modal-body">
                    <div class="recipients-info">
                        <strong>Adresaci:</strong> Wszyscy członkowie projektu "${projectData.name
		}" (${document.querySelectorAll(".team-member-card").length
		} osób)
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tytuł wiadomości *</label>
                        <input type="text" name="message_title" class="form-input" placeholder="Wpisz tytuł wiadomości..." required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Treść wiadomości *</label>
                        <textarea name="message_content" class="form-textarea" placeholder="Wpisz treść wiadomości dla zespołu..." required></textarea>
                    </div>
                    
                    <div class="message-preview" style="display: none;">
                        <h4>Podgląd wiadomości:</h4>
                        <p id="previewContent"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn secondary" onclick="closeModal(this)">Anuluj</button>
                    <button type="submit" class="modal-btn primary" name="send_team_message">Wyślij wiadomość</button>
                </div>
            </form>
        </div>
    `;

	document.body.appendChild(modal);

	// Obsługa podglądu na żywo
	const titleInput = modal.querySelector('input[name="message_title"]');
	const contentInput = modal.querySelector('textarea[name="message_content"]');

	[titleInput, contentInput].forEach((input) => {
		input.addEventListener("input", updatePreview);
	});
}

function updatePreview() {
	const modal = document.querySelector(".message-modal");
	if (!modal) return;

	const title = modal.querySelector('input[name="message_title"]').value;
	const content = modal.querySelector('textarea[name="message_content"]').value;
	const preview = modal.querySelector(".message-preview");
	const previewContent = modal.querySelector("#previewContent");

	if (title || content) {
		preview.style.display = "block";
		previewContent.innerHTML = `<strong>${title || "(Brak tytułu)"
			}</strong>\n\n${content || "(Brak treści)"}`;
	} else {
		preview.style.display = "none";
	}
}

function closeModal(btn) {
	const modal = btn.closest(".modal");
	if (modal) {
		modal.remove();
	}
}
// =========================
// Funkcja otwierająca modal do wysyłki wiadomości do członka
// =========================
function openMessageModalSelectMember() {
	if (!projectData.isOwner) return;

	let optionsHTML = "";
	if (projectData.members && projectData.members.length > 0) {
		projectData.members.forEach((member) => {
			optionsHTML += `<option value="${member.id}">${member.nick}</option>`;
		});
	}

	const modal = document.createElement("div");
	modal.className = "modal message-modal";
	modal.style.display = "block";
	modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>📨 Wyślij wiadomość do członka projektu</h3>
                <button class="modal-close" onclick="closeModal(this)">×</button>
            </div>
            <form method="POST" class="message-form" onsubmit="handleMemberMessageSubmit(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Wybierz członka *</label>
                        <select name="recipient_id" class="form-input" required>
                            <option value="">-- Wybierz członka --</option>
                            ${optionsHTML}
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tytuł wiadomości *</label>
                        <input type="text" name="message_title" class="form-input" placeholder="Wpisz tytuł wiadomości..." required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Treść wiadomości *</label>
                        <textarea name="message_content" class="form-textarea" placeholder="Wpisz treść wiadomości..." required></textarea>
                    </div>
                    <div class="message-preview" style="display: none;">
                        <h4>Podgląd wiadomości:</h4>
                        <p id="previewContent"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn secondary" onclick="closeModal(this)">Anuluj</button>
                    <button type="submit" class="modal-btn primary">Wyślij wiadomość</button>
                </div>
            </form>
        </div>
    `;
	document.body.appendChild(modal);

	// Obsługa podglądu na żywo
	const titleInput = modal.querySelector('input[name="message_title"]');
	const contentInput = modal.querySelector('textarea[name="message_content"]');
	[titleInput, contentInput].forEach((input) =>
		input.addEventListener("input", updatePreview)
	);
}

// =========================
// Funkcja podglądu wiadomości
// =========================
function updatePreview() {
	const modal = document.querySelector(".message-modal");
	if (!modal) return;

	const title = modal.querySelector('input[name="message_title"]').value;
	const content = modal.querySelector('textarea[name="message_content"]').value;
	const preview = modal.querySelector(".message-preview");
	const previewContent = modal.querySelector("#previewContent");

	if (title || content) {
		preview.style.display = "block";
		previewContent.innerHTML = `<strong>${title || "(Brak tytułu)"
			}</strong><br>${content || "(Brak treści)"}`;
	} else {
		preview.style.display = "none";
	}
}

// =========================
// Funkcja zamykająca modal
// =========================
function closeModal(btn) {
	const modal = btn.closest(".modal");
	if (modal) {
		modal.remove();
	}
}

// =========================
// Obsługa wysyłki formularza do członka (ajax lub standard POST)
// =========================
// =========================
// Obsługa wysyłki formularza do członka
// =========================
function handleMemberMessageSubmit(event) {
	event.preventDefault();
	const form = event.target;
	const submitBtn = form.querySelector('button[type="submit"]');
	const originalText = submitBtn.textContent;

	const recipientId = form.recipient_id.value;
	const title = form.message_title.value.trim();
	const content = form.message_content.value.trim();

	if (!recipientId || !title || !content) {
		alert("Wszystkie pola są wymagane!");
		return;
	}

	// Zmiana tekstu przycisku na czas wysyłki
	submitBtn.textContent = "Wysyłanie...";
	submitBtn.disabled = true;

	fetch("send_message.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
			"X-Requested-With": "XMLHttpRequest"
		},
		body: JSON.stringify({
			recipient_id: recipientId,
			title: title,
			content: content,
			project_id: PROJECT_ID // Upewnij się że ta zmienna jest zdefiniowana
		}),
	})
		.then(response => {
			if (!response.ok) {
				throw new Error(`Błąd HTTP: ${response.status}`);
			}
			return response.json();
		})
		.then(data => {
			if (data.success) {
				alert("Wiadomość wysłana pomyślnie!");
				const modal = document.querySelector(".message-modal");
				if (modal) modal.remove();

				// Opcjonalnie: odśwież listę wiadomości lub pokaż potwierdzenie
				if (typeof refreshMessages === 'function') {
					refreshMessages();
				}
			} else {
				throw new Error(data.message || "Nieznany błąd podczas wysyłania wiadomości");
			}
		})
		.catch((err) => {
			console.error("Błąd wysyłania wiadomości:", err);
			alert("Wystąpił błąd podczas wysyłania wiadomości: " + err.message);
		})
		.finally(() => {
			// Przywróć przycisk do stanu początkowego
			submitBtn.textContent = originalText;
			submitBtn.disabled = false;
		});
}

function openRejectionPrompt(requestId, userName, projectId) {
	// Wyświetlamy prompt z prośbą o powód odrzucenia
	const reason = prompt(
		`Odrzucasz zgłoszenie użytkownika ${userName}.\nPodaj powód odrzucenia (min. 10 znaków):`
	);

	if (reason === null) {
		// Użytkownik kliknął Anuluj
		return;
	}

	if (reason.trim().length < 10) {
		alert("Powód odrzucenia musi mieć co najmniej 10 znaków!");
		return;
	}

	// Tworzymy formę i wysyłamy POST do project_decline.php
	const form = document.createElement("form");
	form.method = "POST";
	form.action = "project_decline.php";

	const requestInput = document.createElement("input");
	requestInput.type = "hidden";
	requestInput.name = "request_id";
	requestInput.value = requestId;
	form.appendChild(requestInput);

	const projectInput = document.createElement("input");
	projectInput.type = "hidden";
	projectInput.name = "project_id";
	projectInput.value = projectId;
	form.appendChild(projectInput);

	const reasonInput = document.createElement("input");
	reasonInput.type = "hidden";
	reasonInput.name = "rejection_reason";
	reasonInput.value = reason;
	form.appendChild(reasonInput);

	document.body.appendChild(form);
	form.submit();
}

const burgerMenu = document.getElementById("burger-menu");
const navMenu = document.querySelector(".nav-menu");

if (burgerMenu && navMenu) {
	burgerMenu.addEventListener("click", () => {
		burgerMenu.classList.toggle("active");
		navMenu.classList.toggle("active");
	});
}
