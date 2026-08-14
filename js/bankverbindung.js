document.addEventListener('DOMContentLoaded', function() {
	const list = document.getElementById('members-list');
	const modal = document.getElementById('edit-modal');
	const withdrawModal = document.getElementById('withdraw-modal');
	const editAddress = document.getElementById('edit-address');
	const editZahlungspflichtig = document.getElementById('edit-zahlungspflichtig');
	const editIban = document.getElementById('edit-iban');
	const saveBtn = document.getElementById('save-btn');
	const saveForceBtn = document.getElementById('save-force-btn');
	const withdrawBtn = document.getElementById('withdraw-btn');
	const cancelBtn = document.getElementById('cancel-btn');
	const withdrawAddress = document.getElementById('withdraw-address');
	const withdrawReason = document.getElementById('withdraw-reason');
	const withdrawConfirmBtn = document.getElementById('withdraw-confirm-btn');
	const withdrawCancelBtn = document.getElementById('withdraw-cancel-btn');

	let currentMemberId = null;
	let isObperson = false;
	let isUserView = false;
	const ibanStatus = document.getElementById('iban-status');

	// Live IBAN Validierung
	editIban.addEventListener('input', function() {
		const iban = this.value.trim().replace(/\s+/g, '');
		if (!iban) {
			ibanStatus.textContent = '';
			ibanStatus.style.color = '';
			return;
		}
		if (validateIBAN(iban)) {
			ibanStatus.textContent = '✓ gültig';
			ibanStatus.style.color = 'green';
		} else {
			ibanStatus.textContent = '✗ ungültig';
			ibanStatus.style.color = 'red';
		}
	});

	function saveData(skipValidation = false) {
		if (!currentMemberId) return;

		const iban = editIban.value.trim().replace(/\s+/g, '');
		if (!skipValidation && iban && !validateIBAN(iban)) {
			// Nicht speichern, nur visuell warning geben (red status ist schon da)
			return;
		}

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/member/' + currentMemberId), {
			method: 'PUT',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
				zahlungspflichtig: editZahlungspflichtig.value.trim(),
				iban: iban,
				force: skipValidation ? 1 : 0
			})
		})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					modal.style.display = 'none';
					ibanStatus.textContent = '';
					load();
				} else if (data.error) {
					alert('Fehler: ' + data.error);
				}
			});
	}

	function load() {
		// Erst obperson-Liste versuchen
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/members'))
			.then(r => r.json())
			.then(members => {
				// Wenn Fehler (Unauthorized) oder nicht Array → Mitglied
				if (!Array.isArray(members)) {
					// Nicht obperson - eigenes Mitglied laden
					fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-member'))
						.then(r => r.json())
						.then(data => {
							if (data.error) {
								list.innerHTML = '<p>Fehler: ' + data.error + '</p>';
								return;
							}
							isUserView = true;
							renderMembers([data]);
						});
					return;
				}

				isObperson = true;
				renderMembers(members);
			});
	}

	function renderMembers(members) {
		if (members.error) {
			list.innerHTML = '<p>Fehler: ' + members.error + '</p>';
			return;
		}

		let html = '<table class="grid" style="width:100%"><thead><tr>' +
			'<th>Haus</th><th>Zahlungspflichtig</th><th>IBAN</th><th>Mandat</th><th>Aktion</th>' +
			'</tr></thead><tbody>';

		if (isObperson || isUserView) {
			members = Array.isArray(members) ? members : [members];
			members.forEach(m => {
				const mandatInfo = m.mandate_withdrawn_date
					? `✗ ${escapeHtml(m.mandate_withdrawn_reason || 'Zurückgezogen')}`
					: '✓ Aktiv';
				html += `<tr>
					<td>${escapeHtml(m.address)}</td>
					<td>${escapeHtml(m.zahlungspflichtig || '-')}</td>
					<td>${escapeHtml(m.iban || '-')}</td>
					<td>${mandatInfo}</td>
					<td>
						<button class="edit-btn" data-id="${m.id}" data-addr="${escapeHtml(m.address)}" data-zahl="${escapeHtml(m.zahlungspflichtig || '')}" data-iban="${escapeHtml(m.iban || '')}">Bearbeiten</button>
						<a href="${OC.generateUrl('/apps/weinsteigfinance/api/member/' + m.id + '/mandate-pdf')}" target="_blank" style="margin-left: 10px; color: #0082c9; text-decoration: none;">📄 PDF</a>
					</td>
				</tr>`;
			});
		}

		html += '</tbody></table>';
		list.innerHTML = html;

		document.querySelectorAll('.edit-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				currentMemberId = this.dataset.id;
				editAddress.textContent = this.dataset.addr;
				editZahlungspflichtig.value = this.dataset.zahl;
				editIban.value = this.dataset.iban;
				modal.style.display = 'block';
			});
		});
	}

	saveBtn.addEventListener('click', function() {
		saveData(false);
	});

	saveForceBtn.addEventListener('click', function() {
		saveData(true);
	});

	withdrawBtn.addEventListener('click', function() {
		modal.style.display = 'none';
		withdrawAddress.textContent = editAddress.textContent;
		withdrawReason.value = '';
		withdrawModal.style.display = 'block';
	});

	withdrawConfirmBtn.addEventListener('click', function() {
		if (!currentMemberId) return;

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/member/' + currentMemberId + '/withdraw'), {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({reason: withdrawReason.value.trim()})
		})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					withdrawModal.style.display = 'none';
					load();
				} else if (data.error) {
					alert('Fehler: ' + data.error);
				}
			});
	});

	cancelBtn.addEventListener('click', function() {
		modal.style.display = 'none';
	});

	withdrawCancelBtn.addEventListener('click', function() {
		withdrawModal.style.display = 'none';
	});

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	load();
});
