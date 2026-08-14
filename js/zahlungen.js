document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('zahlungen-container');
	let unmatched = [];
	let members = [];

	function load() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/unmatched'))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				unmatched = data.unmatched || [];
				members = data.members || [];

				let html = '';

				// CSV Import Section (nur obpersonen)
				html += '<div id="zahlungen-import">';
				html += '<h3>CSV-Import</h3>';
				html += '<textarea id="csv-input" placeholder="Füge hier den CSV-Inhalt ein (mit Semikolon-Trennzeichen)..."></textarea>';
				html += '<button class="import-btn" id="import-btn">📤 Zahlungen importieren</button>';
				html += '<div id="import-status" style="margin-top: 10px;"></div>';
				html += '</div>';

				// Unmatched Zahlungen
				if (unmatched.length > 0) {
					html += '<h3>Unzugeordnete Zahlungen (' + unmatched.length + ')</h3>';
					html += '<table id="zahlungen-table"><thead><tr>';
					html += '<th>Datum</th><th>Partner</th><th>Zweck</th><th>Betrag</th><th>Match</th><th>Aktion</th>';
					html += '</tr></thead><tbody>';

					unmatched.forEach(z => {
						html += '<tr>';
						html += '<td>' + escapeHtml(z.valutadatum) + '</td>';
						html += '<td>' + escapeHtml(z.partnername) + '</td>';
						html += '<td style="font-size: 11px;">' + escapeHtml(z.verwendungszweck) + '</td>';
						html += '<td style="text-align: right;">' + parseFloat(z.betrag).toFixed(2) + ' ' + escapeHtml(z.waehrung) + '</td>';
						html += '<td><span class="match-status-' + escapeHtml(z.status) + '">' + escapeHtml(z.match_type) + '</span></td>';
						html += '<td>';
						html += '<select class="assign-select" id="select-' + z.id + '">';
						html += '<option value="">-- Wähle Haus --</option>';
						members.forEach(m => {
							html += '<option value="' + m.id + '">' + escapeHtml(m.address) + '</option>';
						});
						html += '</select>';
						html += '<button class="assign-btn" data-id="' + z.id + '">✓ Zuordnen</button>';
						html += '</td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
				} else {
					html += '<p style="color: #28a745;">✓ Alle Zahlungen zugeordnet!</p>';
				}

				container.innerHTML = html;

				// Import Button Handler
				const importBtn = document.getElementById('import-btn');
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
									importStatus.innerHTML = '<p style="color: #28a745;">✓ ' + data.count + ' Zahlungen importiert</p>';
									csvInput.value = '';
									setTimeout(() => load(), 1000);
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

				// Assign Button Handlers
				document.querySelectorAll('.assign-btn').forEach(btn => {
					btn.addEventListener('click', function() {
						const zahlungId = this.dataset.id;
						const select = document.getElementById('select-' + zahlungId);
						const memberId = select.value;

						if (!memberId) {
							alert('Bitte wähle ein Haus');
							return;
						}

						fetch(OC.generateUrl('/apps/weinsteigfinance/api/zahlungen/' + zahlungId + '/assign/' + memberId), {
							method: 'POST',
							headers: {'Content-Type': 'application/json'}
						})
							.then(r => r.json())
							.then(data => {
								if (data.success) {
									load();
								} else {
									alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
								}
							});
					});
				});
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

	load();
});
