document.addEventListener('DOMContentLoaded', function() {
	const exportBtn = document.getElementById('export-btn');
	const status = document.getElementById('status');

	exportBtn.addEventListener('click', function() {
		exportBtn.disabled = true;
		status.textContent = '⏳ Backup wird erstellt...';
		status.style.color = '#0082c9';

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/backup/export'))
			.then(response => {
				if (!response.ok) {
					throw new Error('Export fehlgeschlagen: ' + response.status);
				}
				return response.blob();
			})
			.then(blob => {
				// Filename aus Content-Disposition Header oder Fallback
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = `weinsteig-finance-backup_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.json`;
				document.body.appendChild(a);
				a.click();
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);

				status.textContent = '✓ Backup erfolgreich heruntergeladen!';
				status.style.color = '#28a745';

				// Reset nach 3 Sekunden
				setTimeout(() => {
					exportBtn.disabled = false;
					status.textContent = '';
				}, 3000);
			})
			.catch(error => {
				console.error('Error:', error);
				status.textContent = '✗ Fehler beim Export: ' + error.message;
				status.style.color = '#dc3545';
				exportBtn.disabled = false;
			});
	});
});
