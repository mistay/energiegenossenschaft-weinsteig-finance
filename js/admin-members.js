document.addEventListener('DOMContentLoaded', function() {
	const table = document.getElementById('members-table');
	const modal = document.getElementById('assign-modal');
	const modalBackdrop = document.getElementById('assign-modal-backdrop');
	const modalAddress = document.getElementById('modal-member-address');
	const userSelect = document.getElementById('user-select');
	const assignBtn = document.getElementById('assign-btn');
	const closeBtn = document.getElementById('modal-close');

	let currentMemberId = null;
	let allAssignments = {};
	let allUsers = [];

	function loadMembers() {
		// Show info box for obpersonen
		let html = '<div style="background: #e3f2fd; border-left: 4px solid #0082c9; padding: 16px; border-radius: 4px; margin-bottom: 20px; color: #0082c9;">';
		html += '<strong>ℹ️ Hinweis:</strong> Du kannst diese Seite nur sehen, weil dein Nutzerkonto in der Gruppe 👑 obperson geführt wird.';
		html += '</div>';
		table.innerHTML = html;

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

		let html = '<div id="members-cards">';
		members.forEach(member => {
			// Formatiere offene Beträge: Rot für Rückstand (negativ), Grün für Guthaben (positiv)
			const amountClass = member.open_amount < -0.01 ? 'amount-negative' : (member.open_amount > 0.01 ? 'amount-positive' : 'amount-zero');
			const amountText = member.open_amount ? member.open_amount.toFixed(2) + ' €' : '0,00 €';
			const journalUrl = OC.generateUrl('/apps/weinsteigfinance/journal?member=' + member.id);

			html += `
				<div class="member-card" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
					<!-- Header: Haus -->
					<div style="background: #0082c9; color: white; padding: 12px 14px; font-weight: bold; font-size: 14px;">📍 ${escapeHtml(member.address)}</div>

					<!-- Body -->
					<div style="padding: 12px 14px;">
						<!-- Benutzer -->
						<div style="margin-bottom: 12px;">
							<div style="font-weight: 600; color: #333; margin-bottom: 6px; font-size: 13px;">Zugeordnete Benutzer</div>
							<div class="users-col-${member.id}" style="background: #f9f9f9; padding: 8px; border-radius: 3px; border: 1px solid #eee; min-height: 32px;">-</div>
						</div>

						<!-- Offene Beträge -->
						<div style="margin-bottom: 12px; padding: 8px; background: #f9f9f9; border-radius: 3px; border-left: 3px solid #0082c9;">
							<div style="font-size: 12px; color: #666; margin-bottom: 4px;">Offene Beträge</div>
							<div class="${amountClass}" style="font-size: 15px; font-weight: bold;">${amountText}</div>
						</div>

						<!-- Aktionen -->
						<div style="display: flex; gap: 6px; flex-wrap: wrap;">
							<button class="assign-btn" data-id="${member.id}" data-addr="${escapeHtml(member.address)}" style="flex: 1; min-width: 120px; padding: 6px 10px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: 500;">+ Benutzer</button>
							<a href="${journalUrl}" style="flex: 1; min-width: 120px; padding: 6px 10px; color: white; text-decoration: none; font-size: 12px; border: none; border-radius: 3px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; background: #0082c9; font-weight: 500;">📊 Journal</a>
						</div>
					</div>
				</div>
			`;
		});
		html += '</div>';

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
						modalBackdrop.style.display = 'block';
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
					closeModal();
					loadMembers();
				}
			});
	});

	function closeModal() {
		modal.style.display = 'none';
		modalBackdrop.style.display = 'none';
	}

	closeBtn.addEventListener('click', closeModal);
	modalBackdrop.addEventListener('click', closeModal);

	// Export Users CSV
	const exportBtn = document.getElementById('export-users-btn');
	if (exportBtn) {
		exportBtn.addEventListener('click', function() {
			window.location.href = OC.generateUrl('/apps/weinsteigfinance/api/export-users');
		});
		exportBtn.addEventListener('mouseover', function() {
			this.style.background = '#0070a8';
		});
		exportBtn.addEventListener('mouseout', function() {
			this.style.background = '#0082c9';
		});
	}

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	loadMembers();
});
