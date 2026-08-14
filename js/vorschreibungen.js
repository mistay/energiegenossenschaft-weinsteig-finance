document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('vorschreibungen-container');

	function load() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/vorschreibungen'))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				const months = data.months || [];
				const members = data.members || [];
				const isObperson = data.isObperson || false;

				if (members.length === 0) {
					container.innerHTML = '<p>Keine Häuser gefunden.</p>';
					return;
				}

				// Tabelle aufbauen: Spalten = Monate, Zeilen = Häuser
				let html = '<table id="vorschreibungen-table"><thead><tr><th>Haus</th>';
				months.forEach(m => {
					html += '<th>' + escapeHtml(m.label) + '</th>';
				});
				html += '</tr></thead><tbody>';

				members.forEach(member => {
					html += '<tr><td><strong>' + escapeHtml(member.address) + '</strong></td>';
					months.forEach(m => {
						const monthStr = m.year + '-' + String(m.month).padStart(2, '0');
						const url = OC.generateUrl('/apps/weinsteigfinance/api/vorschreibung/' + member.id + '/' + monthStr);
						html += '<td><a href="' + url + '" target="_blank" class="download-btn">📥 PDF</a></td>';
					});
					html += '</tr>';
				});

				html += '</tbody></table>';
				container.innerHTML = html;
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
