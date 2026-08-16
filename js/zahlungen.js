let unmatched = [];
let members = [];

function load() {
	const container = document.getElementById('zahlungen-container');
	if (!container) {
		console.error('zahlungen-container not found');
		return;
	}

	fetch(OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/unmatched'))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				unmatched = data.unmatched || [];
				const matched = data.matched || [];
				const stats = data.stats || {};
				members = data.members || [];

				let html = '';

				// CSV Import Section
				html += '<div id="zahlungen-import">';
				html += '<h3>CSV-Import</h3>';
				html += '<div style="margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-radius: 3px;">';
				html += '<label style="cursor: pointer; display: inline-block; padding: 10px 15px; background: #0082c9; color: white; border-radius: 3px; font-weight: bold;">';
				html += '📁 CSV-Datei auswählen';
				html += '<input type="file" id="csv-file" accept=".csv" style="display: none;">';
				html += '</label>';
				html += '<span style="margin-left: 10px; font-size: 12px; color: #666;">oder Text unten einfügen</span>';
				html += '</div>';
				html += '<textarea id="csv-input" placeholder="Oder: CSV-Inhalt hierher einfügen (mit Semikolon-Trennzeichen)..."></textarea>';
				html += '<div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">';
				html += '<button class="import-btn" id="import-btn">📤 Importieren</button>';
				if (stats.pending > 0) {
					html += '<button class="import-btn" id="auto-match-btn" style="background: #ff9800;">🔍 Auto-Match</button>';
				}
				html += '</div>';
				html += '<div id="import-status" style="margin-top: 10px;"></div>';
				html += '</div>';

				// Statistik + Letzter Import
				if (stats.total > 0) {
					html += '<div style="background: #f0f8ff; padding: 10px; border-radius: 3px; margin-bottom: 20px; font-size: 12px;">';
					html += '📊 Total: <strong>' + stats.total + '</strong> | ✓ Zugeordnet: <strong style="color: #28a745;">' + stats.matched + '</strong> | ⚠️ Ausstehend: <strong style="color: #ff9800;">' + stats.pending + '</strong>';
					if (data.lastImport) {
						html += '<br>📅 Letzter Import: ' + escapeHtml(data.lastImport);
					}
					html += '</div>';
				}

				// Unmatched Zahlungen - Card Layout
				if (unmatched.length > 0) {
					html += '<h3 style="color: #ff9800; margin-top: 30px;">⚠️ Unzugeordnete Zahlungen (' + unmatched.length + ')</h3>';

					unmatched.forEach(z => {
						html += '<div class="zahlung-card" style="background: #fffbea; border: 1px solid #ffd580; border-radius: 4px; padding: 14px; margin-bottom: 12px;">';

						// Header: Datum + Betrag
						html += '<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; gap: 8px;">';
						html += '<span style="font-weight: 600; color: #333;">' + escapeHtml(z.valutadatum) + '</span>';
						html += '<span style="font-size: 16px; font-weight: bold; color: #ff9800;">' + parseFloat(z.betrag).toFixed(2) + ' ' + escapeHtml(z.waehrung) + '</span>';
						html += '</div>';

						// Partner + Status
						html += '<div style="margin-bottom: 10px; padding: 8px; background: white; border-radius: 3px; border-left: 3px solid #ff9800;">';
						html += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 4px;">';
						html += '<strong style="color: #333; font-size: 13px;">Von: ' + escapeHtml(z.partnername) + '</strong>';
						html += '<span class="match-status-' + escapeHtml(z.status) + '">' + escapeHtml(z.match_type) + '</span>';
						html += '</div>';
						html += '<div style="font-size: 12px; color: #666; word-break: break-word;">' + escapeHtml(z.verwendungszweck) + '</div>';
						html += '</div>';

						// Action: Dropdown + Button
						html += '<div style="display: flex; gap: 6px; flex-wrap: wrap;">';
						html += '<select class="assign-select" id="select-' + z.id + '" style="flex: 1; min-width: 150px; padding: 6px; border: 1px solid #ddd; border-radius: 3px;">';
						html += '<option value="">📍 Wähle Haus...</option>';
						members.forEach(m => {
							html += '<option value="' + m.id + '">' + escapeHtml(m.address) + '</option>';
						});
						html += '</select>';
						html += '<button type="button" class="assign-btn" data-id="' + z.id + '" style="padding: 6px 12px;">✓ Zuordnen</button>';
						html += '</div>';

						html += '</div>';
					});
				}

				// Matched Zahlungen - Card Layout
				if (matched.length > 0) {
					html += '<h3 style="margin-top: 30px; color: #28a745;">✓ Zugeordnete Zahlungen (' + matched.length + ')</h3>';

					matched.forEach(z => {
						const currentMember = members.find(m => m.id == z.member_id);
						html += '<div class="zahlung-card" style="background: #f0fdf4; border: 1px solid #a6d96a; border-radius: 4px; padding: 14px; margin-bottom: 12px;">';

						// Header: Datum + Betrag
						html += '<div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; gap: 8px;">';
						html += '<span style="font-weight: 600; color: #333;">' + escapeHtml(z.valutadatum) + '</span>';
						html += '<span style="font-size: 16px; font-weight: bold; color: #28a745;">' + parseFloat(z.betrag).toFixed(2) + ' ' + escapeHtml(z.waehrung) + '</span>';
						html += '</div>';

						// Partner + Status
						html += '<div style="margin-bottom: 10px; padding: 8px; background: white; border-radius: 3px; border-left: 3px solid #28a745;">';
						html += '<div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">';
						html += '<strong style="color: #333; font-size: 13px;">Von: ' + escapeHtml(z.partnername) + '</strong>';
						html += '<span class="match-status-' + escapeHtml(z.status) + '">' + escapeHtml(z.match_type) + ' (' + z.match_confidence + '%)</span>';
						html += '</div>';
						html += '<div style="font-size: 12px; color: #666; word-break: break-word; margin-bottom: 6px;">' + escapeHtml(z.verwendungszweck) + '</div>';
						html += '<div style="font-size: 12px; font-weight: 600; color: #155724;">📍 ' + (currentMember ? escapeHtml(currentMember.address) : '—') + '</div>';
						html += '</div>';

						// Action: Dropdown + Buttons
						html += '<div style="display: flex; gap: 6px; flex-wrap: wrap;">';
						html += '<select class="assign-select" id="select-' + z.id + '" style="flex: 1; min-width: 150px; padding: 6px; border: 1px solid #ddd; border-radius: 3px;">';
						html += '<option value="">📍 Haus ändern...</option>';
						members.forEach(m => {
							const selected = m.id == z.member_id ? ' selected' : '';
							html += '<option value="' + m.id + '"' + selected + '>' + escapeHtml(m.address) + '</option>';
						});
						html += '</select>';
						html += '<button type="button" class="assign-btn" data-id="' + z.id + '" style="padding: 6px 12px;">✓ Ändern</button>';
						html += '<button type="button" class="unassign-btn" data-id="' + z.id + '" style="padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">↩️ Zurück</button>';
						html += '</div>';

						html += '</div>';
					});
				}

				console.log('Setting HTML, length:', html.length);
				container.innerHTML = html;
				console.log('HTML set. Container content:', container.innerHTML.length);

				// CSV File Upload Handler
				const csvFileInput = document.getElementById('csv-file');
				if (csvFileInput) {
					csvFileInput.addEventListener('change', function(e) {
						const file = e.target.files[0];
						if (!file) return;

						const reader = new FileReader();
						reader.onload = function(event) {
							document.getElementById('csv-input').value = event.target.result;
							importStatus.innerHTML = '<p style="color: #0082c9;">✓ CSV-Datei geladen: ' + escapeHtml(file.name) + '</p>';
						};
						reader.onerror = function() {
							importStatus.innerHTML = '<p style="color: red;">Fehler beim Lesen der Datei</p>';
						};
						reader.readAsText(file);
					});
				}

				// Import Button Handler
				const importBtn = document.getElementById('import-btn');
				const autoMatchBtn = document.getElementById('auto-match-btn');
				const csvInput = document.getElementById('csv-input');
				const importStatus = document.getElementById('import-status');

				if (importBtn) {
					importBtn.addEventListener('click', function() {
						const csv = csvInput.value.trim();
						if (!csv) {
							importStatus.innerHTML = '<p style="color: red;">CSV-Feld ist leer</p>';
							return;
						}

						importBtn.disabled = true;
						importStatus.innerHTML = '<p style="color: #0082c9;">⏳ Importiere...</p>';

						fetch(OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/import'), {
							method: 'POST',
							headers: {'Content-Type': 'application/json'},
							body: JSON.stringify({csv: csv})
						})
							.then(r => r.json())
							.then(data => {
								if (data.success) {
									let msg = '✓ ' + data.count + ' Zahlungen importiert';
									if (data.duplicate_count > 0) {
										msg += ' | ⚠️ ' + data.duplicate_count + ' Duplikate ignoriert';
									}
									importStatus.innerHTML = '<p style="color: #28a745;">' + msg + '</p>';
									if (data.duplicates && data.duplicates.length > 0) {
										importStatus.innerHTML += '<p style="color: #ff9800; font-size: 11px; margin-top: 5px;">Ignorierte: ';
										data.duplicates.forEach(d => {
											importStatus.innerHTML += '<br>' + escapeHtml(d.date) + ' | ' + escapeHtml(d.partner) + ' | ' + d.amount.toFixed(2);
										});
										importStatus.innerHTML += '</p>';
										importStatus.innerHTML += '<button id="reload-btn" style="margin-top: 10px; padding: 6px 12px; background: #0082c9; color: white; border: none; border-radius: 3px; cursor: pointer;">↻ Aktualisieren</button>';
										document.getElementById('reload-btn').addEventListener('click', () => load());
									} else {
										csvInput.value = '';
										setTimeout(() => load(), 1500);
									}
								} else {
									importStatus.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error || 'Unbekannter Fehler') + '</p>';
									importBtn.disabled = false;
								}
							})
							.catch(err => {
								importStatus.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(err.message) + '</p>';
								importBtn.disabled = false;
							});
					});
				}

				// Auto-Match Button Handler
				if (autoMatchBtn) {
					autoMatchBtn.addEventListener('click', function() {
						if (!confirm('Versuche alle ' + stats.pending + ' unzugeordneten Zahlungen erneut zu matchen?')) {
							return;
						}

						autoMatchBtn.disabled = true;
						importStatus.innerHTML = '<p style="color: #0082c9;">⏳ Auto-Match läuft...</p>';

						fetch(OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/auto-match'), {
							method: 'POST',
							headers: {'Content-Type': 'application/json'}
						})
							.then(r => r.json())
							.then(data => {
								if (data.success) {
									importStatus.innerHTML = '<p style="color: #28a745;">✓ ' + data.message + '</p>';
									setTimeout(() => load(), 1000);
								} else {
									importStatus.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error || 'Unbekannter Fehler') + '</p>';
									autoMatchBtn.disabled = false;
								}
							})
							.catch(err => {
								importStatus.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(err.message) + '</p>';
								autoMatchBtn.disabled = false;
							});
					});
				}

				// Assign Button Handlers (für beide Tabellen)
				attachButtonHandlers();
			})
			.catch(err => {
				container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(err.message) + '</p>';
			});
	}

function escapeHtml(text) {
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

function attachButtonHandlers() {
	console.log('Attaching button handlers...');

	// Assign Buttons (Zuordnen/Ändern)
	document.querySelectorAll('.assign-btn').forEach(btn => {
		btn.onclick = function(e) {
			e.preventDefault();
			console.log('Assign clicked:', this.dataset.id);

			const zahlungId = this.dataset.id;
			const select = document.getElementById('select-' + zahlungId);
			const memberId = select ? select.value : null;

			if (!memberId) {
				alert('Bitte wähle ein Haus');
				return false;
			}

			const url = OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/' + zahlungId + '/assign/' + memberId);
			fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json'}})
				.then(r => r.json())
				.then(data => {
					if (data.success) load();
					else alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
				})
				.catch(err => alert('Fehler: ' + err.message));
			return false;
		};
	});

	// Unassign Buttons (Zurückstellen)
	document.querySelectorAll('.unassign-btn').forEach(btn => {
		btn.onclick = function(e) {
			e.preventDefault();
			const zahlungId = this.dataset.id;

			if (!confirm('Zahlung wirklich auf "unzugeordnet" zurückstellen?')) {
				return false;
			}

			const url = OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/' + zahlungId + '/unassign');
			fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json'}})
				.then(r => r.json())
				.then(data => {
					if (data.success) load();
					else alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
				})
				.catch(err => alert('Fehler: ' + err.message));
			return false;
		};
	});

	console.log('Attached handlers to', document.querySelectorAll('.assign-btn').length, 'assign +', document.querySelectorAll('.unassign-btn').length, 'unassign buttons');
}

// Starte wenn DOM fertig ist
document.addEventListener('DOMContentLoaded', load);
