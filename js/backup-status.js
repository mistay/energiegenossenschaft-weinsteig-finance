console.log('backup-status.js loaded');

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
			credentials: 'include',
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
		credentials: 'include',
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
