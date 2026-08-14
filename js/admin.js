document.addEventListener('DOMContentLoaded', function() {
	const table = document.getElementById('members-table');
	const modal = document.getElementById('assign-modal');
	const modalAddress = document.getElementById('modal-member-address');
	const userSelect = document.getElementById('user-select');
	const assignBtn = document.getElementById('assign-btn');
	const closeBtn = document.getElementById('modal-close');

	let currentMemberId = null;

	function loadMembers() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/members'))
			.then(r => r.json())
			.then(members => {
				if (members.error) {
					table.innerHTML = '<tr><td colspan="3">Fehler: ' + members.error + '</td></tr>';
					return;
				}

				fetch(OC.generateUrl('/apps/weinsteigfinance/api/users'))
					.then(r => r.json())
					.then(users => {
						loadAssignments(members, users);
					});
			});
	}

	function loadAssignments(members, users) {
		const url = OC.generateUrl('/apps/weinsteigfinance/api/members') + '?loadAssignments=1';

		let html = '';
		members.forEach(member => {
			html += `
				<tr>
					<td>${escapeHtml(member.address)}</td>
					<td class="users-col-${member.id}">-</td>
					<td>
						<button class="assign-btn" data-id="${member.id}" data-addr="${escapeHtml(member.address)}">
							+ Benutzer
						</button>
					</td>
				</tr>
			`;
		});

		table.innerHTML = html;

		// Zuordnungen laden
		fetch(url)
			.then(r => r.json())
			.then(data => {
				if (data.assignments) {
					data.assignments.forEach(a => {
						const cell = document.querySelector('.users-col-' + a.member_id);
						if (cell) {
							cell.innerHTML = a.user_id;
						}
					});
				}

				// Event-Handler für Zuordnen-Buttons
				document.querySelectorAll('.assign-btn').forEach(btn => {
					btn.addEventListener('click', function() {
						currentMemberId = this.dataset.id;
						modalAddress.textContent = this.dataset.addr;

						// Benutzer-Liste füllen
						userSelect.innerHTML = '<option value="">-- Bitte wählen --</option>';
						users.forEach(user => {
							userSelect.innerHTML += `<option value="${escapeHtml(user.uid)}">${escapeHtml(user.displayName || user.uid)}</option>`;
						});

						modal.style.display = 'block';
					});
				});
			});
	}

	assignBtn.addEventListener('click', function() {
		const userId = userSelect.value;
		if (!userId || !currentMemberId) return;

		fetch(OC.generateUrl('/apps/weinsteigfinance/api/assign'), {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({memberId: currentMemberId, userId: userId})
		})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					modal.style.display = 'none';
					loadMembers();
				}
			});
	});

	closeBtn.addEventListener('click', function() {
		modal.style.display = 'none';
	});

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	loadMembers();
});
