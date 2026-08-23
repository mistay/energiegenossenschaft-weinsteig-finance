document.addEventListener('DOMContentLoaded', function() {
	loadPendingMandates();
	loadSepaData();
});

function loadPendingMandates() {
	fetch(OCA?.generateUrl?.('/apps/weinsteigfinance/api/pending-mandate-approvals') || '/index.php/apps/weinsteigfinance/api/pending-mandate-approvals',
		{ credentials: 'include' })
		.then(r => r.json())
		.then(data => {
			const section = document.getElementById('pending-section');
			const tbody = document.getElementById('pending-tbody');

			if (!data.pending || data.pending.length === 0) {
				section.style.display = 'none';
				return;
			}

			section.style.display = 'block';
			tbody.innerHTML = '';

			data.pending.forEach(item => {
				const row = document.createElement('tr');
				row.style.borderBottom = '1px solid #ffe0b3';
				row.innerHTML = `
					<td style="padding: 10px;">${escapeHtml(item.address)}</td>
					<td style="padding: 10px;">${escapeHtml(item.zahlungspflichtig)}</td>
					<td style="padding: 10px; font-family: monospace; font-size: 12px;">${escapeHtml(item.iban)}</td>
					<td style="padding: 10px; text-align: center; color: #856404;">📋 v${item.mandate_version}</td>
				`;
				tbody.appendChild(row);
			});
		})
		.catch(err => {
			document.getElementById('pending-tbody').innerHTML = '<tr><td colspan="4" style="padding: 10px; color: #dc3545;">Fehler beim Laden</td></tr>';
		});
}

function loadSepaData() {
	const url = OCA?.generateUrl?.('/apps/weinsteigfinance/api/sepa-datentraeger') || '/index.php/apps/weinsteigfinance/api/sepa-datentraeger';
	fetch(url, { credentials: 'include' })
		.then(r => r.json())
		.then(data => {
			let html = '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;">';
			html += '<button class="export-btn" onclick="exportCsv()">📥 CSV exportieren</button>';
			html += '<button class="export-btn" style="background: #28a745;" onclick="exportGeorgeCSV()">🏦 George Business (SDD)</button>';
			html += '</div>';

			if (!data.mandates || data.mandates.length === 0) {
				html += '<p style="color: #999;">Keine gültigen Mandate mit offenen Beträgen vorhanden.</p>';
			} else {
				html += `<div class="stats-box">
					<div class="stat-item">
						<div class="stat-label">Mandate</div>
						<div class="stat-value">${data.mandates.length}</div>
					</div>
					<div class="stat-item">
						<div class="stat-label">Gesamtoffene Beträge</div>
						<div class="stat-value">${data.mandates.reduce((s, m) => s + (m.open_amount || 0), 0).toFixed(2)} €</div>
					</div>
				</div>`;

				html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
				data.mandates.forEach(m => {
					const amountClass = m.open_amount > 0 ? 'amount-positive' : 'amount-zero';
					html += `<div class="sepa-card" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
						<strong>${escapeHtml(m.address)}</strong><br>
						<small style="color: #999;">${escapeHtml(m.zahlungspflichtig)}</small><br><br>
						<strong>IBAN:</strong> <code style="font-size: 11px;">${escapeHtml(m.iban)}</code><br>
						<strong>Mandat seit:</strong> ${m.mandate_granted_date || '—'}<br>
						<strong>Offener Betrag:</strong> <span class="${amountClass}">${m.open_amount.toFixed(2)} €</span>
					</div>`;
				});
				html += '</div>';
			}

			document.getElementById('sepa-container').innerHTML = html;
		})
		.catch(err => {
			document.getElementById('sepa-container').innerHTML = '<p style="color: #dc3545;">Fehler beim Laden: ' + err.message + '</p>';
		});
}

function exportCsv() {
	window.location.href = OCA?.generateUrl?.('/apps/weinsteigfinance/api/sepa-datentraeger/export') || '/index.php/apps/weinsteigfinance/api/sepa-datentraeger/export';
}

function exportGeorgeCSV() {
	window.location.href = OCA?.generateUrl?.('/apps/weinsteigfinance/api/sepa-datentraeger/export/george') || '/index.php/apps/weinsteigfinance/api/sepa-datentraeger/export/george';
}

function escapeHtml(text) {
	const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
	return String(text).replace(/[&<>"']/g, m => map[m]);
}
