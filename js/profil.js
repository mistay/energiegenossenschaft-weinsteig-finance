function load() {
	const container = document.getElementById('profil-container');

	// Load user info first (groups and username)
	fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-groups'))
		.then(r => r.json())
		.then(userInfo => {
			if (!userInfo || userInfo.error) {
				container.innerHTML = '<p style="color: red;">Fehler: Benutzerdaten nicht geladen</p>';
				return;
			}

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
					html += '<div class="profil-field-value">' + escapeHtml(userInfo.userId) + '</div>';
					html += '</div>';

					// Display roles
					if (userInfo.groups && userInfo.groups.length > 0) {
						const groupLabels = {
							'obpersonen': '👑 Admin',
							'mitglieder': '🏠 Mitglied'
						};
						const labels = userInfo.groups.map(g => groupLabels[g] || g).join(', ');
						html += '<div class="profil-field">';
						html += '<div class="profil-field-label">Rollen:</div>';
						html += '<div class="profil-field-value">' + escapeHtml(labels) + '</div>';
						html += '</div>';
					}

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

					// Mandat-Validierung: Gültig nur wenn nicht zurückgezogen UND PDF hochgeladen
					let mandatHtml = '<div>';
					if (member.mandate_withdrawn_date) {
						mandatHtml += '<div style="color: #dc3545; margin-bottom: 8px;"><strong>Mandat Status:</strong> ⚠️ Zurückgezogen</div>';
						mandatHtml += '<div style="color: #dc3545; font-size: 12px; background: #ffebee; padding: 8px; border-radius: 4px;">';
						mandatHtml += '<strong>Grund:</strong> ' + escapeHtml(member.mandate_withdrawn_reason || 'Grund nicht angegeben');
						mandatHtml += '</div>';
					} else if (!member.signed_mandate_exists) {
						mandatHtml += '<div style="color: #ff9800; margin-bottom: 8px;"><strong>Mandat Status:</strong> ⏳ Nicht gültig</div>';
						mandatHtml += '<div style="color: #ff9800; font-size: 12px; background: #fff3e0; padding: 8px; border-radius: 4px;">';
						mandatHtml += '<strong>Info:</strong> Es wurde noch kein unterschriebenes SEPA-Mandat hochgeladen. ';
						mandatHtml += 'Bitte laden Sie das unterzeichnete Mandatsformular im Bereich "SEPA Lastschrift" hoch.';
						mandatHtml += '</div>';
					} else if (member.mandate_granted_date) {
						mandatHtml += '<div style="color: #28a745; margin-bottom: 8px;"><strong>Mandat Status:</strong> ✓ Gültig</div>';
						mandatHtml += '<div style="color: #28a745; font-size: 12px; background: #f1f8e9; padding: 8px; border-radius: 4px;">';
						mandatHtml += '<strong>Gültig seit:</strong> ' + escapeHtml(member.mandate_granted_date);
						mandatHtml += '</div>';
					} else {
						mandatHtml += '<div style="color: #ff9800; margin-bottom: 8px;"><strong>Mandat Status:</strong> ⏳ Nicht gültig</div>';
						mandatHtml += '<div style="color: #ff9800; font-size: 12px; background: #fff3e0; padding: 8px; border-radius: 4px;">';
						mandatHtml += '<strong>Info:</strong> Es wurde noch kein unterschriebenes SEPA-Mandat hochgeladen und kein Erteilungsdatum erfasst. ';
						mandatHtml += 'Bitte laden Sie das unterzeichnete Mandatsformular im Bereich "SEPA Lastschrift" hoch.';
						mandatHtml += '</div>';
					}
					mandatHtml += '</div>';
					html += mandatHtml;

					html += '</div>';
					html += '</div>';

					container.innerHTML = html;
				})
				.catch(err => {
					container.innerHTML = '<p style="color: red;">Fehler beim Laden der Liegenschaftsdaten: ' + escapeHtml(err.message) + '</p>';
				});
		})
		.catch(err => {
			container.innerHTML = '<p style="color: red;">Fehler beim Laden der Benutzerdaten: ' + escapeHtml(err.message) + '</p>';
		});
}

function escapeHtml(text) {
	if (!text) return '';
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', load);
