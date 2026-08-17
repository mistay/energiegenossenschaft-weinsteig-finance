document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('vorschreibungen-container');
	let currentData = {};

	function load() {
		// Get user groups and vorschreibungen in parallel
		Promise.all([
			fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-groups')).then(r => r.json()),
			fetch(OC.generateUrl('/apps/weinsteigfinance/api/vorschreibungen')).then(r => r.json())
		])
			.then(([userInfo, data]) => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				currentData = data;
				const months = data.months || [];
				const members = data.members || [];
				const isObperson = data.isObperson || false;
				const cronStatus = data.cronStatus || {};
				const userGroups = userInfo.groups || [];

				if (members.length === 0) {
					container.innerHTML = '<p>Keine Häuser gefunden.</p>';
					return;
				}

				// Generate-Button für obpersonen
				let html = '';

				// Info-Box für berechtigte Gruppen (obpersonen, kassier:innen)
				const authorizedGroups = ['obpersonen', 'kassier:innen'];
				const hasAuthorization = userGroups.some(g => authorizedGroups.includes(g));
				if (hasAuthorization && userGroups.length > 0) {
					const groupIcons = {
						'obpersonen': '👑',
						'kassier:innen': '💰'
					};
					const visibleGroups = userGroups
						.filter(g => authorizedGroups.includes(g))
						.map(g => (groupIcons[g] || '') + ' ' + g)
						.join(', ');

					if (visibleGroups) {
						html += '<div style="background: #e3f2fd; border-left: 4px solid #0082c9; padding: 16px; border-radius: 4px; margin-bottom: 20px; color: #0082c9;">';
						html += '<strong>ℹ️ Hinweis:</strong> Es werden alle Vorschreibungen angezeigt, weil dieses Nutzerkonto in der Gruppe ' + escapeHtml(visibleGroups) + ' geführt wird.';
						html += '</div>';
					}
				}

				if (isObperson && months.length > 0) {
					const latestMonth = months[months.length - 1];
					html += '<div style="margin-bottom: 20px;">';
					html += '<label>Monat generieren: ';
					html += '<select id="month-select" style="padding: 5px; margin-right: 10px;">';
					months.forEach((m, idx) => {
						const selected = idx === months.length - 1 ? ' selected' : '';
						html += '<option value="' + m.year + '-' + String(m.month).padStart(2, '0') + '"' + selected + '>' + escapeHtml(m.label) + '</option>';
					});
					html += '</select>';
					html += '<button id="generate-btn" style="padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: bold;">✨ Alle generieren</button>';
					html += '</label>';
					html += '<div id="generate-status" style="margin-top: 10px;"></div>';
					html += '</div>';

					// Cron-Status anzeigen
					html += '<div style="background: #e8f4f8; border-left: 4px solid #0082c9; padding: 12px; border-radius: 3px; margin-bottom: 20px; font-size: 13px; line-height: 1.6;">';
					if (cronStatus.cronLastRun) {
						html += '✓ <strong>Cron lief:</strong> ' + escapeHtml(cronStatus.cronLastRun) + '<br>';
					} else {
						html += '⏳ <strong>Cron-Status:</strong> Noch nicht gelaufen<br>';
					}
					if (cronStatus.nextRunExpected) {
						html += '📅 <strong>Nächste Generierung (1. des Monats):</strong> ' + escapeHtml(cronStatus.nextRunExpected);
					}
					html += '</div><hr>';
				}

				// Vorschreibungen nach Häusern gruppiert, Monate zeilenweise
				html += '<div id="vorschreibungen-cards" style="margin-top: 20px;">';

				members.forEach(member => {
					html += '<div class="member-card" style="margin-bottom: 30px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';

					// Haus-Überschrift
					html += '<div style="background: #0082c9; color: white; padding: 12px 16px; font-weight: bold; font-size: 15px;">' + escapeHtml(member.address) + '</div>';

					// Vorschreibungen als Liste (neuste oben)
					html += '<div style="padding: 12px 16px;">';
					const monthsReverse = [...months].reverse();
					let hasAny = false;
					monthsReverse.forEach((m, idx) => {
						const monthStr = m.year + '-' + String(m.month).padStart(2, '0');
						const vorschreibung = member.vorschreibungen?.[monthStr];

						const rowStyle = 'display: flex; justify-content: space-between; align-items: center; padding: 10px 0;' + (idx > 0 ? ' border-top: 1px solid #eee;' : '');
						html += '<div style="' + rowStyle + '">';
						html += '<span style="font-size: 13px; color: #555;">' + escapeHtml(m.label) + '</span>';

						if (vorschreibung?.exists) {
							hasAny = true;
							const url = OC.generateUrl('/apps/weinsteigfinance/api/vorschreibung/' + member.id + '/' + monthStr);
							html += '<a href="' + url + '" target="_blank" class="download-btn" title="Generiert: ' + escapeHtml(vorschreibung.date) + '" style="padding: 6px 10px; font-size: 12px;">📥 ' + escapeHtml(vorschreibung.date) + '</a>';
						} else {
							html += '<span style="color: #ccc; font-size: 13px;">—</span>';
						}
						html += '</div>';
					});
					html += '</div>';

					html += '</div>';
				});

				html += '</div>';
				container.innerHTML = html;

				// Generate-Button Handler
				if (isObperson) {
					const generateBtn = document.getElementById('generate-btn');
					const statusDiv = document.getElementById('generate-status');
					const monthSelect = document.getElementById('month-select');

					if (generateBtn) {
						generateBtn.addEventListener('click', function() {
							const month = monthSelect.value;
							const [year, m] = month.split('-');
							generateBtn.disabled = true;
							statusDiv.innerHTML = '<p style="color: #0082c9;">⏳ Generiere ' + months.length + ' Vorschreibungen...</p>';

							fetch(OC.generateUrl('/apps/weinsteigfinance/api/vorschreibungen/' + year + '/' + m + '/generate'), {
								method: 'POST',
								headers: {'Content-Type': 'application/json'}
							})
								.then(r => r.json())
								.then(data => {
									if (data.success) {
										statusDiv.innerHTML = '<p style="color: #28a745;">✓ ' + escapeHtml(data.message) + '</p>';
										setTimeout(() => load(), 1000);
									} else {
										statusDiv.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error || 'Unbekannter Fehler') + '</p>';
										generateBtn.disabled = false;
									}
								})
								.catch(err => {
									statusDiv.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(err.message) + '</p>';
									generateBtn.disabled = false;
								});
						});
					}
				}
			})
			.catch(err => {
				container.innerHTML = '<p style="color: red;">Fehler beim Laden: ' + escapeHtml(err.message) + '</p>';
			});
	}

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	load();
});
