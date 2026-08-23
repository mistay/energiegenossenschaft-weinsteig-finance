// Load reminder texts from database
function loadReminderTexts() {
	fetch(OC.generateUrl('/apps/weinsteigfinance/api/reminder-texts'))
		.then(r => r.json())
		.then(data => {
			if (data && typeof data === 'object') {
				for (let stage = 1; stage <= 3; stage++) {
					if (data[stage]) {
						document.getElementById(`reminder-subject-${stage}`).value = data[stage].subject || '';
						document.getElementById(`reminder-body-${stage}`).value = data[stage].body || '';
					}
				}
			}
		})
		.catch(e => {
			console.error('Error loading reminder texts:', e);
			showNotification('Fehler beim Laden der Mahnstufen-Texte', 'error');
		});
}

// Save reminder text for a stage
function saveReminderText(stage) {
	const subject = document.getElementById(`reminder-subject-${stage}`).value.trim();
	const body = document.getElementById(`reminder-body-${stage}`).value.trim();

	if (!subject || !body) {
		showNotification('Betreff und Nachrichtentext sind erforderlich', 'error');
		return;
	}

	const statusEl = document.querySelector(`.reminder-status[data-stage="${stage}"]`);
	statusEl.style.display = 'inline';
	statusEl.textContent = '...';
	statusEl.style.color = '#666';

	fetch(OC.generateUrl(`/apps/weinsteigfinance/api/reminder-texts/${stage}`), {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
		},
		body: JSON.stringify({ subject, body })
	})
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				statusEl.textContent = '✓ Gespeichert';
				statusEl.style.color = '#2e7d32';
				setTimeout(() => {
					statusEl.style.display = 'none';
				}, 3000);
			} else {
				showNotification(data.error || 'Fehler beim Speichern', 'error');
				statusEl.textContent = '✗ Fehler';
				statusEl.style.color = '#c62828';
			}
		})
		.catch(e => {
			console.error('Error saving reminder text:', e);
			showNotification('Fehler beim Speichern der Mahnstufe', 'error');
			statusEl.textContent = '✗ Fehler';
			statusEl.style.color = '#c62828';
		});
}

// Setup event listeners
function setupReminderTextListeners() {
	document.querySelectorAll('.reminder-save-btn').forEach(btn => {
		btn.addEventListener('click', () => {
			const stage = btn.dataset.stage;
			saveReminderText(parseInt(stage));
		});
	});
}

// Show notification using Nextcloud Toast
function showNotification(message, type = 'info') {
	if (typeof OCP !== 'undefined' && OCP.Toast) {
		OCP.Toast.success(message);
	} else {
		console.log(`[${type}] ${message}`);
	}
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', () => {
	loadReminderTexts();
	setupReminderTextListeners();
});
