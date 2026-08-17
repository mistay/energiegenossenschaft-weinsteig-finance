document.addEventListener('DOMContentLoaded', function() {
	const list = document.getElementById('members-list');
	const modal = document.getElementById('edit-modal');
	const modalBackdrop = document.getElementById('edit-modal-backdrop');
	const withdrawModal = document.getElementById('withdraw-modal');
	const withdrawModalBackdrop = document.getElementById('withdraw-modal-backdrop');
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
					modalBackdrop.style.display = 'none';
					ibanStatus.textContent = '';
					load();
				} else if (data.error) {
					alert('Fehler: ' + data.error);
				}
			});
	}

	function load() {
		// Get user groups and members in parallel
		Promise.all([
			fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-groups')).then(r => r.json()),
			fetch(OC.generateUrl('/apps/weinsteigfinance/api/members')).then(r => r.json())
		])
			.then(([userInfo, members]) => {
				// Wenn Fehler (Unauthorized) oder nicht Array → Mitglied
				if (!Array.isArray(members)) {
					// Nicht obperson/kassier - eigenes Mitglied laden
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
				// Show info box for authorized groups
				const userGroups = userInfo.groups || [];
				const authorizedGroups = ['obpersonen', 'kassier:innen'];
				const hasAuthorization = userGroups.some(g => authorizedGroups.includes(g));

				if (hasAuthorization && userGroups.length > 0) {
					const groupLabels = {
						'obpersonen': '👑 Admin',
						'kassier:innen': '💰 Kassier:in'
					};
					const visibleGroups = userGroups
						.filter(g => authorizedGroups.includes(g))
						.map(g => groupLabels[g] || g)
						.join(', ');

					if (visibleGroups) {
						showInfoBox(visibleGroups);
					}
				}

				renderMembers(members);
			})
			.catch(() => {
				// Fallback: try loading members directly
				fetch(OC.generateUrl('/apps/weinsteigfinance/api/members'))
					.then(r => r.json())
					.then(members => {
						if (Array.isArray(members)) {
							isObperson = true;
							renderMembers(members);
						}
					});
			});
	}

	function showInfoBox(groupLabels) {
		const infoBox = document.createElement('div');
		infoBox.style.cssText = 'background: #e3f2fd; border-left: 4px solid #0082c9; padding: 16px; border-radius: 4px; margin-bottom: 20px; color: #0082c9;';
		infoBox.innerHTML = '<strong>ℹ️ Hinweis:</strong> Es werden alle Bankverbindungen angezeigt, weil dieses Nutzerkonto in der Gruppe ' + escapeHtml(groupLabels) + ' geführt wird.';

		const listElement = document.getElementById('members-list');
		if (listElement && listElement.parentNode) {
			listElement.parentNode.insertBefore(infoBox, listElement);
		}
	}

	function escapeHtml(text) {
		if (!text) return '';
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function renderMembers(members) {
		if (members.error) {
			list.innerHTML = '<p>Fehler: ' + members.error + '</p>';
			return;
		}

		let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; padding: 8px;">';

		if (isObperson || isUserView) {
			members = Array.isArray(members) ? members : [members];
			members.forEach(m => {
				let mandatInfo;
				if (m.mandate_withdrawn_date) {
					mandatInfo = `✗ ${escapeHtml(m.mandate_withdrawn_reason || 'Zurückgezogen')}`;
				} else if (m.mandate_granted_date) {
					const date = new Date(m.mandate_granted_date).toLocaleDateString('de-AT');
					mandatInfo = `✓ Aktiv (seit ${date})`;
				} else {
					mandatInfo = '✓ Aktiv';
				}
				html += `
					<div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
						<div style="background: #0082c9; color: white; padding: 12px; font-weight: 600;">
							${escapeHtml(m.address)}
						</div>
						<div style="padding: 12px;">
							<div style="margin-bottom: 10px;">
								<div style="font-size: 11px; color: #999; font-weight: 600; margin-bottom: 4px;">ZAHLUNGSPFLICHTIG</div>
								<div>${escapeHtml(m.zahlungspflichtig || '-')}</div>
							</div>
							<div style="margin-bottom: 10px;">
								<div style="font-size: 11px; color: #999; font-weight: 600; margin-bottom: 4px;">IBAN</div>
								<div style="font-family: monospace; word-break: break-all;">${escapeHtml(m.iban || '-')}</div>
							</div>
							<div style="margin-bottom: 10px;">
								<div style="font-size: 11px; color: #999; font-weight: 600; margin-bottom: 4px;">MANDAT</div>
								<div>${mandatInfo}</div>
							</div>
							<div style="margin-bottom: 12px;">
								<div style="font-size: 11px; color: #999; font-weight: 600; margin-bottom: 4px;">UNTERSCHRIEBENE MANDATE</div>
								<div class="downloads-cell-${m.id}" style="font-size: 12px;">lädt...</div>
							</div>
							<div style="display: flex; gap: 6px; flex-wrap: wrap;">
								<button class="edit-btn" data-id="${m.id}" data-addr="${escapeHtml(m.address)}" data-zahl="${escapeHtml(m.zahlungspflichtig || '')}" data-iban="${escapeHtml(m.iban || '')}" style="padding: 6px 10px; font-size: 12px; flex: 1; min-width: 70px;">Bearbeiten</button>
								<a href="${OC.generateUrl('/apps/weinsteigfinance/api/member/' + m.id + '/mandate-pdf')}" target="_blank" style="padding: 6px 10px; font-size: 12px; flex: 1; min-width: 70px; text-align: center; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; display: flex; align-items: center; justify-content: center;">📄 Vorlage</a>
								<button class="upload-signed-btn" data-id="${m.id}" style="padding: 6px 10px; font-size: 12px; flex: 1; min-width: 70px;">📤 Upload</button>
							</div>
						</div>
					</div>
				`;
			});
		}

		html += '</div>';
		list.innerHTML = html;

		document.querySelectorAll('.edit-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				currentMemberId = this.dataset.id;
				editAddress.textContent = this.dataset.addr;
				editZahlungspflichtig.value = this.dataset.zahl;
				editIban.value = this.dataset.iban;
				modal.style.display = 'block';
				modalBackdrop.style.display = 'block';
			});
		});

		document.querySelectorAll('.upload-signed-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				const memberId = this.dataset.id;
				const input = document.createElement('input');
				input.type = 'file';
				input.accept = 'application/pdf';
				input.addEventListener('change', function() {
					if (!this.files.length) return;
					const formData = new FormData();
					formData.append('file', this.files[0]);
					fetch(OC.generateUrl('/apps/weinsteigfinance/api/member/' + memberId + '/mandate-signed'), {
						method: 'POST',
						body: formData
					})
						.then(r => r.json())
						.then(data => {
							if (data.success) {
								alert('Datei erfolgreich hochgeladen');
								load();
							} else {
								alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
							}
						});
				});
				input.click();
			});
		});

		// Lade unterschriebene Mandate für jedes Mitglied
		const editBtns = document.querySelectorAll('.edit-btn');
		editBtns.forEach(editBtn => {
			const memberId = editBtn.dataset.id;
			const downloadCell = document.querySelector('.downloads-cell-' + memberId);
			if (!downloadCell) return;

			fetch(OC.generateUrl('/apps/weinsteigfinance/api/member/' + memberId + '/mandate-signed'), {method: 'GET'})
				.then(r => r.json())
				.then(data => {
					if (data.exists && data.files && data.files.length > 0) {
						downloadCell.innerHTML = '';
						data.files.forEach(f => {
							const link = document.createElement('a');
							link.href = f.downloadUrl;
							link.style.display = 'block';
							link.style.marginBottom = '4px';
							link.style.color = '#28a745';
							link.style.textDecoration = 'none';
							link.style.fontSize = '12px';
							const date = new Date(f.mtime * 1000).toLocaleDateString('de-AT');
							link.innerHTML = `📥 v${f.version} (${date})`;
							downloadCell.appendChild(link);
						});
					} else {
						downloadCell.innerHTML = '<span style="font-size: 12px; color: #999;">—</span>';
					}
				})
				.catch(err => {
					downloadCell.innerHTML = '<span style="font-size: 12px; color: #999;">—</span>';
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
		modalBackdrop.style.display = 'none';
		withdrawAddress.textContent = editAddress.textContent;
		withdrawReason.value = '';
		withdrawModal.style.display = 'block';
		withdrawModalBackdrop.style.display = 'block';
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
					withdrawModalBackdrop.style.display = 'none';
					load();
				} else if (data.error) {
					alert('Fehler: ' + data.error);
				}
			});
	});

	cancelBtn.addEventListener('click', function() {
		modal.style.display = 'none';
		modalBackdrop.style.display = 'none';
	});

	withdrawCancelBtn.addEventListener('click', function() {
		withdrawModal.style.display = 'none';
		withdrawModalBackdrop.style.display = 'none';
	});

	// Schließe Modal wenn auf Backdrop geklickt wird
	modalBackdrop.addEventListener('click', function() {
		modal.style.display = 'none';
		modalBackdrop.style.display = 'none';
	});

	withdrawModalBackdrop.addEventListener('click', function() {
		withdrawModal.style.display = 'none';
		withdrawModalBackdrop.style.display = 'none';
	});

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	load();
});
