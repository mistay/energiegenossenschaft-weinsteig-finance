let allZahlungen = [];
let allMonths = [];

function load() {
	const container = document.getElementById('zahlungen-list');
	if (!container) {
		console.error('zahlungen-list not found');
		return;
	}

	fetch(OC.generateUrl('/apps/weinsteigfinance/api/zahlungen-uebersicht'))
		.then(r => r.json())
		.then(data => {
			if (data.error) {
				container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
				return;
			}

			allZahlungen = data.zahlungen || [];
			const stats = data.stats || {};

			// Extrahiere eindeutige Monate
			const monthSet = new Set();
			allZahlungen.forEach(z => {
				if (z.valutadatum) {
					const month = z.valutadatum.substring(0, 7); // YYYY-MM
					monthSet.add(month);
				}
			});
			allMonths = Array.from(monthSet).sort().reverse();

			// Fülle Month Dropdown
			const monthFilter = document.getElementById('month-filter');
			monthFilter.innerHTML = '<option value="">-- Alle Monate --</option>';
			allMonths.forEach(month => {
				const option = document.createElement('option');
				option.value = month;
				option.textContent = formatMonth(month);
				monthFilter.appendChild(option);
			});

			// Zeige Statistik
			const statsBox = document.getElementById('stats-box');
			document.getElementById('stat-gesamt').textContent = stats.gesamt ? stats.gesamt.toFixed(2) : '0.00';
			document.getElementById('stat-zugeordnet').textContent = stats.zugeordnet ? stats.zugeordnet.toFixed(2) : '0.00';
			document.getElementById('stat-unzugeordnet').textContent = stats.unzugeordnet ? stats.unzugeordnet.toFixed(2) : '0.00';
			statsBox.style.display = 'block';

			// Rendere Tabelle
			renderTable('');
		})
		.catch(err => {
			container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(err.message) + '</p>';
		});
}

function renderTable(selectedMonth) {
	const container = document.getElementById('zahlungen-list');

	let filtered = allZahlungen;
	if (selectedMonth) {
		filtered = allZahlungen.filter(z => z.valutadatum && z.valutadatum.startsWith(selectedMonth));
	}

	if (filtered.length === 0) {
		container.innerHTML = '<p style="color: #999;">Keine Zahlungen gefunden.</p>';
		return;
	}

	let html = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
	html += '<thead><tr style="background: #f5f5f5;">';
	html += '<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Datum</th>';
	html += '<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Partner</th>';
	html += '<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Zweck</th>';
	html += '<th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Betrag</th>';
	html += '<th style="border: 1px solid #ddd; padding: 10px; text-align: center;">Status</th>';
	html += '</tr></thead><tbody>';

	filtered.forEach(z => {
		const statusClass = z.status === 'matched' ? 'color: #28a745;' : 'color: #ff9800;';
		const statusText = z.status === 'matched' ? '✓ Zugeordnet' : '⚠️ Unzugeordnet';

		html += '<tr style="border-bottom: 1px solid #eee;">';
		html += '<td style="border: 1px solid #ddd; padding: 10px;">' + escapeHtml(z.valutadatum) + '</td>';
		html += '<td style="border: 1px solid #ddd; padding: 10px;">' + escapeHtml(z.partnername) + '</td>';
		html += '<td style="border: 1px solid #ddd; padding: 10px; font-size: 12px;">' + escapeHtml(z.verwendungszweck) + '</td>';
		html += '<td style="border: 1px solid #ddd; padding: 10px; text-align: right;">' + parseFloat(z.betrag).toFixed(2) + ' ' + escapeHtml(z.waehrung) + '</td>';
		html += '<td style="border: 1px solid #ddd; padding: 10px; text-align: center; ' + statusClass + '">' + statusText + '</td>';
		html += '</tr>';
	});

	html += '</tbody></table>';
	container.innerHTML = html;
}

function escapeHtml(text) {
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

function formatMonth(monthStr) {
	const [year, month] = monthStr.split('-');
	const months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
	return months[parseInt(month) - 1] + ' ' + year;
}

// Starte wenn DOM fertig ist
document.addEventListener('DOMContentLoaded', function() {
	load();

	const monthFilter = document.getElementById('month-filter');
	if (monthFilter) {
		monthFilter.addEventListener('change', function() {
			renderTable(this.value);
		});
	}
});
