document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('profil-container');
	const myMemberUrl = OCA?.generateUrl?.('/apps/weinsteigfinance/api/my-member') || '/index.php/apps/weinsteigfinance/api/my-member';

	fetch(myMemberUrl, { credentials: 'include' })
		.then(r => r.json())
		.then(data => {
			if (data.error) {
				container.innerHTML = '<p style="color: red;">Fehler: ' + data.error + '</p>';
				return;
			}

			let html = '<div class="profil-card">';

			// DisplayName (bearbeitbar)
			html += '<div class="profil-field">';
			html += '<div class="profil-field-label">👤 Name</div>';
			html += '<div class="profil-field-value">';
			html += '<span id="displayName-text">' + escapeHtml(data.displayName || 'N/A') + '</span>';
			html += '<button class="edit-name-btn" onclick="toggleEditName()">✏️ Bearbeiten</button>';
			html += '</div>';
			html += '</div>';

			// Edit-Mode (versteckt)
			html += '<div id="edit-name-section" style="display: none;">';
			html += '<input type="text" id="displayName-input" class="edit-name-input" value="' + escapeHtml(data.displayName || '') + '" placeholder="Neuer Name">';
			html += '<div class="edit-name-actions">';
			html += '<button class="edit-name-save" onclick="saveDisplayName()">✓ Speichern</button>';
			html += '<button class="edit-name-cancel" onclick="cancelEditName()">✗ Abbrechen</button>';
			html += '</div>';
			html += '<div id="edit-name-message"></div>';
			html += '</div>';

			// Andere Felder
			html += '<div class="profil-field">';
			html += '<div class="profil-field-label">🏠 Liegenschaft</div>';
			html += '<div class="profil-field-value">' + escapeHtml(data.address || 'N/A') + '</div>';
			html += '</div>';

			if (data.iban) {
				html += '<div class="profil-field">';
				html += '<div class="profil-field-label">🏦 IBAN</div>';
				html += '<div class="profil-field-value" style="font-family: monospace;">' + escapeHtml(data.iban) + '</div>';
				html += '</div>';
			}

			if (data.kontoinhaber) {
				html += '<div class="profil-field">';
				html += '<div class="profil-field-label">💳 Kontoinhaber</div>';
				html += '<div class="profil-field-value">' + escapeHtml(data.kontoinhaber) + '</div>';
				html += '</div>';
			}

			html += '</div>';

			// Mandat Status
			// Mandat Status
			if (data.mandate_approved) {
				html += '<div class="liegenschaft-box">';
				html += '<strong>✅ SEPA-Mandat gültig</strong><br>';
				html += 'Ein unterschriebenes Mandat wurde hochgeladen und von den Kassier:innen genehmigt. Lastschriften können eingezogen werden.';
				html += '</div>';
			} else if (data.signed_mandate_exists) {
				html += '<div class="liegenschaft-box">';
				html += '<strong>⏳ Mandat wartet auf Freigabe</strong><br>';
				html += 'Ein Mandat wurde hochgeladen und wartet auf die Genehmigung durch die Kassier:innen. Danach können Lastschriften eingezogen werden.';
				html += '</div>';
			} else {
				html += '<div class="liegenschaft-box">';
				html += '<strong>⏳ SEPA-Mandat erforderlich</strong><br>';
				html += 'Bitte geben Sie eine IBAN ein und laden Sie ein unterschriebenes Mandat hoch, damit Zahlungen eingezogen werden können.';
				html += '</div>';
			}

			container.innerHTML = html;
			window.myMemberData = data;
		})
		.catch(err => {
			container.innerHTML = '<p style="color: red;">Fehler beim Laden: ' + err.message + '</p>';
		});
});

function escapeHtml(text) {
	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return text.replace(/[&<>"']/g, m => map[m]);
}

function toggleEditName() {
	document.getElementById('displayName-text').style.display = 'none';
	document.querySelector('.edit-name-btn').style.display = 'none';
	document.getElementById('edit-name-section').style.display = 'block';
	document.getElementById('displayName-input').focus();
}

function cancelEditName() {
	document.getElementById('displayName-text').style.display = 'inline';
	document.querySelector('.edit-name-btn').style.display = 'inline';
	document.getElementById('edit-name-section').style.display = 'none';
	document.getElementById('edit-name-message').innerHTML = '';
}

function saveDisplayName() {
	const newName = document.getElementById('displayName-input').value.trim();
	const messageDiv = document.getElementById('edit-name-message');

	if (!newName) {
		messageDiv.textContent = 'Name darf nicht leer sein';
		messageDiv.className = 'edit-name-message edit-name-error';
		return;
	}

	messageDiv.textContent = '⏳ Speichert...';
	messageDiv.className = 'edit-name-message';

	const updateUrl = OCA?.generateUrl?.('/apps/weinsteigfinance/api/update-display-name') || '/index.php/apps/weinsteigfinance/api/update-display-name';

	fetch(updateUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		credentials: 'include',
		body: JSON.stringify({ displayName: newName })
	})
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				document.getElementById('displayName-text').textContent = newName;
				messageDiv.textContent = '✓ Name erfolgreich aktualisiert';
				messageDiv.className = 'edit-name-message edit-name-success';

				setTimeout(() => {
					cancelEditName();
				}, 1500);
			} else {
				messageDiv.textContent = 'Fehler: ' + (data.error || 'Unbekannter Fehler');
				messageDiv.className = 'edit-name-message edit-name-error';
			}
		})
		.catch(err => {
			messageDiv.textContent = 'Fehler: ' + err.message;
			messageDiv.className = 'edit-name-message edit-name-error';
		});
}
