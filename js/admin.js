document.addEventListener('DOMContentLoaded', function() {
	const table = document.getElementById('members-table');
	const modal = document.getElementById('assign-modal');
	const modalAddress = document.getElementById('modal-member-address');
	const userSelect = document.getElementById('user-select');
	const assignBtn = document.getElementById('assign-btn');
	const closeBtn = document.getElementById('modal-close');

	let currentMemberId = null;
	let allAssignments = {};
	let allUsers = [];

	function loadMembers() {
		fetch(OC.generateUrl('/apps/weinsteigfinance/api/members'))
			.then(r => r.json())
			.then(members => {
				if (members.error) {
					table.innerHTML = '<tr><td colspan="4">Fehler: ' + members.error + '</td></tr>';
					return;
				}

				fetch(OC.generateUrl('/apps/weinsteigfinance/api/users'))
					.then(r => r.json())
					.then(users => {
						allUsers = users;
						loadAssignments(members, users);
					});
			});
	}

	function loadAssignments(members, users) {
		const url = OC.generateUrl('/apps/weinsteigfinance/api/members') + '?loadAssignments=1';

		let html = '';
		members.forEach(member => {
			const amountClass = member.open_amount > 0 ? 'amount-negative' : (member.open_amount < 0 ? 'amount-positive' : 'amount-zero');
			const amountText = member.open_amount ? member.open_amount.toFixed(2) + ' €' : '0,00 €';
			const journalUrl = OC.generateUrl('/apps/weinsteigfinance/journal?member=' + member.id);
			html += `
				<tr>
					<td>${escapeHtml(member.address)}</td>
					<td class="users-col-${member.id}">-</td>
					<td class="${amountClass}"><strong>${amountText}</strong></td>
					<td style="display: flex; gap: 4px; flex-wrap: wrap; align-items: center;">
						<button class="assign-btn" data-id="${member.id}" data-addr="${escapeHtml(member.address)}">
							+ Benutzer
						</button>
						<a href="${journalUrl}" style="color: #0082c9; text-decoration: none; font-size: 12px; padding: 4px 8px; border: 1px solid #0082c9; border-radius: 3px; cursor: pointer; display: inline-block;">
							📊 Journal
						</a>
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
					allAssignments = {};
					data.assignments.forEach(a => {
						if (!allAssignments[a.member_id]) {
							allAssignments[a.member_id] = [];
						}
						allAssignments[a.member_id].push(a.user_id);
					});

					Object.keys(allAssignments).forEach(memberId => {
						const cell = document.querySelector('.users-col-' + memberId);
						if (cell) {
							const userList = allAssignments[memberId].map(uid => {
								return `<div>${escapeHtml(uid)} <button class="remove-btn" data-member="${memberId}" data-user="${escapeHtml(uid)}">✕</button></div>`;
							}).join('');
							cell.innerHTML = userList;

							// Remove-Button Handler
							cell.querySelectorAll('.remove-btn').forEach(btn => {
								btn.addEventListener('click', function(e) {
									e.stopPropagation();
									const mId = this.dataset.member;
									const uId = this.dataset.user;
									fetch(OC.generateUrl('/apps/weinsteigfinance/api/unassign'), {
										method: 'POST',
										headers: {'Content-Type': 'application/json'},
										body: JSON.stringify({memberId: mId, userId: uId})
									})
										.then(r => r.json())
										.then(data => {
											if (data.success) {
												loadMembers();
											}
										});
								});
							});
						}
					});
				}

				// Event-Handler für Zuordnen-Buttons
				document.querySelectorAll('.assign-btn').forEach(btn => {
					btn.addEventListener('click', function() {
						currentMemberId = this.dataset.id;
						modalAddress.textContent = this.dataset.addr;

						// Benutzer-Liste füllen (ausser bereits zugeordnete)
						const assigned = allAssignments[currentMemberId] || [];
						userSelect.innerHTML = '<option value="">-- Bitte wählen --</option>';
						allUsers.forEach(user => {
							if (!assigned.includes(user.uid)) {
								userSelect.innerHTML += `<option value="${escapeHtml(user.uid)}">${escapeHtml(user.displayName || user.uid)}</option>`;
							}
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
