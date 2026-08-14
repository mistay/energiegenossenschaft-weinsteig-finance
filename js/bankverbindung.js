document.addEventListener('DOMContentLoaded', function() {
	const list = document.getElementById('members-list');
	const modal = document.getElementById('edit-modal');
	const editAddress = document.getElementById('edit-address');
	const editZahlungspflichtig = document.getElementById('edit-zahlungspflichtig');
	const editIban = document.getElementById('edit-iban');
	const saveBtn = document.getElementById('save-btn');
	const cancelBtn = document.getElementById('cancel-btn');

	let currentMemberId = null;
	let isObperson = false;

	function load() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/members'))
			.then(r => r.json())
			.then(members => {
				if (members.error) {
					list.innerHTML = '<p>Fehler: ' + members.error + '</p>';
					return;
				}

				// Check ob obperson
				isObperson = !members.error && Array.isArray(members);

				let html = '<table class="grid" style="width:100%"><thead><tr>' +
					'<th>Haus</th><th>Zahlungspflichtig</th><th>IBAN</th><th>Aktion</th>' +
					'</tr></thead><tbody>';

				if (isObperson) {
					// Obperson sieht alle Häuser
					members.forEach(m => {
						html += `<tr>
							<td>${escapeHtml(m.address)}</td>
							<td>${escapeHtml(m.zahlungspflichtig || '-')}</td>
							<td>${escapeHtml(m.iban || '-')}</td>
							<td><button class="edit-btn" data-id="${m.id}" data-addr="${escapeHtml(m.address)}" data-zahl="${escapeHtml(m.zahlungspflichtig || '')}" data-iban="${escapeHtml(m.iban || '')}">Bearbeiten</button></td>
						</tr>`;
					});
				} else {
					// Mitglied sieht nur eigenes Haus
					list.innerHTML = '<p>Nur Obpersonen können Bankverbindungen zentral verwalten.</p>';
					return;
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
			});
	}

	saveBtn.addEventListener('click', function() {
		if (!currentMemberId) return;

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/member/' + currentMemberId), {
			method: 'PUT',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
				zahlungspflichtig: editZahlungspflichtig.value,
				iban: editIban.value
			})
		})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					modal.style.display = 'none';
					load();
				}
			});
	});

	cancelBtn.addEventListener('click', function() {
		modal.style.display = 'none';
	});

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	load();
});
