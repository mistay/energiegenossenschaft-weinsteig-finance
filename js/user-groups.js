document.addEventListener('DOMContentLoaded', function() {
	const infoDiv = document.getElementById('user-groups-info');
	if (!infoDiv) return;

	fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-groups'))
		.then(r => r.json())
		.then(data => {
			if (data.error) {
				infoDiv.textContent = 'Fehler: ' + data.error;
				infoDiv.style.color = '#d32f2f';
				infoDiv.style.background = '#ffebee';
				return;
			}

			const userId = data.userId || '?';
			const groups = data.groups || [];

			if (groups.length === 0) {
				infoDiv.innerHTML = `<strong>${escapeHtml(userId)}</strong><br><span style="font-size: 11px;">(keine Gruppe)</span>`;
			} else {
				const groupLabels = {
					'obpersonen': '👑 Admin',
					'mitglieder': '🏠 Mitglied'
				};

				const labels = groups.map(g => groupLabels[g] || g).join(', ');
				infoDiv.innerHTML = `<strong>${escapeHtml(userId)}</strong><br><span style="font-size: 11px;">${escapeHtml(labels)}</span>`;
			}
		});

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
});
