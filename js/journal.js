function load() {
	const loadingEl = document.getElementById('loading');
	const errorEl = document.getElementById('error-message');
	const statsBox = document.getElementById('stats-box');
	const vorschreibungenSection = document.getElementById('vorschreibungen-section');
	const zahlungenSection = document.getElementById('zahlungen-section');

	loadingEl.style.display = 'block';
	errorEl.style.display = 'none';

	// Prüfe URL-Parameter für fremdes Journal (nur Admins)
	const urlParams = new URLSearchParams(window.location.search);
	const memberId = urlParams.get('member');

	if (memberId) {
		// Admin möchte fremdes Journal sehen
		loadJournalForMember(memberId);
	} else {
		// Lade eigenes Mitglied
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-member'))
			.then(r => r.json())
			.then(memberData => {
				if (!memberData || memberData.error) {
					throw new Error(memberData?.message || 'Kein Mitglied zugewiesen');
				}

				loadJournalForMember(memberData.id);
			})
			.catch(err => {
				loadingEl.style.display = 'none';
				errorEl.style.display = 'block';
				errorEl.innerHTML = '❌ ' + escapeHtml(err.message);
			});
	}
}

function loadJournalForMember(memberId) {
	const loadingEl = document.getElementById('loading');
	const errorEl = document.getElementById('error-message');

	// Lade Journal für spezifisches Mitglied
	fetch(OC.generateUrl('/apps/weinsteigfinance/api/member/' + memberId + '/journal'))
		.then(r => r.json())
		.then(journalData => {
			if (journalData.error) {
				throw new Error(journalData.message || journalData.error);
			}

			loadingEl.style.display = 'none';
			renderJournal(journalData);
		})
		.catch(err => {
			loadingEl.style.display = 'none';
			errorEl.style.display = 'block';
			errorEl.innerHTML = '❌ ' + escapeHtml(err.message);
		});
}

function renderJournal(data) {
	const statsBox = document.getElementById('stats-box');
	const statusMessage = document.getElementById('status-message');
	const accountInfoBox = document.getElementById('account-info-box');
	const accountInfoSubject = document.getElementById('account-info-subject');
	const vorschreibungenSection = document.getElementById('vorschreibungen-section');
	const zahlungenSection = document.getElementById('zahlungen-section');

	const stats = data.stats || {};
	const member = data.member || {};
	const vorschreibungen = data.vorschreibungen || [];
	const zahlungen = data.zahlungen || [];

	// Zeige Statistik
	document.getElementById('stat-saldo').textContent = formatAmount(stats.saldo);
	document.getElementById('stat-open').textContent = formatAmount(stats.openVorschreibungen);
	document.getElementById('stat-zahlungen').textContent = formatAmount(stats.totalZahlungen);
	statsBox.style.display = 'block';

	// Zeige Status-Nachricht und Kontoinformationen
	const saldo = parseFloat(stats.saldo) || 0;
	statusMessage.style.display = 'block';
	accountInfoBox.style.display = 'block';

	// Setze Betreff (Adresse)
	if (member.address) {
		accountInfoSubject.textContent = escapeHtml(member.address);
	}

	if (saldo < -0.01) {
		// Schuld
		const schuld = Math.abs(saldo);
		statusMessage.style.background = '#fff3cd';
		statusMessage.style.borderLeftColor = '#ffc107';
		statusMessage.style.color = '#856404';
		statusMessage.innerHTML = `⚠️ <strong>Zahlungsaufforderung:</strong> Ihr Konto ist mit <strong>${schuld.toFixed(2).replace('.', ',')} €</strong> im Rückstand. Bitte um Ausgleich durch Zahlung aufs Konto der Energiegenossenschaft Weinsteig.`;
	} else if (saldo > 0.01) {
		// Guthaben
		statusMessage.style.background = '#d4edda';
		statusMessage.style.borderLeftColor = '#28a745';
		statusMessage.style.color = '#155724';
		statusMessage.innerHTML = `✓ <strong>Guthaben:</strong> Sie haben ein Guthaben von <strong>${saldo.toFixed(2).replace('.', ',')} €</strong>`;
	} else {
		// Ausgeglichen
		statusMessage.style.background = '#d1ecf1';
		statusMessage.style.borderLeftColor = '#17a2b8';
		statusMessage.style.color = '#0c5460';
		statusMessage.innerHTML = `✓ <strong>Ausgeglichen:</strong> Ihr Konto ist ausgeglichen.`;
	}

	// Rendere Vorschreibungen
	if (vorschreibungen.length > 0) {
		const tbody = document.querySelector('#vorschreibungen-table tbody');
		tbody.innerHTML = '';

		vorschreibungen.forEach(v => {
			const statusClass = v.status === 'paid' ? 'status-paid' : 'status-open';
			const statusText = v.status === 'paid' ? '✓ Bezahlt' : '⏳ Offen';
			const period = v.month.toString().padStart(2, '0') + '/' + v.year;

			const row = document.createElement('tr');
			row.innerHTML = `
				<td style="border: 1px solid #ddd; padding: 10px;">${escapeHtml(period)}</td>
				<td style="border: 1px solid #ddd; padding: 10px; text-align: right;">${formatAmount(v.amount)}</td>
				<td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><span class="${statusClass}">${statusText}</span></td>
				<td style="border: 1px solid #ddd; padding: 10px; font-size: 12px; color: #666;">${escapeHtml(formatDate(v.created_at))}</td>
			`;
			tbody.appendChild(row);
		});
		vorschreibungenSection.style.display = 'block';
	}

	// Rendere Zahlungen
	if (zahlungen.length > 0) {
		const tbody = document.querySelector('#zahlungen-table tbody');
		tbody.innerHTML = '';

		zahlungen.forEach(z => {
			const statusClass = z.status === 'matched' ? 'status-matched' : 'status-pending';
			const statusText = z.status === 'matched' ? '✓ Zugeordnet' : '⏳ Ausstehend';

			const row = document.createElement('tr');
			row.innerHTML = `
				<td style="border: 1px solid #ddd; padding: 10px;">${escapeHtml(z.valutadatum)}</td>
				<td style="border: 1px solid #ddd; padding: 10px;">${escapeHtml(z.partnername)}</td>
				<td style="border: 1px solid #ddd; padding: 10px; font-size: 12px; color: #666;">${escapeHtml(z.verwendungszweck)}</td>
				<td style="border: 1px solid #ddd; padding: 10px; text-align: right;">${formatAmount(z.betrag)}</td>
				<td style="border: 1px solid #ddd; padding: 10px; text-align: center;"><span class="${statusClass}">${statusText}</span></td>
			`;
			tbody.appendChild(row);
		});
		zahlungenSection.style.display = 'block';
	}
}

function formatAmount(amount) {
	if (!amount) return '0,00 €';
	return parseFloat(amount).toFixed(2).replace('.', ',') + ' €';
}

function formatDate(dateStr) {
	if (!dateStr) return '—';
	try {
		const date = new Date(dateStr);
		return date.toLocaleDateString('de-AT');
	} catch {
		return dateStr;
	}
}

function escapeHtml(text) {
	const div = document.createElement('div');
	div.textContent = text;
	return div.innerHTML;
}

// Starte wenn DOM fertig ist
document.addEventListener('DOMContentLoaded', load);
