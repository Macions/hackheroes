let currentEditField = "";
let originalValue = "";
let nickCheckTimeout = null;
let currentEditElement = null;

document.addEventListener("DOMContentLoaded", function () {
	initializeInlineEditing();
	initializePasswordChange();
	initializeNotifications();
	initializePreferences();
	verificationCheck();
	initializeAvatarModal();
});

function initializeAvatarModal() {
	console.log("Initializing avatar modal...");

	// 1. Przycisk zapisywania avatara w modal
	const saveAvatarBtn = document.querySelector(
		"#avatarModal .modal-btn.primary"
	);
	if (saveAvatarBtn) {
		saveAvatarBtn.addEventListener("click", saveAvatar);
		console.log("✅ Avatar save button found:", saveAvatarBtn);
	} else {
		console.error("❌ Avatar save button not found!");

		// Debug: sprawdź wszystkie przyciski w modal
		const allButtons = document.querySelectorAll("#avatarModal button");
		console.log("All buttons in modal:", allButtons);

		// Spróbuj znaleźć przycisk po tekście
		const buttons = document.querySelectorAll("#avatarModal button");
		buttons.forEach((btn) => {
			if (
				btn.textContent.includes("Zapisz") ||
				btn.textContent.includes("Save")
			) {
				btn.addEventListener("click", saveAvatar);
				console.log("✅ Found save button by text:", btn);
			}
		});
	}

	// 2. Input do wybierania pliku
	const avatarUpload = document.getElementById("avatarUpload");
	if (avatarUpload) {
		avatarUpload.addEventListener("change", function () {
			previewAvatar(this);
		});
		console.log("✅ Avatar upload input found");
	} else {
		console.error("❌ Avatar upload input not found!");
	}

	// 3. Przyciski zamykania modala
	const closeAvatarBtns = document.querySelectorAll(
		"#avatarModal .modal-btn.secondary, #avatarModal .modal-close"
	);
	if (closeAvatarBtns.length > 0) {
		closeAvatarBtns.forEach((btn) => {
			btn.addEventListener("click", closeAvatarModal);
		});
		console.log("✅ Close buttons found:", closeAvatarBtns.length);
	} else {
		console.error("❌ Close buttons not found!");

		// Dodaj ręcznie do przycisku Anuluj
		const cancelBtn = document.querySelector(
			"#avatarModal .modal-btn.secondary"
		);
		if (cancelBtn) {
			cancelBtn.addEventListener("click", closeAvatarModal);
			console.log("✅ Added close event to cancel button");
		}
	}

	// 4. Przycisk zmiany avatara na stronie profilu (poza modem)
	const changeAvatarBtn = document.querySelector(".change-avatar-btn");
	if (changeAvatarBtn) {
		changeAvatarBtn.addEventListener("click", openAvatarModal);
		console.log("✅ Change avatar button found");
	} else {
		console.error("❌ Change avatar button not found!");

		// Sprawdź czy może jest inny przycisk
		const profileAvatar = document.getElementById("profileAvatar");
		if (profileAvatar) {
			profileAvatar.addEventListener("click", openAvatarModal);
			console.log("✅ Added click event to profile avatar");
		}
	}

	// 5. Zamknięcie modala przy kliknięciu w tło
	const avatarModal = document.getElementById("avatarModal");
	if (avatarModal) {
		avatarModal.addEventListener("click", function (e) {
			if (e.target === this) {
				closeAvatarModal();
			}
		});
		console.log("✅ Background click handler added");
	}

	// 6. Zamknięcie modala klawiszem Escape
	document.addEventListener("keydown", function (e) {
		if (
			e.key === "Escape" &&
			avatarModal &&
			avatarModal.style.display === "flex"
		) {
			closeAvatarModal();
		}
	});

	console.log("🎯 Avatar modal initialization complete");
}

function initializeInlineEditing() {
	const editButtons = document.querySelectorAll(".edit-btn");

	editButtons.forEach((button) => {
		button.addEventListener("click", function (e) {
			e.stopPropagation();
			const dataItem = this.closest(".data-item");
			const label = dataItem.querySelector("label").textContent;
			const valueSpan = dataItem.querySelector(".data-value span");

			const fieldMap = {
				"Imię i nazwisko": "fullName",
				Nick: "nick",
				"E-mail": "email",
				Telefon: "phone",
			};

			const field = fieldMap[label];
			if (field) {
				startInlineEdit(field, valueSpan, this);
			}
		});
	});

	document.addEventListener("click", function (e) {
		if (
			!e.target.closest(".data-value") &&
			!e.target.classList.contains("edit-btn")
		) {
			finishEditing();
		}
	});
}

function startInlineEdit(field, valueElement, editButton) {
	if (isSaving) {
		console.log("Trwa zapisywanie, pomijam nową edycję");
		return;
	}

	finishEditing();

	const currentValue = valueElement.textContent;
	originalValue = currentValue;

	const input = document.createElement("input");
	input.type = "text";
	input.value = currentValue;
	input.className = "inline-edit-input";

	const feedback = document.createElement("div");
	feedback.className = "edit-feedback";

	valueElement.parentNode.replaceChild(input, valueElement);
	editButton.style.display = "none";

	input.parentNode.appendChild(feedback);

	input.focus();
	input.select();

	currentEditField = field;
	currentEditElement = input.parentNode;

	if (field === "nick") {
		input.addEventListener("input", function () {
			if (isSaving) return; // Blokuj podczas zapisywania
			clearTimeout(nickCheckTimeout);
			nickCheckTimeout = setTimeout(() => {
				checkNickAvailability(this.value);
			}, 500);
		});
	}

	input.addEventListener("keypress", function (e) {
		if (isSaving) return; // Blokuj podczas zapisywania

		if (e.key === "Enter") {
			saveInlineEdit();
		} else if (e.key === "Escape") {
			cancelInlineEdit();
		}
	});

	input.addEventListener("blur", function () {
		if (isSaving) return; // Blokuj podczas zapisywania

		setTimeout(() => {
			if (!document.activeElement.classList.contains("inline-edit-input")) {
				saveInlineEdit();
			}
		}, 100);
	});
}

function finishEditing() {
	if (currentEditElement) {
		saveInlineEdit();
	}
}
let isSaving = false; // DODAJ NA GÓRZE

function startInlineEdit(field, valueElement, editButton) {
	if (isSaving) {
		console.log("Trwa zapisywanie, pomijam nową edycję");
		return;
	}

	finishEditing();

	const currentValue = valueElement.textContent;
	originalValue = currentValue;

	const input = document.createElement("input");
	input.type = "text";
	input.value = currentValue;
	input.className = "inline-edit-input";

	const feedback = document.createElement("div");
	feedback.className = "edit-feedback";

	valueElement.parentNode.replaceChild(input, valueElement);
	editButton.style.display = "none";

	input.parentNode.appendChild(feedback);

	input.focus();
	input.select();

	currentEditField = field;
	currentEditElement = input.parentNode;

	if (field === "nick") {
		input.addEventListener("input", function () {
			if (isSaving) return; // Blokuj podczas zapisywania
			clearTimeout(nickCheckTimeout);
			nickCheckTimeout = setTimeout(() => {
				checkNickAvailability(this.value);
			}, 500);
		});
	}

	input.addEventListener("keypress", function (e) {
		if (isSaving) return; // Blokuj podczas zapisywania

		if (e.key === "Enter") {
			saveInlineEdit();
		} else if (e.key === "Escape") {
			cancelInlineEdit();
		}
	});

	input.addEventListener("blur", function () {
		if (isSaving) return; // Blokuj podczas zapisywania

		setTimeout(() => {
			if (!document.activeElement.classList.contains("inline-edit-input")) {
				saveInlineEdit();
			}
		}, 100);
	});
}

function saveInlineEdit() {
	if (!currentEditElement || isSaving) return;

	const input = currentEditElement.querySelector(".inline-edit-input");
	if (!input) return;

	const newValue = input.value.trim();
	const field = currentEditField;
	const feedback = currentEditElement.querySelector(".edit-feedback");

	if (newValue === originalValue) {
		cancelInlineEdit();
		return;
	}

	if (
		field === "nick" &&
		feedback &&
		feedback.textContent.includes("już istnieje")
	) {
		cancelInlineEdit();
		return;
	}

	if (!newValue) {
		showInlineFeedback("Wartość nie może być pusta", "error");
		return;
	}

	// BLOKUJ DALSZE EDYTOWANIE
	isSaving = true;
	input.disabled = true;
	input.style.opacity = "0.7";
	input.style.cursor = "wait";

	const xhr = new XMLHttpRequest();
	xhr.open("POST", "update_profile.php", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	xhr.onload = function () {
		// ODŁĄCZ BLOKADĘ NA KONIEC
		isSaving = false;

		if (xhr.status === 200) {
			const response = JSON.parse(xhr.responseText);

			if (response.success) {
				// PROSTSZE ROZWIĄZANIE - ZAWSZE ODTWÓRZ INTERFEJS
				restoreInterfaceAfterSave(newValue);

				// LOGOWANIE ZMIANY PROFILU
				const fieldNames = {
					fullName: "Imię i nazwisko",
					nick: "Nick",
					email: "Email",
					phone: "Telefon",
				};
				logUserAction(
					"profile_update",
					`Zmiana ${fieldNames[field]}: "${originalValue}" → "${newValue}"`
				);

				showSuccessIndicator(currentEditElement);
				setTimeout(() => {
					removeSuccessIndicator(currentEditElement);
					clearInlineFeedback();
					currentEditElement = null;
				}, 2000);
			} else {
				// ODŁĄCZ BLOKADĘ PRZY BŁĘDZIE
				input.disabled = false;
				input.style.opacity = "1";
				input.style.cursor = "text";
				showInlineFeedback(response.message || "Błąd zapisu", "error");
			}
		} else {
			// ODŁĄCZ BLOKADĘ PRZY BŁĘDZIE
			input.disabled = false;
			input.style.opacity = "1";
			input.style.cursor = "text";
			showInlineFeedback("Błąd połączenia", "error");
		}
	};

	xhr.onerror = function () {
		// ODŁĄCZ BLOKADĘ PRZY BŁĘDZIE
		isSaving = false;
		input.disabled = false;
		input.style.opacity = "1";
		input.style.cursor = "text";
		showInlineFeedback("Błąd połączenia", "error");
	};

	const userId = document.body.getAttribute("data-user-id");
	xhr.send(
		`field=${field}&value=${encodeURIComponent(newValue)}&user_id=${userId}`
	);
}

// NOWA FUNKCJA - ZAWSZE ODTWARZA INTERFEJS
function restoreInterfaceAfterSave(newValue) {
	if (!currentEditElement) return;

	// Znajdź data-item i label żeby wiedzieć które pole aktualizować
	const dataItem = currentEditElement.closest(".data-item");
	const label = dataItem?.querySelector("label")?.textContent;

	// Zawsze przywróć span z nową wartością
	currentEditElement.innerHTML = `
        <span>${newValue}</span>
        <button class="edit-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                <path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13" stroke="currentColor" stroke-width="2"/>
                <path d="M18.5 2.5C18.8978 2.10217 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.10217 21.5 2.5C21.8978 2.89782 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.10217 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2"/>
            </svg>
        </button>
    `;

	// Ponowna inicjalizacja przycisku
	const newEditButton = currentEditElement.querySelector(".edit-btn");
	if (newEditButton) {
		newEditButton.addEventListener("click", function (e) {
			e.stopPropagation();
			const dataItem = this.closest(".data-item");
			const label = dataItem.querySelector("label").textContent;
			const valueSpan = dataItem.querySelector(".data-value span");

			const fieldMap = {
				"Imię i nazwisko": "fullName",
				Nick: "nick",
				"E-mail": "email",
				Telefon: "phone",
			};

			const field = fieldMap[label];
			if (field) {
				startInlineEdit(field, valueSpan, this);
			}
		});
	}
}
function finishEditing() {
	if (currentEditElement && !isSaving) {
		saveInlineEdit();
	}
}

function cancelInlineEdit() {
	if (!currentEditElement) return;

	const input = currentEditElement.querySelector(".inline-edit-input");
	if (!input) return;

	const span = document.createElement("span");
	span.textContent = originalValue;
	currentEditElement.replaceChild(span, input);

	const editButton = currentEditElement.querySelector(".edit-btn");
	if (editButton) {
		editButton.style.display = "flex";
	}

	clearInlineFeedback();
	removeSuccessIndicator(currentEditElement);
	currentEditElement = null;
}

function checkNickAvailability(nick) {
	if (nick === originalValue) {
		clearInlineFeedback();
		return;
	}

	if (!nick.trim()) {
		showInlineFeedback("Nick nie może być pusty", "error");
		return;
	}

	const xhr = new XMLHttpRequest();
	xhr.open("GET", `check_nick.php?nick=${encodeURIComponent(nick)}`, true);

	xhr.onload = function () {
		if (xhr.status === 200) {
			const response = JSON.parse(xhr.responseText);
			if (response.status === "available") {
				showInlineFeedback("Nick dostępny", "success");
			} else if (response.status === "taken") {
				showInlineFeedback("Ten nick już istnieje", "error");
			} else {
				showInlineFeedback("Błąd sprawdzania nicku", "error");
			}
		}
	};

	xhr.onerror = function () {
		showInlineFeedback("Błąd połączenia", "error");
	};

	xhr.send();
}

function showInlineFeedback(message, type) {
	if (!currentEditElement) return;

	const feedback = currentEditElement.querySelector(".edit-feedback");
	if (feedback) {
		feedback.textContent = message;
		feedback.className = `edit-feedback ${type}`;
	}
}

function clearInlineFeedback() {
	if (!currentEditElement) return;

	const feedback = currentEditElement.querySelector(".edit-feedback");
	if (feedback) {
		feedback.textContent = "";
		feedback.className = "edit-feedback";
	}
}

function showSuccessIndicator(container) {
	removeSuccessIndicator(container);

	const successIcon = document.createElement("span");
	successIcon.className = "success-indicator";
	successIcon.innerHTML = "✓ Zmieniono";
	container.appendChild(successIcon);

	container.classList.add("has-success");
}

function removeSuccessIndicator(container) {
	if (!container) return;

	const existingIndicator = container.querySelector(".success-indicator");
	if (existingIndicator) {
		existingIndicator.remove();
	}

	container.classList.remove("has-success");
}

function openAvatarModal() {
	document.getElementById("avatarModal").style.display = "flex";
}

function closeAvatarModal() {
	document.getElementById("avatarModal").style.display = "none";
}

function previewAvatar(input) {
	if (input.files && input.files[0]) {
		const reader = new FileReader();
		reader.onload = function (e) {
			document.getElementById("avatarPreview").src = e.target.result;
		};
		reader.readAsDataURL(input.files[0]);
	}
}

function openDangerModal(action) {
	const messages = {
		delete: "Czy na pewno chcesz usunąć konto? Tej operacji nie można cofnąć.",
		logout: "Czy na pewno chcesz się wylogować ze wszystkich urządzeń?",
		permissions: "Czy na pewno chcesz cofnąć wszystkie uprawnienia?",
	};

	if (confirm(messages[action])) {
		const xhr = new XMLHttpRequest();
		xhr.open("POST", "danger_actions.php", true);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		xhr.onload = function () {
			if (xhr.status === 200) {
				const response = JSON.parse(xhr.responseText);
				if (response.success) {
					// LOGOWANIE NIEBEZPIECZNYCH AKCJI
					logUserAction("danger_action", `Wykonano akcję: ${action}`);

					if (action === "delete") {
						window.location.href = "join.php";
					} else if (action === "logout") {
						window.location.reload();
					}
				}
			}
		};

		const userId = document.body.getAttribute("data-user-id");
		xhr.send(`action=${action}&user_id=${userId}`);
	}
}

document.addEventListener("click", function (event) {
	const modals = document.querySelectorAll(".modal");
	modals.forEach((modal) => {
		if (event.target === modal) {
			modal.style.display = "none";
		}
	});
});

document.addEventListener("keydown", function (event) {
	if (event.key === "Escape") {
		const modals = document.querySelectorAll(".modal");
		modals.forEach((modal) => {
			modal.style.display = "none";
		});
	}
});

function initializePasswordChange() {
	const savePasswordBtn = document.querySelector(".password-fields .save-btn");
	if (savePasswordBtn) {
		savePasswordBtn.addEventListener("click", handlePasswordChange);
	}

	const passwordInputs = document.querySelectorAll(
		'.password-fields input[type="password"]'
	);
	passwordInputs.forEach((input) => {
		input.addEventListener("input", validatePasswordFields);
	});
}

function handlePasswordChange() {
	const passwordInputs = document.querySelectorAll(
		'.password-fields input[type="password"]'
	);

	if (passwordInputs.length < 3) {
		showPasswordFeedback("Błąd formularza", "error");
		return;
	}

	const currentPassword = passwordInputs[0].value;
	const newPassword = passwordInputs[1].value;
	const confirmPassword = passwordInputs[2].value;

	if (!currentPassword || !newPassword || !confirmPassword) {
		showPasswordFeedback("Wszystkie pola są wymagane", "error");
		return;
	}

	if (currentPassword === newPassword) {
		showPasswordFeedback("Nowe hasło musi być inne od obecnego", "error");
		return;
	}

	if (newPassword !== confirmPassword) {
		showPasswordFeedback("Hasła nie są identyczne", "error");
		return;
	}

	const passwordStrength = validatePasswordStrength(newPassword);
	if (!passwordStrength.isValid) {
		showPasswordFeedback(passwordStrength.message, "error");
		return;
	}

	changePassword(currentPassword, newPassword);
}

function validatePasswordFields() {
	const passwordInputs = document.querySelectorAll(
		'.password-fields input[type="password"]'
	);

	if (passwordInputs.length < 3) return;

	const newPassword = passwordInputs[1].value;
	const confirmPassword = passwordInputs[2].value;

	if (!newPassword) return;

	const strength = validatePasswordStrength(newPassword);
	const strengthIndicator =
		document.getElementById("password-strength") ||
		createPasswordStrengthIndicator();

	if (newPassword) {
		strengthIndicator.textContent = strength.message;
		strengthIndicator.className = `password-strength ${strength.isValid ? "valid" : "invalid"
			}`;
	} else {
		strengthIndicator.textContent = "";
		strengthIndicator.className = "password-strength";
	}

	if (confirmPassword) {
		const matchIndicator =
			document.getElementById("password-match") ||
			createPasswordMatchIndicator();
		if (newPassword === confirmPassword) {
			matchIndicator.textContent = "Hasła są identyczne";
			matchIndicator.className = "password-match valid";
		} else {
			matchIndicator.textContent = "Hasła nie są identyczne";
			matchIndicator.className = "password-match invalid";
		}
	}
}

function verificationCheck() {
	const verified = document.querySelector(".verified");

	// SPRAWDŹ CZY ELEMENT ISTNIEJE
	if (!verified) {
		console.log("Element .verified nie został znaleziony");
		return;
	}

	const verifiedValue = verified.textContent.trim();

	if (verifiedValue !== "Zweryfikowany" && verifiedValue !== "Zweryfikowano") {
		verified.style.color = "red";
	} else {
		verified.style.color = "green";
	}
}

function createPasswordStrengthIndicator() {
	const indicator = document.createElement("div");
	indicator.id = "password-strength";
	indicator.className = "password-strength";

	const passwordFields = document.querySelector(".password-fields");
	if (!passwordFields) return indicator;

	const passwordInputs = passwordFields.querySelectorAll(
		'input[type="password"]'
	);
	if (passwordInputs.length >= 2) {
		passwordInputs[1].parentNode.insertBefore(
			indicator,
			passwordInputs[1].nextSibling
		);
	}

	return indicator;
}

function createPasswordMatchIndicator() {
	const indicator = document.createElement("div");
	indicator.id = "password-match";
	indicator.className = "password-match";

	const passwordFields = document.querySelector(".password-fields");
	if (!passwordFields) return indicator;

	const passwordInputs = passwordFields.querySelectorAll(
		'input[type="password"]'
	);
	if (passwordInputs.length >= 3) {
		passwordInputs[2].parentNode.insertBefore(
			indicator,
			passwordInputs[2].nextSibling
		);
	}

	return indicator;
}

function validatePasswordStrength(password) {
	const requirements = {
		minLength: password.length >= 8,
		hasUpperCase: /[A-Z]/.test(password),
		hasLowerCase: /[a-z]/.test(password),
		hasNumbers: /\d/.test(password),
		hasSpecialChar: /[!@#$%^&*(),.?":{}|<>]/.test(password),
	};

	const missing = [];

	if (!requirements.minLength) missing.push("co najmniej 8 znaków");
	if (!requirements.hasUpperCase) missing.push("jedną wielką literę");
	if (!requirements.hasLowerCase) missing.push("jedną małą literę");
	if (!requirements.hasNumbers) missing.push("jedną cyfrę");
	if (!requirements.hasSpecialChar) missing.push("jeden znak specjalny");

	if (missing.length === 0) {
		return {
			isValid: true,
			message: "Hasło spełnia wymagania",
		};
	} else {
		return {
			isValid: false,
			message: `Hasło musi zawierać: ${missing.join(", ")}`,
		};
	}
}

function changePassword(currentPassword, newPassword) {
	const xhr = new XMLHttpRequest();
	xhr.open("POST", "change_password.php", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	xhr.onload = function () {
		if (xhr.status === 200) {
			if (!xhr.responseText || xhr.responseText.trim() === "") {
				showPasswordFeedback("Błąd serwera - pusta odpowiedź", "error");
				return;
			}

			try {
				const response = JSON.parse(xhr.responseText);

				if (response.success) {
					// LOGOWANIE ZMIANY HASŁA
					logUserAction("password_change", "Hasło zostało zmienione");

					showPasswordFeedback("Hasło zostało zmienione pomyślnie", "success");
					clearPasswordFields();
				} else {
					showPasswordFeedback(
						response.message || "Błąd zmiany hasła",
						"error"
					);
				}
			} catch (e) {
				console.error("Błąd parsowania odpowiedzi:", e);
				showPasswordFeedback(
					"Błąd serwera - skontaktuj się z administratorem",
					"error"
				);
			}
		} else {
			showPasswordFeedback("Błąd połączenia: " + xhr.status, "error");
		}
	};

	xhr.onerror = function () {
		showPasswordFeedback("Błąd połączenia z serwerem", "error");
	};

	const userId = document.body.getAttribute("data-user-id");
	xhr.send(
		`current_password=${encodeURIComponent(
			currentPassword
		)}&new_password=${encodeURIComponent(newPassword)}&user_id=${userId}`
	);
}

function showPasswordFeedback(message, type) {
	let feedback = document.querySelector(".password-feedback");
	if (!feedback) {
		feedback = document.createElement("div");
		feedback.className = "password-feedback";
		const passwordFields = document.querySelector(".password-fields");
		if (passwordFields) {
			passwordFields.appendChild(feedback);
		} else {
			return;
		}
	}

	feedback.textContent = message;
	feedback.className = `password-feedback ${type}`;
}

function clearPasswordFields() {
	const inputs = document.querySelectorAll(
		'.password-fields input[type="password"]'
	);
	inputs.forEach((input) => (input.value = ""));

	const strengthIndicator = document.getElementById("password-strength");
	if (strengthIndicator) strengthIndicator.textContent = "";

	const matchIndicator = document.getElementById("password-match");
	if (matchIndicator) matchIndicator.textContent = "";
}

function initializeNotifications() {
	const checkboxes = document.querySelectorAll(
		'.checkbox-group input[type="checkbox"]'
	);

	loadNotificationSettings();

	checkboxes.forEach((checkbox, index) => {
		checkbox.addEventListener("change", function () {
			updateNotificationSetting(index, this.checked);
		});
	});
}

function loadNotificationSettings() {
	const xhr = new XMLHttpRequest();
	xhr.open(
		"GET",
		`get_notifications.php?user_id=${document.body.getAttribute(
			"data-user-id"
		)}`,
		true
	);

	xhr.onload = function () {
		if (xhr.status === 200) {
			if (!xhr.responseText || xhr.responseText.trim() === "") {
				console.error("Pusta odpowiedź serwera");
				return;
			}

			try {
				const response = JSON.parse(xhr.responseText);
				if (response.success) {
					updateCheckboxes(response.settings);
				} else {
					console.error("Błąd ładowania ustawień:", response.message);
					setDefaultCheckboxes();
				}
			} catch (e) {
				console.error("Błąd parsowania ustawień:", e);
				setDefaultCheckboxes();
			}
		} else {
			console.error("Błąd HTTP:", xhr.status);
			setDefaultCheckboxes();
		}
	};

	xhr.onerror = function () {
		console.error("Błąd połączenia");
		setDefaultCheckboxes();
	};

	xhr.send();
}

function updateCheckboxes(settings) {
	const checkboxes = document.querySelectorAll(
		'.checkbox-group input[type="checkbox"]'
	);

	checkboxes[0].checked = settings.new_tasks_email;
	checkboxes[1].checked = settings.new_comments_email;
	checkboxes[2].checked = settings.system_email;
}

function updateNotificationSetting(settingIndex, isChecked) {
	const settingMap = {
		0: "new_tasks_email",
		1: "new_comments_email",
		2: "system_email",
	};

	const settingName = settingMap[settingIndex];
	if (!settingName) return;

	const settingNames = {
		new_tasks_email: "E-mail o nowych zadaniach",
		new_comments_email: "Powiadomienia o komentarzach",
		system_email: "Powiadomienia systemowe",
	};

	const xhr = new XMLHttpRequest();
	xhr.open("POST", "update_notification.php", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	xhr.onload = function () {
		if (xhr.status === 200) {
			try {
				const response = JSON.parse(xhr.responseText);
				if (!response.success) {
					const checkboxes = document.querySelectorAll(
						'.checkbox-group input[type="checkbox"]'
					);
					checkboxes[settingIndex].checked = !isChecked;
					showNotificationFeedback("Błąd zapisu ustawienia", "error");
				} else {
					// LOGOWANIE ZMIANY POWIADOMIEŃ
					logUserAction(
						"notification_change",
						`${settingNames[settingName]}: ${isChecked ? "włączone" : "wyłączone"
						}`
					);
					showNotificationFeedback("Ustawienie zapisane", "success");
				}
			} catch (e) {
				console.error("Błąd aktualizacji ustawienia:", e);
			}
		}
	};

	const userId = document.body.getAttribute("data-user-id");
	xhr.send(
		`user_id=${userId}&setting=${settingName}&value=${isChecked ? 1 : 0}`
	);
}

function showNotificationFeedback(message, type) {
	let feedback = document.querySelector(".notification-feedback");
	if (!feedback) {
		feedback = document.createElement("div");
		feedback.className = "notification-feedback";
		document.querySelector(".setting-group h3").after(feedback);
	}

	feedback.textContent = message;
	feedback.className = `notification-feedback ${type}`;

	setTimeout(() => {
		feedback.remove();
	}, 2000);
}

function setDefaultCheckboxes() {
	const checkboxes = document.querySelectorAll(
		'.checkbox-group input[type="checkbox"]'
	);
	checkboxes[0].checked = true;
	checkboxes[1].checked = true;
	checkboxes[2].checked = false;
}

function initializePreferences() {
	const selects = document.querySelectorAll(".preference-item select");

	loadPreferences();

	selects.forEach((select, index) => {
		select.addEventListener("change", function () {
			updatePreference(index, this.value);
		});
	});
}

function loadPreferences() {
	const xhr = new XMLHttpRequest();
	xhr.open(
		"GET",
		`get_preferences.php?user_id=${document.body.getAttribute("data-user-id")}`,
		true
	);

	xhr.onload = function () {
		if (xhr.status === 200) {
			try {
				const response = JSON.parse(xhr.responseText);
				if (response.success) {
					updateSelects(response.preferences);
				}
			} catch (e) {
				console.error("Błąd ładowania preferencji:", e);
				setDefaultPreferences();
			}
		} else {
			setDefaultPreferences();
		}
	};

	xhr.onerror = function () {
		setDefaultPreferences();
	};

	xhr.send();
}

function updateSelects(preferences) {
	const selects = document.querySelectorAll(".preference-item select");

	if (preferences.default_role && selects[0]) {
		selects[0].value = preferences.default_role;
	}

	if (preferences.engagement_level && selects[1]) {
		selects[1].value = preferences.engagement_level;
	}
}

function updatePreference(preferenceIndex, value) {
	const preferenceMap = {
		0: "default_role",
		1: "engagement_level",
	};

	const preferenceName = preferenceMap[preferenceIndex];
	if (!preferenceName) return;

	const preferenceNames = {
		default_role: "Domyślna rola",
		engagement_level: "Poziom zaangażowania",
	};

	const xhr = new XMLHttpRequest();
	xhr.open("POST", "update_preferences.php", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	xhr.onload = function () {
		if (xhr.status === 200) {
			try {
				const response = JSON.parse(xhr.responseText);
				if (response.success) {
					// LOGOWANIE ZMIANY PREFERENCJI
					logUserAction(
						"preference_change",
						`${preferenceNames[preferenceName]}: "${value}"`
					);
					showPreferenceFeedback("Preferencja zapisana", "success");
				} else {
					showPreferenceFeedback("Błąd zapisu preferencji", "error");
				}
			} catch (e) {
				console.error("Błąd aktualizacji preferencji:", e);
			}
		}
	};

	const userId = document.body.getAttribute("data-user-id");
	xhr.send(
		`user_id=${userId}&preference=${preferenceName}&value=${encodeURIComponent(
			value
		)}`
	);
}

function setDefaultPreferences() {
	const selects = document.querySelectorAll(".preference-item select");
	if (selects[0]) selects[0].value = "Uczestnik";
	if (selects[1]) selects[1].value = "Aktywnie uczestniczę";
}

function showPreferenceFeedback(message, type) {
	let feedback = document.querySelector(".preference-feedback");
	if (!feedback) {
		feedback = document.createElement("div");
		feedback.className = "preference-feedback";
		document
			.querySelector(".preferences-section .section-header")
			.appendChild(feedback);
	}

	feedback.textContent = message;
	feedback.className = `preference-feedback ${type}`;

	setTimeout(() => {
		feedback.remove();
	}, 2000);
}

function logUserAction(action, details = "") {
	const xhr = new XMLHttpRequest();
	xhr.open("POST", "../subpages/global/log_action.php", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	xhr.onload = function () {
		if (xhr.status !== 200) {
			console.error("Błąd logowania akcji:", action);
		}
	};

	xhr.onerror = function () {
		console.error("Błąd połączenia z ../subpages/global/log_action.php"); // DEBUG
	};

	const userId = document.body.getAttribute("data-user-id");
	const userEmail =
		document
			.querySelector("[data-user-email]")
			?.getAttribute("data-user-email") || "";

	xhr.send(
		`user_id=${userId}` +
		`&email=${encodeURIComponent(userEmail)}` +
		`&action=${encodeURIComponent(action)}` +
		`&details=${encodeURIComponent(details)}`
	);
}

function openDeleteModal() {
	document.getElementById("deleteModal").classList.add("active");
}

function closeDeleteModal() {
	document.getElementById("deleteModal").classList.remove("active");
}

function confirmDelete() {
	const btn = document.getElementById("confirmDeleteBtn");
	btn.disabled = true;
	btn.innerHTML = "Usuwanie...";

	fetch("delete_account.php", {
		method: "POST",
		headers: {
			"Content-Type": "application/x-www-form-urlencoded",
		},
		body: "confirm_delete=true",
	})
		.then((response) => response.json())
		.then((data) => {
			if (data.success) {
				window.location.href = "../index.php";
			} else {
				alert("Błąd: " + data.message);
				closeDeleteModal();
			}
		})
		.catch((error) => {
			console.error("Error:", error);
			alert("Wystąpił błąd podczas usuwania konta");
			closeDeleteModal();
		});
}

document.getElementById("deleteModal").addEventListener("click", function (e) {
	if (e.target === this) {
		closeDeleteModal();
	}
});

function saveAvatar() {
	console.log("🔄 saveAvatar function started");

	const saveBtn = document.querySelector("#avatarModal .modal-btn.primary");
	console.log("🔍 Save button in saveAvatar:", saveBtn);

	const fileInput = document.getElementById("avatarUpload");

	// SPRAWDŹ CZY PLIK ISTNIEJE
	if (!fileInput) {
		console.error("❌ Nie znaleziono inputa avatarUpload");
		showAvatarFeedback("Błąd formularza - brak inputa", "error");
		return;
	}

	if (!fileInput.files || fileInput.files.length === 0) {
		console.error("❌ Nie wybrano pliku");
		showAvatarFeedback("Wybierz plik", "error");
		return;
	}

	const file = fileInput.files[0];
	console.log("📁 Wybrany plik:", file.name, file.size, file.type);

	// Walidacja pliku
	const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
	const maxFileSize = 5 * 1024 * 1024;

	if (!allowedTypes.includes(file.type)) {
		showAvatarFeedback("Dozwolone formaty: JPG, PNG, GIF", "error");
		return;
	}

	if (file.size > maxFileSize) {
		showAvatarFeedback("Plik jest za duży (max 5MB)", "error");
		return;
	}

	// ✅ BEZPIECZNA AKTUALIZACJA PRZYCISKU
	let originalText = "Zapisz zdjęcie";
	if (saveBtn && saveBtn.textContent) {
		originalText = saveBtn.textContent;
		saveBtn.textContent = "Zapisywanie...";
		saveBtn.disabled = true;
		console.log("🔘 Przycisk zablokowany");
	}

	// PRZYGOTUJ DANE
	const formData = new FormData();
	formData.append("avatar", file);

	const userId = document.body.getAttribute("data-user-id");
	if (userId) {
		formData.append("user_id", userId);
		console.log("👤 User ID:", userId);
	} else {
		console.error("❌ Brak user ID");
		showAvatarFeedback("Błąd sesji", "error");
		if (saveBtn) {
			saveBtn.textContent = originalText;
			saveBtn.disabled = false;
		}
		return;
	}

	// WYŚLIJ ZAPYTANIE
	const xhr = new XMLHttpRequest();
	xhr.open("POST", "update_avatar.php", true);

	xhr.onload = function () {
		console.log("📡 Odpowiedź serwera:", xhr.status);

		// ✅ PRZYWRÓĆ PRZYCISK
		if (saveBtn) {
			saveBtn.textContent = originalText;
			saveBtn.disabled = false;
			console.log("🔘 Przycisk przywrócony");
		}

		if (xhr.status === 200) {
			try {
				const response = JSON.parse(xhr.responseText);
				console.log("📄 Odpowiedź JSON:", response);

				if (response.success) {
					// ✅ TYLKO ODSWIERZENIE STRONY - USUŃ CAŁĄ RESZTĘ
					console.log("✅ Sukces! Odświeżam stronę...");

					// Zamknij modal
					closeAvatarModal();

					// Pokaz komunikat
					showAvatarFeedback(
						"✅ Avatar został zmieniony! Odświeżam stronę...",
						"success"
					);

					window.location.reload();
				} else {
					console.error("❌ Błąd z serwera:", response.message);
					showAvatarFeedback(
						response.message || "Błąd zapisu avatara",
						"error"
					);
				}
			} catch (e) {
				console.error("❌ Błąd parsowania JSON:", e);
				showAvatarFeedback("Błąd serwera - nieprawidłowa odpowiedź", "error");
			}
		} else {
			console.error("❌ Błąd HTTP:", xhr.status);
			showAvatarFeedback("Błąd połączenia: " + xhr.status, "error");
		}
	};

	xhr.onerror = function () {
		console.error("❌ Błąd XHR");
		if (saveBtn) {
			saveBtn.textContent = originalText;
			saveBtn.disabled = false;
		}
		showAvatarFeedback("Błąd połączenia z serwerem", "error");
	};

	xhr.upload.onprogress = function (e) {
		if (e.lengthComputable) {
			const percentComplete = Math.round((e.loaded / e.total) * 100);
			console.log("📤 Upload progress: " + percentComplete + "%");
		}
	};

	console.log("🚀 Wysyłanie żądania...");
	xhr.send(formData);
}

function showAvatarFeedback(message, type) {
	let feedback = document.querySelector(".avatar-feedback");
	if (!feedback) {
		feedback = document.createElement("div");
		feedback.className = "avatar-feedback";
		const modalContent = document.querySelector("#avatarModal .modal-content");
		if (modalContent) {
			modalContent.appendChild(feedback);
		} else {
			console.error("❌ Nie znaleziono modal-content");
			return;
		}
	}

	feedback.textContent = message;
	feedback.className = `avatar-feedback ${type}`;

	// Automatyczne usunięcie po 3 sekundach
	setTimeout(() => {
		if (feedback && feedback.parentNode) {
			feedback.remove();
		}
	}, 3000);
}

// Odblokowanie przycisku po wybraniu pliku
avatarUpload.addEventListener('change', function () {
	const saveBtn = document.getElementById('saveAvatarBtn');
	saveBtn.disabled = !this.files.length;

	if (this.files.length) {
		// Tutaj też można dodać walidację pliku
		const file = this.files[0];
		if (file.size > 5 * 1024 * 1024) { // 5MB
			saveBtn.disabled = true;
			showError('Plik jest za duży');
		}
	}
});

function logUserAction(action, details = "") {
	const userId = document.body.getAttribute("data-user-id");
	const xhr = new XMLHttpRequest();
	xhr.open("POST", "../subpages/global/log_action.php", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	xhr.onload = function () {
		if (xhr.status === 200) {
			try {
				const response = JSON.parse(xhr.responseText);
				if (!response.success) {
					console.error(`Nie udało się zalogować akcji: ${action}`, response.message);
				} else {
					console.log(`✅ Zalogowano akcję: ${action}`, details);
				}
			} catch (e) {
				console.error("Błąd parsowania odpowiedzi logu:", e);
			}
		} else {
			console.error("Błąd logowania akcji:", xhr.status, action);
		}
	};

	xhr.onerror = function () {
		console.error("Błąd połączenia przy logowaniu akcji:", action);
	};

	xhr.send(
		`user_id=${encodeURIComponent(userId)}&action=${encodeURIComponent(
			action
		)}&details=${encodeURIComponent(details)}`
	);
}


const burgerMenu = document.getElementById("burger-menu");
const navMenu = document.querySelector(".nav-menu");

burgerMenu.addEventListener("click", () => {
	burgerMenu.classList.toggle("active");
	navMenu.classList.toggle("active");
});
