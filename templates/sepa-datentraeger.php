<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'sepa-datentraeger';
?>

<div id="weinsteigfinance-sepa-datentraeger" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>📄 SEPA-Datenträger</h2>

	<!-- Ungenehmigten Mandate -->
	<div id="pending-section" style="display: none; margin-bottom: 30px;">
		<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 4px;">
			<h3 style="margin-top: 0; color: #856404;">⏳ Mandate warten auf Freigabe</h3>
			<p style="color: #856404; margin: 0 0 15px 0;">Diese Häuser haben SEPA-Mandate hochgeladen, aber noch nicht genehmigt:</p>
			<table style="width: 100%; border-collapse: collapse; color: #333;">
				<thead>
					<tr style="background: #fff0d4; border-bottom: 2px solid #ffc107;">
						<th style="text-align: left; padding: 10px; font-weight: 600;">🏠 Haus</th>
						<th style="text-align: left; padding: 10px; font-weight: 600;">👤 Kontoinhaber</th>
						<th style="text-align: left; padding: 10px; font-weight: 600;">📱 IBAN</th>
						<th style="text-align: center; padding: 10px; font-weight: 600;">✓ Status</th>
					</tr>
				</thead>
				<tbody id="pending-tbody">
					<tr><td colspan="4" style="padding: 10px; text-align: center;">Lädt...</td></tr>
				</tbody>
			</table>
		</div>
	</div>

	<div id="sepa-container" style="margin-top: 20px;">
		<p style="color: #999;">Lädt...</p>
	</div>

	<script>
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

	function escapeHtml(text) {
		const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
		return String(text).replace(/[&<>"']/g, m => map[m]);
	}
	</script>
</div>

<style>
.sepa-card {
	transition: box-shadow 0.2s, transform 0.2s;
}

.sepa-card:hover {
	box-shadow: 0 2px 6px rgba(0,0,0,0.12);
	transform: translateY(-1px);
}

.amount-positive {
	color: #28a745;
	font-weight: bold;
}

.amount-negative {
	color: #dc3545;
	font-weight: bold;
}

.amount-zero {
	color: #28a745;
	font-weight: bold;
}

.export-btn {
	background: #0082c9;
	color: white;
	border: none;
	padding: 10px 20px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 14px;
	margin-bottom: 20px;
	transition: background 0.2s;
	font-weight: 500;
}

.export-btn:hover {
	background: #0070a8;
}

.stats-box {
	background: #f0f8ff;
	border-left: 4px solid #0082c9;
	padding: 15px;
	border-radius: 4px;
	margin-bottom: 20px;
	display: flex;
	justify-content: space-around;
	gap: 20px;
	flex-wrap: wrap;
}

.stat-item {
	text-align: center;
	flex: 1;
	min-width: 100px;
}

.stat-label {
	font-size: 12px;
	color: #999;
	margin-bottom: 5px;
}

.stat-value {
	font-size: 18px;
	font-weight: bold;
	color: #0082c9;
}

.info-box {
	background: #fff3e0;
	border-left: 4px solid #ff9800;
	padding: 12px;
	border-radius: 4px;
	margin-bottom: 20px;
	font-size: 13px;
	color: #666;
}

/* Responsive */
@media (max-width: 768px) {
	.sepa-card {
		margin-bottom: 10px;
	}

	.stat-item {
		min-width: 80px;
	}

	.stat-label {
		font-size: 11px;
	}

	.stat-value {
		font-size: 16px;
	}

	.export-btn {
		padding: 8px 16px;
		font-size: 13px;
	}
}
</style>

<script>
function loadSepaData() {
	const url = OCA?.generateUrl?.('/apps/weinsteigfinance/api/sepa-datentraeger') || '/index.php/apps/weinsteigfinance/api/sepa-datentraeger';
	fetch(url, { credentials: 'include' })
		.then(r => r.json())
		.then(data => {
			let html = '<button class="export-btn" onclick="exportCsv()">📥 Als CSV exportieren</button>';

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

function escapeHtml(text) {
	const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
	return String(text).replace(/[&<>"']/g, m => map[m]);
}
</script>
