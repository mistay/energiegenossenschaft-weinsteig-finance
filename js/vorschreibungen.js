document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('vorschreibungen-container');
	let currentData = {};

	function load() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/vorschreibungen'))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				currentData = data;
				const months = data.months || [];
				const members = data.members || [];
				const isObperson = data.isObperson || false;
				const cronStatus = data.cronStatus || {};

				if (members.length === 0) {
					container.innerHTML = '<p>Keine Häuser gefunden.</p>';
					return;
				}

				// Generate-Button für obpersonen
				let html = '';
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
					html += '<div style="background: #f5f5f5; padding: 10px; border-radius: 3px; margin-bottom: 20px; font-size: 12px;">';
					if (cronStatus.lastRun) {
						html += '✓ <strong>Letzter Cron-Lauf:</strong> ' + escapeHtml(cronStatus.lastRun) + '<br>';
					} else {
						html += '⏳ <strong>Cron-Lauf:</strong> Noch nicht gelaufen<br>';
					}
					if (cronStatus.lastGenerated) {
						html += '✓ <strong>Zuletzt Vorschreibungen generiert:</strong> ' + escapeHtml(cronStatus.lastGenerated);
					}
					html += '</div><hr>';
				}

				// Tabelle aufbauen: Spalten = Monate, Zeilen = Häuser
				html += '<table id="vorschreibungen-table"><thead><tr><th>Haus</th>';
				months.forEach(m => {
					html += '<th>' + escapeHtml(m.label) + '</th>';
				});
				html += '</tr></thead><tbody>';

				members.forEach(member => {
					html += '<tr><td><strong>' + escapeHtml(member.address) + '</strong></td>';
					months.forEach(m => {
						const monthStr = m.year + '-' + String(m.month).padStart(2, '0');
						const vorschreibung = member.vorschreibungen?.[monthStr];
						if (vorschreibung?.exists) {
							const url = OC.generateUrl('/apps/weinsteigfinance/api/vorschreibung/' + member.id + '/' + monthStr);
							html += '<td><a href="' + url + '" target="_blank" class="download-btn" title="Generiert: ' + escapeHtml(vorschreibung.date) + '">📥<br><span style="font-size: 10px; color: rgba(255,255,255,0.9);">(' + escapeHtml(vorschreibung.date) + ')</span></a></td>';
						} else {
							html += '<td style="color: #999;">—</td>';
						}
					});
					html += '</tr>';
				});

				html += '</tbody></table>';
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
