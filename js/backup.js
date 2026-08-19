document.addEventListener('DOMContentLoaded', function() {
	const exportBtn = document.getElementById('export-btn');
	const status = document.getElementById('status');

	exportBtn.addEventListener('click', function() {
		exportBtn.disabled = true;
		status.textContent = '⏳ Backup wird erstellt...';
		status.style.color = '#0082c9';

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/backup/export'))
			.then(response => response.json())
			.then(data => {
				if (data.success && data.downloadUrl) {
					status.textContent = '✓ Backup erstellt. Lädt herunter...';
					status.style.color = '#28a745';

					// Starte Download via downloadUrl
					window.location.href = data.downloadUrl;

					// Reset nach 3 Sekunden
					setTimeout(() => {
						exportBtn.disabled = false;
						status.textContent = '';
					}, 3000);
				} else {
					throw new Error(data.error || 'Unbekannter Fehler');
				}
			})
			.catch(error => {
				console.error('Error:', error);
				status.textContent = '✗ Fehler beim Export: ' + error.message;
				status.style.color = '#dc3545';
				exportBtn.disabled = false;
			});
	});
});
