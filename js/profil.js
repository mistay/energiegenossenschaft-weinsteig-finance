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
					html += '<div style="font-size: 18px; font-weight: 600; color: #0082c9;">' + escapeHtml(member.address) + '</div>';
					html += '</div>';
					html += '</div>';

					// SEPA Mandat
					html += '<div class="profil-card">';
					html += '<h3 style="margin-top: 0;">🏦 SEPA Mandat</h3>';

					if (member.zahlungspflichtig) {
						html += '<div class="profil-field">';
						html += '<div class="profil-field-label">Zahlungspflichtig:</div>';
						html += '<div class="profil-field-value">' + escapeHtml(member.zahlungspflichtig) + '</div>';
						html += '</div>';
					}

					if (member.iban) {
						html += '<div class="profil-field">';
						html += '<div class="profil-field-label">IBAN:</div>';
						html += '<div class="profil-field-value"><code style="background: #f5f5f5; padding: 4px 8px; border-radius: 3px; font-size: 12px;">' + escapeHtml(member.iban) + '</code></div>';
						html += '</div>';
					} else {
						html += '<div class="profil-field">';
						html += '<div class="profil-field-label">IBAN:</div>';
						html += '<div class="profil-field-value" style="color: #999;">Nicht hinterlegt</div>';
						html += '</div>';
					}

					// Mandat Status
					let mandatHtml = '';
					if (member.mandate_withdrawn_date) {
						mandatHtml += '<div class="profil-field">';
						mandatHtml += '<div class="profil-field-label">Status:</div>';
						mandatHtml += '<div class="profil-field-value" style="color: #dc3545;"><strong>⚠️ Zurückgezogen</strong></div>';
						mandatHtml += '</div>';
						mandatHtml += '<div style="background: #ffebee; padding: 12px; border-radius: 4px; border-left: 4px solid #dc3545; margin-top: 12px;">';
						mandatHtml += '<div style="color: #dc3545; font-size: 13px;"><strong>Grund:</strong> ' + escapeHtml(member.mandate_withdrawn_reason || 'Grund nicht angegeben') + '</div>';
						mandatHtml += '</div>';
					} else if (!member.signed_mandate_exists) {
						mandatHtml += '<div class="profil-field">';
						mandatHtml += '<div class="profil-field-label">Status:</div>';
						mandatHtml += '<div class="profil-field-value" style="color: #ff9800;"><strong>⏳ Nicht gültig</strong></div>';
						mandatHtml += '</div>';
						mandatHtml += '<div style="background: #fff3e0; padding: 12px; border-radius: 4px; border-left: 4px solid #ff9800; margin-top: 12px;">';
						mandatHtml += '<div style="color: #ff9800; font-size: 13px;">';
						mandatHtml += '<strong>Info:</strong> Es wurde noch kein unterschriebenes SEPA-Mandat hochgeladen. ';
						mandatHtml += 'Bitte laden Sie das unterzeichnete Mandatsformular im Bereich "SEPA Lastschrift" hoch.';
						mandatHtml += '</div>';
						mandatHtml += '</div>';
					} else if (member.mandate_granted_date) {
						mandatHtml += '<div class="profil-field">';
						mandatHtml += '<div class="profil-field-label">Status:</div>';
						mandatHtml += '<div class="profil-field-value" style="color: #28a745;"><strong>✓ Gültig</strong></div>';
						mandatHtml += '</div>';
						mandatHtml += '<div class="profil-field">';
						mandatHtml += '<div class="profil-field-label">Gültig seit:</div>';
						mandatHtml += '<div class="profil-field-value">' + escapeHtml(member.mandate_granted_date) + '</div>';
						mandatHtml += '</div>';
					} else {
						mandatHtml += '<div class="profil-field">';
						mandatHtml += '<div class="profil-field-label">Status:</div>';
						mandatHtml += '<div class="profil-field-value" style="color: #ff9800;"><strong>⏳ Nicht gültig</strong></div>';
						mandatHtml += '</div>';
						mandatHtml += '<div style="background: #fff3e0; padding: 12px; border-radius: 4px; border-left: 4px solid #ff9800; margin-top: 12px;">';
						mandatHtml += '<div style="color: #ff9800; font-size: 13px;">';
						mandatHtml += '<strong>Info:</strong> Es wurde noch kein unterschriebenes SEPA-Mandat hochgeladen und kein Erteilungsdatum erfasst. ';
						mandatHtml += 'Bitte laden Sie das unterzeichnete Mandatsformular im Bereich "SEPA Lastschrift" hoch.';
						mandatHtml += '</div>';
						mandatHtml += '</div>';
					}
					html += mandatHtml;

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
