<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'profil';
?>

<div id="weinsteigfinance-profil" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>👤 Mein Profil</h2>

	<div id="profil-container">
		<p style="color: #999;">Lädt...</p>
	</div>
</div>

<style>
	.profil-card {
		background: white;
		border: 1px solid #ecf0f1;
		border-radius: 6px;
		padding: 20px;
		margin-bottom: 20px;
	}

	.profil-field {
		display: grid;
		grid-template-columns: 150px 1fr;
		gap: 16px;
		padding: 12px 0;
		border-bottom: 1px solid #ecf0f1;
	}

	.profil-field:last-child {
		border-bottom: none;
	}

	.profil-field-label {
		font-weight: 600;
		color: #2c3e50;
	}

	.profil-field-value {
		color: #555;
	}

	.liegenschaft-box {
		background: #f0f8ff;
		border-left: 4px solid #0082c9;
		padding: 16px;
		border-radius: 4px;
		margin-top: 20px;
	}

	.edit-name-btn {
		padding: 4px 12px;
		background: #0082c9;
		color: white;
		border: none;
		border-radius: 3px;
		cursor: pointer;
		font-size: 12px;
		margin-left: 8px;
	}

	.edit-name-btn:hover {
		background: #0066a3;
	}

	.edit-name-input {
		padding: 6px 8px;
		border: 1px solid #0082c9;
		border-radius: 3px;
		font-size: 14px;
		flex: 1;
		max-width: 300px;
	}

	.edit-name-actions {
		margin-top: 12px;
		display: flex;
		gap: 8px;
	}

	.edit-name-actions button {
		padding: 6px 16px;
		border: none;
		border-radius: 3px;
		cursor: pointer;
		font-size: 13px;
	}

	.edit-name-save {
		background: #28a745;
		color: white;
	}

	.edit-name-save:hover {
		background: #218838;
	}

	.edit-name-cancel {
		background: #ccc;
		color: #333;
	}

	.edit-name-cancel:hover {
		background: #bbb;
	}

	.edit-name-message {
		margin-top: 8px;
		font-size: 12px;
		padding: 8px;
		border-radius: 3px;
	}

	.edit-name-success {
		background: #d4edda;
		color: #155724;
	}

	.edit-name-error {
		background: #f8d7da;
		color: #721c24;
	}
</style>

<script>
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
			if (data.signed_mandate_exists) {
				html += '<div class="liegenschaft-box">';
				html += '<strong>✅ SEPA-Mandat gültig</strong><br>';
				html += 'Ein unterschriebenes Mandat wurde hochgeladen und ist gültig. Lastschriften können eingezogen werden.';
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
</script>
