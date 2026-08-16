document.addEventListener('DOMContentLoaded', function() {
	const creditorInput = document.getElementById('creditor-id');
	const ibanInput = document.getElementById('creditor-iban');
	const bicInput = document.getElementById('creditor-bic');
	const configSaveBtn = document.getElementById('config-save');
	const configStatus = document.getElementById('config-status');

	function loadConfig() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/config'))
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					configStatus.textContent = 'Fehler: ' + data.error;
					configStatus.style.color = '#dc3545';
					return;
				}
				applyConfig(data);

				const missing = [];
				if (!data.creditorId) missing.push('Creditor ID');
				if (!data.creditorIban) missing.push('IBAN');
				if (!data.creditorBic) missing.push('BIC');

				if (missing.length) {
					configStatus.textContent = '⚠️ Noch nicht konfiguriert: ' + missing.join(', ');
					configStatus.style.color = '#dc3545';
				}
			});
	}

	function applyConfig(data) {
		creditorInput.value = data.creditorId || '';
		ibanInput.value = data.creditorIban || '';
		bicInput.value = data.creditorBic || '';
	}

	configSaveBtn.addEventListener('click', function() {
		configStatus.textContent = '';

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/config'), {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
				creditorId: creditorInput.value,
				creditorIban: ibanInput.value,
				creditorBic: bicInput.value
			})
		})
			.then(r => r.json())
			.then(data => {
				if (data.error) {
					configStatus.textContent = 'Fehler: ' + data.error;
					configStatus.style.color = '#dc3545';
					return;
				}
				applyConfig(data);
				configStatus.textContent = '✓ Gespeichert';
				configStatus.style.color = '#28a745';
			});
	});

	loadConfig();
});
