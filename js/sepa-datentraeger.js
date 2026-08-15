document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('sepa-container');

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function load() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/sepa-datentraeger'))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				const mandates = data.mandates || [];

				let html = '';

				// Info-Box
				html += '<div class="info-box">';
				html += '💡 <strong>SEPA Core Datenträger</strong> – Übersicht aller gültigen SEPA-Mandate und offenen Beträge in Kundenkonten';
				html += '</div>';

				// Export Button
				html += '<button id="export-btn" class="export-btn">📥 Als CSV exportieren</button>';

				// Statistiken
				if (mandates.length > 0) {
					let totalOpen = 0;
					let openCount = 0;
					mandates.forEach(m => {
						totalOpen += m.open_amount;
						if (m.open_amount > 0) openCount++;
					});

					html += '<div class="stats-box">';
					html += '<div class="stat-item">';
					html += '<div class="stat-label">Gültige Mandate</div>';
					html += '<div class="stat-value">' + mandates.length + '</div>';
					html += '</div>';
					html += '<div class="stat-item">';
					html += '<div class="stat-label">Mit offenen Beträgen</div>';
					html += '<div class="stat-value">' + openCount + '</div>';
					html += '</div>';
					html += '<div class="stat-item">';
					html += '<div class="stat-label">Total offene Beträge</div>';
					html += '<div class="stat-value">' + totalOpen.toFixed(2) + ' €</div>';
					html += '</div>';
					html += '</div>';
				}

				// Tabelle
				html += '<table id="sepa-table"><thead><tr>' +
					'<th>Haus</th><th>Kontoinhaber</th><th>IBAN</th><th>Mandat gültig seit</th><th>Offene Beträge</th>' +
					'</tr></thead><tbody>';

				if (mandates.length === 0) {
					html += '<tr><td colspan="5" style="text-align: center; color: #999;">Keine gültigen Mandate gefunden</td></tr>';
				} else {
					mandates.forEach(m => {
						const amountClass = m.open_amount > 0 ? 'amount-negative' : (m.open_amount < 0 ? 'amount-positive' : 'amount-zero');
						const amountText = m.open_amount.toFixed(2) + ' €';
						html += '<tr>';
						html += '<td><strong>' + escapeHtml(m.address) + '</strong></td>';
						html += '<td>' + escapeHtml(m.zahlungspflichtig) + '</td>';
						html += '<td><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-size: 11px;">' + escapeHtml(m.iban) + '</code></td>';
						html += '<td>' + escapeHtml(m.mandate_granted_date) + '</td>';
						html += '<td class="' + amountClass + '">' + amountText + '</td>';
						html += '</tr>';
					});
				}

				html += '</tbody></table>';
				container.innerHTML = html;

				// Export Button Handler
				document.getElementById('export-btn').addEventListener('click', function() {
					window.location.href = OC.generateUrl('/apps/weinsteigfinance/api/sepa-datentraeger/export');
				});
			})
			.catch(err => {
				container.innerHTML = '<p style="color: red;">Fehler beim Laden: ' + escapeHtml(err.message) + '</p>';
			});
	}

	load();
});
