function load() {
	const container = document.getElementById('profil-container');

	// Lade Nutzer-Info + Mitglied-Info
	fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-member'))
		.then(r => r.json())
		.then(member => {
			if (!member || member.error) {
				container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(member?.message || 'Nicht gefunden') + '</p>';
				return;
			}

			let html = '<div class="profil-card">';
			html += '<h3 style="margin-top: 0;">Persönliche Informationen</h3>';

			html += '<div class="profil-field">';
			html += '<div class="profil-field-label">Benutzername:</div>';
			html += '<div class="profil-field-value">' + escapeHtml(getUserId()) + '</div>';
			html += '</div>';

			html += '</div>';

			// Liegenschaft
			html += '<div class="profil-card">';
			html += '<h3 style="margin-top: 0;">🏘️ Zugeordnete Liegenschaft</h3>';
			html += '<div class="liegenschaft-box">';
			html += '<div style="font-size: 18px; font-weight: 600; color: #0082c9; margin-bottom: 8px;">' + escapeHtml(member.address) + '</div>';

			if (member.zahlungspflichtig) {
				html += '<div style="margin-bottom: 8px;"><strong>Zahlungspflichtig:</strong> ' + escapeHtml(member.zahlungspflichtig) + '</div>';
			}

			if (member.iban) {
				html += '<div style="margin-bottom: 8px;"><strong>IBAN:</strong> <code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">' + escapeHtml(member.iban) + '</code></div>';
			} else {
				html += '<div style="margin-bottom: 8px; color: #999;"><strong>IBAN:</strong> Nicht hinterlegt</div>';
			}

			if (member.mandate_withdrawn_date) {
				html += '<div style="color: #dc3545;"><strong>Mandat:</strong> ⚠️ Zurückgezogen (' + escapeHtml(member.mandate_withdrawn_reason || 'Grund nicht angegeben') + ')</div>';
			} else if (member.mandate_granted_date) {
				html += '<div style="color: #28a745;"><strong>Mandat:</strong> ✓ Gültig seit ' + escapeHtml(member.mandate_granted_date) + '</div>';
			} else {
				html += '<div style="color: #ff9800;"><strong>Mandat:</strong> ⏳ Nicht erteilt</div>';
			}

			html += '</div>';
			html += '</div>';

			container.innerHTML = html;
		})
		.catch(err => {
			container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(err.message) + '</p>';
		});
}

function escapeHtml(text) {
	if (!text) return '';
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

function getUserId() {
	// Versuche Nutzer-ID aus dem DOM zu bekommen
	const userId = document.querySelector('[data-uid]')?.getAttribute('data-uid');
	return userId || 'Nutzer';
}

document.addEventListener('DOMContentLoaded', load);
