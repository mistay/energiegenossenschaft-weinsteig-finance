<?php
declare(strict_types=1);
style('weinsteigfinance', 'style');
$currentPage = 'backup-status';
?>
<div id="weinsteigfinance-backup-status" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h1 style="margin-bottom: 30px;">💾 Backup-Status</h1>

	<div style="margin-bottom: 20px;">
		<button id="create-backup-btn" style="
			padding: 12px 24px;
			font-size: 16px;
			background: #28a745;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-weight: 600;
		">
			⚡ Backup jetzt erstellen
		</button>
		<span id="create-status" style="margin-left: 12px; vertical-align: middle;"></span>
	</div>

	<div id="status-container" style="max-width: 900px;">
		<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
			<h2 style="margin-top: 0; font-size: 18px; margin-bottom: 24px;">📊 Status</h2>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
				<div style="background: #f0f7ff; border: 1px solid #0082c9; border-radius: 4px; padding: 16px;">
					<div style="font-size: 12px; color: #0082c9; font-weight: 600; margin-bottom: 8px;">🕐 LETZTES BACKUP</div>
					<div style="font-size: 20px; font-weight: bold; color: #333;" id="last-backup">—</div>
				</div>

				<div style="background: #e8f5e9; border: 1px solid #28a745; border-radius: 4px; padding: 16px;">
					<div style="font-size: 12px; color: #28a745; font-weight: 600; margin-bottom: 8px;">⏱️ NÄCHSTES BACKUP</div>
					<div style="font-size: 20px; font-weight: bold; color: #333;" id="next-backup">—</div>
				</div>
			</div>

			<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 16px;">
				<div style="font-size: 12px; color: #856404; font-weight: 600; margin-bottom: 8px;">⏳ VERBLEIBENDE ZEIT</div>
				<div style="font-size: 24px; font-weight: bold; color: #856404;" id="remaining-time">—</div>
			</div>
		</div>

		<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0; font-size: 18px; margin-bottom: 16px;">📥 Verfügbare Backups</h2>

			<div id="backups-list" style="overflow-x: auto;">
				<table style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="background: #f5f5f5;">
							<th style="text-align: left; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600;">Dateiname</th>
							<th style="text-align: left; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600;">Größe</th>
							<th style="text-align: center; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600;">Aktion</th>
						</tr>
					</thead>
					<tbody id="backups-tbody">
						<tr><td colspan="3" style="text-align: center; padding: 20px; color: #999;">lädt...</td></tr>
					</tbody>
				</table>
			</div>
		</div>

		<div style="background: #e8f5e9; border-left: 4px solid #28a745; padding: 20px; border-radius: 4px; margin-top: 30px;">
			<p style="margin: 0; color: #2e7d32;">
				<strong>✅ Automatische Backups:</strong><br>
				Die Datenbank wird täglich um 02:00 Uhr automatisch gesichert.
				Alle Backups werden im Ordner <code style="background: #f0f0f0; padding: 2px 4px;">/data/backup/</code> gespeichert.
			</p>
		</div>
	</div>
</div>

<script>
console.log('backup-status.php script loaded');

document.addEventListener('DOMContentLoaded', function() {
	console.log('DOMContentLoaded fired');
	loadBackupStatus();
	setupCreateBackupButton();
	// Reload status every 30 seconds
	setInterval(loadBackupStatus, 30000);
});

function setupCreateBackupButton() {
	const btn = document.getElementById('create-backup-btn');
	const status = document.getElementById('create-status');

	if (!btn) {
		console.error('create-backup-btn not found');
		return;
	}

	btn.addEventListener('click', function() {
		console.log('Backup button clicked');
		btn.disabled = true;
		status.textContent = '⏳ Backup wird erstellt...';
		status.style.color = '#0082c9';

		fetch('/index.php/apps/weinsteigfinance/api/backup/export', {
			method: 'GET',
			headers: {
				'Accept': 'application/json',
			},
		})
			.then(r => {
				console.log('Response status:', r.status);
				if (!r.ok) {
					throw new Error('HTTP ' + r.status + ': ' + r.statusText);
				}
				return r.json();
			})
			.then(data => {
				console.log('Response data:', data);
				if (data.success && data.downloadUrl) {
					status.textContent = '✓ Backup erstellt!';
					status.style.color = '#28a745';
					window.location.href = data.downloadUrl;
					setTimeout(() => {
						loadBackupStatus(); // Reload list
					}, 500);
					setTimeout(() => {
						btn.disabled = false;
						status.textContent = '';
					}, 3000);
				} else {
					throw new Error(data.error || 'Unbekannter Fehler');
				}
			})
			.catch(err => {
				console.error('Error:', err);
				status.textContent = '✗ Fehler: ' + err.message;
				status.style.color = '#dc3545';
				btn.disabled = false;
			});
	});
}

function loadBackupStatus() {
	const url = '/index.php/apps/weinsteigfinance/api/backup/status';
	console.log('Loading backup status from:', url);

	fetch(url, {
		method: 'GET',
		headers: {
			'Accept': 'application/json',
		},
	})
		.then(r => {
			console.log('loadBackupStatus response status:', r.status);
			if (!r.ok) {
				throw new Error('API status: ' + r.status + ' ' + r.statusText);
			}
			return r.json();
		})
		.then(data => {
			console.log('Backup status data:', data);

			if (data.error) {
				throw new Error(data.error);
			}

			document.getElementById('last-backup').textContent = data.lastBackupDate || '—';
			document.getElementById('next-backup').textContent = data.nextBackupDate || '—';
			document.getElementById('remaining-time').textContent = data.remainingTime || '—';

			const tbody = document.getElementById('backups-tbody');
			tbody.innerHTML = '';

			if (!data.backups || data.backups.length === 0) {
				tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px; color: #999;">Keine Backups vorhanden</td></tr>';
				return;
			}

			data.backups.forEach(backup => {
				const sizeMB = (backup.size / (1024 * 1024)).toFixed(2);
				const row = document.createElement('tr');
				row.style.borderBottom = '1px solid #ddd';
				row.innerHTML = `
					<td style="padding: 12px; font-family: monospace; font-size: 12px;">${backup.date}</td>
					<td style="padding: 12px;">${sizeMB} MB</td>
					<td style="padding: 12px; text-align: center;">
						<a href="${backup.url}" style="
							display: inline-block;
							padding: 6px 12px;
							background: #0082c9;
							color: white;
							text-decoration: none;
							border-radius: 3px;
							font-size: 12px;
						">📥 Download</a>
					</td>
				`;
				tbody.appendChild(row);
			});
		})
		.catch(err => {
			console.error('Error loading backup status:', err);
			document.getElementById('backups-tbody').innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 20px; color: #dc3545;">Fehler beim Laden: ' + err.message + '</td></tr>';
		});
}
</script>
