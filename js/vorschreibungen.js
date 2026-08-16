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

				// Tabelle aufbauen: Zeilen = Monate (neuste oben), Spalten = Häuser
				html += '<div style="overflow-x: auto; margin-top: 20px;">';
				html += '<table id="vorschreibungen-table" style="min-width: 100%; border-collapse: collapse;">';

				// Header: Häuser
				html += '<thead><tr style="background: #f5f5f5;">';
				html += '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; position: sticky; left: 0; background: #f5f5f5; font-weight: bold;">Monat</th>';
				members.forEach(member => {
					html += '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; white-space: nowrap; font-weight: bold;">' + escapeHtml(member.address) + '</th>';
				});
				html += '</tr></thead>';

				// Body: Monate (neuste oben, also reverse)
				html += '<tbody>';
				const monthsReverse = [...months].reverse();
				monthsReverse.forEach(m => {
					const monthStr = m.year + '-' + String(m.month).padStart(2, '0');
					html += '<tr style="border-bottom: 1px solid #ddd;">';
					html += '<td style="padding: 10px; border: 1px solid #ddd; position: sticky; left: 0; background: white; font-weight: 500;">' + escapeHtml(m.label) + '</td>';

					members.forEach(member => {
						const vorschreibung = member.vorschreibungen?.[monthStr];
						if (vorschreibung?.exists) {
							const url = OC.generateUrl('/apps/weinsteigfinance/api/vorschreibung/' + member.id + '/' + monthStr);
							html += '<td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><a href="' + url + '" target="_blank" class="download-btn" title="Generiert: ' + escapeHtml(vorschreibung.date) + '" style="display: inline-block; padding: 6px 8px; background: white; color: #0082c9; border: 1px solid #0082c9; border-radius: 3px; text-decoration: none; font-size: 12px; transition: all 0.2s;">📥 ' + escapeHtml(vorschreibung.date) + '</a></td>';
						} else {
							html += '<td style="padding: 10px; border: 1px solid #ddd; text-align: center; color: #ccc;">—</td>';
						}
					});
					html += '</tr>';
				});
				html += '</tbody></table></div>';
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
