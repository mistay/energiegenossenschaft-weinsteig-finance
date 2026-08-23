<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'sepa-datentraeger';
script('weinsteigfinance', 'sepa-datentraeger');
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
