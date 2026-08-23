document.addEventListener('DOMContentLoaded', function() {
	loadRemindersData();
	setupEventListeners();
	updateNextGenerationTime();
});

function loadRemindersData() {
	fetch(OCA?.generateUrl?.('/apps/weinsteigfinance/api/members') || '/index.php/apps/weinsteigfinance/api/members',
		{ credentials: 'include' })
		.then(r => r.json())
		.then(data => {
			renderRemindersTable(data);
		})
		.catch(err => {
			document.getElementById('reminders-container').innerHTML =
				'<p style="color: #dc3545;">Fehler beim Laden: ' + err.message + '</p>';
		});
}

function renderRemindersTable(members) {
	const container = document.getElementById('reminders-container');

	if (!members || members.length === 0) {
		container.innerHTML = '<p style="color: #999;">Keine Häuser gefunden.</p>';
		return;
	}

	let html = '<div style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">';

	// Header
	html += `<div class="header-row">
		<div class="header-cell" style="width: 25%;">🏠 Haus</div>
		<div class="header-cell" style="width: 15%; text-align: right;">💰 Betrag</div>
		<div class="header-cell" style="width: 20%; text-align: center;">📊 Status</div>
		<div class="header-cell" style="width: 20%; text-align: center;">📅 Letzte</div>
		<div class="header-cell" style="width: 20%;">⚙️ Aktionen</div>
	</div>`;

	// Rows
	members.forEach((member, idx) => {
		const openAmount = calculateOpenAmount(member);
		const debtAmount = Math.abs(openAmount);  // Convert to positive debt value
		const reminderStage = member.reminder_stage || 0;
		const statusBadge = getReminderStatusBadge(reminderStage);
		const lastReminderDate = member.last_reminder_date ? new Date(member.last_reminder_date).toLocaleDateString('de-DE') : '-';
		const isSuppressed = member.reminder_stop_until ?
			new Date(member.reminder_stop_until) > new Date() : false;

		html += `<div class="reminder-row" style="border-bottom: 1px solid #eee; ${idx % 2 === 0 ? 'background: #f9f9f9;' : ''}">
			<div class="reminder-cell reminder-address" style="width: 25%;">
				${escapeHtml(member.address || '-')}
			</div>
			<div class="reminder-cell reminder-amount" style="width: 15%; text-align: right;">
				<strong>${openAmount < 0 ? '+' : ''}${debtAmount.toFixed(2)}€</strong>
				<div style="font-size: 11px; color: #666;">${openAmount < 0 ? 'Schuld' : 'Guthaben'}</div>
			</div>
			<div class="reminder-cell reminder-status" style="width: 20%; text-align: center;">
				${statusBadge}
			</div>
			<div class="reminder-cell" style="width: 20%; text-align: center; font-size: 12px; color: #666;">
				${lastReminderDate}
			</div>
			<div class="reminder-cell reminder-actions" style="width: 20%;">
				<button class="reminder-button ${openAmount < 10 ? 'reminder-button-disabled' : ''}"
					${openAmount < 10 ? 'disabled' : ''}
					data-action="create-reminder"
					data-member-id="${member.id}">
					💬 Mahnung
				</button>
				<button class="reminder-button"
					data-action="open-history"
					data-member-id="${member.id}"
					data-address="${escapeHtml(member.address)}">
					📋 Verlauf
				</button>
				<button class="reminder-button" style="background: #6c757d;"
					data-action="check-conditions"
					data-member-id="${member.id}"
					data-address="${escapeHtml(member.address)}">
					ℹ️ Warum?
				</button>
				${getSuppressButton(member.id, isSuppressed, member.reminder_stop_until)}
			</div>
		</div>`;
	});

	html += '</div>';
	container.innerHTML = html;
}

function getReminderStatusBadge(stage) {
	const stages = {
		0: { text: '🟢 Keine', class: 'badge-none' },
		1: { text: '🟡 Stufe 1', class: 'badge-stage1' },
		2: { text: '🔴 Stufe 2', class: 'badge-stage2' },
		3: { text: '⛔ Stufe 3', class: 'badge-stage3' }
	};
	const s = stages[stage] || stages[0];
	return `<span class="reminder-badge ${s.class}">${s.text}</span>`;
}

function getSuppressButton(memberId, isSuppressed, stopUntil) {
	if (isSuppressed) {
		const date = new Date(stopUntil).toLocaleDateString('de-DE');
		return `<button class="reminder-button reminder-button-danger"
			data-action="clear-reminder-stop"
			data-member-id="${memberId}">
			🔓 Stop aufheben (bis ${date})
		</button>`;
	} else {
		return `<button class="reminder-button"
			data-action="open-suppress-dialog"
			data-member-id="${memberId}">
			🔇 Stop
		</button>`;
	}
}

function calculateOpenAmount(member) {
	// Simplified: use member's journal or calculate from zahlungen/vorschreibungen
	// For now, return a placeholder
	return member.open_amount || 0;
}

function createReminderManual(memberId) {
	if (confirm('Mahnung jetzt erstellen und versenden?')) {
		fetch(OCA?.generateUrl?.(`/apps/weinsteigfinance/api/member/${memberId}/reminder/create`) ||
			`/index.php/apps/weinsteigfinance/api/member/${memberId}/reminder/create`,
			{
				method: 'POST',
				credentials: 'include',
				headers: { 'Content-Type': 'application/json' }
			})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					showNotification('✅ Mahnung erstellt und versendet!', 'success');
					loadRemindersData();
				} else {
					showNotification('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'), 'error');
				}
			})
			.catch(err => {
				showNotification('❌ Fehler beim Versand: ' + err.message, 'error');
			});
	}
}

function openHistoryModal(memberId, address) {
	document.getElementById('history-address').textContent = address;
	document.getElementById('history-modal').classList.add('active');

	fetch(OCA?.generateUrl?.(`/apps/weinsteigfinance/api/member/${memberId}/reminder-history`) ||
		`/index.php/apps/weinsteigfinance/api/member/${memberId}/reminder-history`,
		{ credentials: 'include' })
		.then(r => r.json())
		.then(data => {
			renderHistory(data);
		})
		.catch(err => {
			document.getElementById('history-list').innerHTML =
				'<p style="color: #dc3545;">Fehler beim Laden: ' + err.message + '</p>';
		});
}

function openConditionsModal(memberId, address) {
	const modal = document.createElement('div');
	modal.className = 'modal active';
	modal.id = 'conditions-modal-' + memberId;
	modal.innerHTML = `
		<div class="modal-content">
			<div class="modal-header">
				🔍 Warum wird eine Mahnung ${address}?
			</div>
			<div class="modal-body" style="text-align: center; padding: 20px;">
				Lädt...
			</div>
			<div class="modal-footer">
				<button class="modal-close" id="conditions-close-${memberId}">Schließen</button>
			</div>
		</div>
	`;
	document.body.appendChild(modal);

	// Close button
	document.getElementById('conditions-close-' + memberId).addEventListener('click', function() {
		modal.remove();
	});

	// Close on background click
	modal.addEventListener('click', function(e) {
		if (e.target === this) this.remove();
	});

	fetch(OCA?.generateUrl?.(`/apps/weinsteigfinance/api/member/${memberId}/reminder-check`) ||
		`/index.php/apps/weinsteigfinance/api/member/${memberId}/reminder-check`,
		{ credentials: 'include' })
		.then(r => {
			if (!r.ok) throw new Error('HTTP ' + r.status);
			return r.json();
		})
		.then(data => {
			console.log('Reminder check data:', data);
			if (data.error) {
				throw new Error(data.error);
			}
			if (!data.checks) {
				throw new Error('Ungültige Datenstruktur: checks nicht vorhanden');
			}
			renderConditions(modal, data);
		})
		.catch(err => {
			console.error('Fehler beim Laden der Bedingungen:', err);
			modal.querySelector('.modal-body').innerHTML =
				'<p style="color: #dc3545;">Fehler: ' + err.message + '</p>';
		});
}

function renderConditions(modal, data) {
	const body = modal.querySelector('.modal-body');

	let html = '';

	// Overall status
	if (data.can_create_reminder) {
		html += '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 15px;">';
		html += '<h3 style="margin: 0 0 10px 0; color: #155724;">✅ MAHNUNG WÜRDE AUSGEGEBEN!</h3>';
	} else {
		html += '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px; margin-bottom: 15px;">';
		html += '<h3 style="margin: 0 0 10px 0; color: #721c24;">❌ MAHNUNG WIRD NICHT AUSGEGEBEN</h3>';
	}
	html += '<p style="margin: 0; font-size: 13px;">' + data.message + '</p></div>';

	// Detailed checks
	html += '<div style="text-align: left;">';
	html += '<h4 style="margin: 15px 0 10px 0; color: #333;">Bedingungen:</h4>';

	const checkOrder = ['open_amount', 'suppression', 'bill_age', 'reminder_spacing', 'recent_import'];
	checkOrder.forEach(key => {
		const check = data.checks[key];
		if (!check) return;

		const color = check.passed ? '#155724' : '#721c24';
		const bg = check.passed ? '#d4edda' : '#f8d7da';

		html += `<div style="background: ${bg}; padding: 10px; margin: 8px 0; border-radius: 3px; border-left: 4px solid ${color};">
			<div style="color: ${color}; font-weight: 600; font-size: 13px;">
				${check.message}
			</div>
		</div>`;
	});

	html += '</div>';

	body.innerHTML = html;
}

function renderHistory(reminders) {
	const container = document.getElementById('history-list');

	if (!reminders || reminders.length === 0) {
		container.innerHTML = '<p style="color: #999;">Keine Mahnungen vorhanden.</p>';
		return;
	}

	let html = '';
	reminders.forEach(reminder => {
		const createdDate = new Date(reminder.created_at).toLocaleString('de-DE');
		const sentDate = reminder.sent_at ? new Date(reminder.sent_at).toLocaleString('de-DE') : 'Nicht versendet';
		const stageName = ['', 'Zahlungserinnerung', 'Mahnung', 'Letzte Mahnung'][reminder.reminder_stage] || '-';

		html += `<div class="reminder-entry">
			<div class="reminder-entry-date">
				📅 ${createdDate}
				<span class="reminder-entry-stage">Stufe ${reminder.reminder_stage}</span>
			</div>
			<div style="margin-top: 5px;">
				<strong>${stageName}</strong><br>
				✉️ Versendet: ${sentDate}
				${reminder.email_address ? `<br>📧 ${escapeHtml(reminder.email_address)}` : ''}
			</div>
		</div>`;
	});

	container.innerHTML = html;
}

function openSuppressDialog(memberId) {
	const days = prompt('Mahnungen pausieren für wie viele Tage? (0 = unbegrenzt)', '14');
	if (days === null) return;

	let stopUntil = null;
	if (days && parseInt(days) > 0) {
		const date = new Date();
		date.setDate(date.getDate() + parseInt(days));
		stopUntil = date.toISOString().split('T')[0];
	}

	const params = new URLSearchParams();
	if (stopUntil) {
		params.append('stop_until', stopUntil);
	}

	fetch(OCA?.generateUrl?.(`/apps/weinsteigfinance/api/member/${memberId}/reminder-stop`) ||
		`/index.php/apps/weinsteigfinance/api/member/${memberId}/reminder-stop`,
		{
			method: 'POST',
			credentials: 'include',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString()
		})
		.then(r => r.json())
		.then(data => {
			if (data.success) {
				showNotification('✅ ' + data.message, 'success');
				loadRemindersData();
			} else {
				showNotification('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'), 'error');
			}
		})
		.catch(err => {
			showNotification('❌ Fehler: ' + err.message, 'error');
		});
}

function clearReminderStop(memberId) {
	if (confirm('Mahnstop aufheben?')) {
		fetch(OCA?.generateUrl?.(`/apps/weinsteigfinance/api/member/${memberId}/reminder-stop`) ||
			`/index.php/apps/weinsteigfinance/api/member/${memberId}/reminder-stop`,
			{
				method: 'POST',
				credentials: 'include',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ stop_until: null })
			})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					showNotification('✅ Mahnstop aufgehoben', 'success');
					loadRemindersData();
				} else {
					showNotification('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'), 'error');
				}
			})
			.catch(err => {
				showNotification('❌ Fehler: ' + err.message, 'error');
			});
	}
}

function setupEventListeners() {
	// Trigger manual generation button
	const triggerBtn = document.getElementById('trigger-now-btn');
	if (triggerBtn) {
		triggerBtn.addEventListener('click', triggerManualGeneration);
	}

	// History modal close button
	const historyCloseBtn = document.getElementById('history-modal-close');
	if (historyCloseBtn) {
		historyCloseBtn.addEventListener('click', function() {
			document.getElementById('history-modal').classList.remove('active');
		});
	}

	// Close modal on background click
	const historyModal = document.getElementById('history-modal');
	if (historyModal) {
		historyModal.addEventListener('click', function(e) {
			if (e.target === this) {
				this.classList.remove('active');
			}
		});
	}

	// Event delegation for reminder buttons
	const container = document.getElementById('reminders-container');
	if (container) {
		container.addEventListener('click', function(e) {
			const btn = e.target.closest('button[data-action]');
			if (!btn) return;

			const action = btn.dataset.action;
			const memberId = parseInt(btn.dataset.memberId);
			const address = btn.dataset.address;

			switch(action) {
				case 'create-reminder':
					if (!btn.disabled) createReminderManual(memberId);
					break;
				case 'open-history':
					openHistoryModal(memberId, address);
					break;
				case 'check-conditions':
					openConditionsModal(memberId, address);
					break;
				case 'open-suppress-dialog':
					openSuppressDialog(memberId);
					break;
				case 'clear-reminder-stop':
					clearReminderStop(memberId);
					break;
			}
		});
	}
}

function triggerManualGeneration() {
	if (confirm('Alle ausstehenden Mahnungen jetzt generieren und versenden?')) {
		// This would call a new API endpoint for bulk generation
		// For now, we inform the user it's automatic
		showNotification('⚙️ Mahnungen werden automatisch um 02:00 Uhr generiert. Oder erstelle einzelne Mahnungen per Haus.', 'info');
	}
}

function updateNextGenerationTime() {
	const now = new Date();
	const next = new Date(now);
	next.setDate(next.getDate() + 1);
	next.setHours(2, 0, 0, 0);

	const hoursUntil = Math.floor((next - now) / 3600000);
	const minutesUntil = Math.floor(((next - now) % 3600000) / 60000);

	document.getElementById('next-generation').textContent =
		next.toLocaleDateString('de-DE') + ' um ' + next.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }) +
		` (in ${hoursUntil}h ${minutesUntil}m)`;

	document.getElementById('status-text').textContent =
		'✅ Mahnungssystem aktiv - automatische Generierung täglich um 02:00 Uhr';
}

function escapeHtml(text) {
	const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
	return String(text).replace(/[&<>"']/g, m => map[m]);
}

function showNotification(message, type = 'info') {
	// Try modern Nextcloud API first (OCP.Toast)
	if (typeof OCP !== 'undefined' && OCP.Toast) {
		if (type === 'success') {
			OCP.Toast.success(message);
		} else if (type === 'error') {
			OCP.Toast.error(message);
		} else {
			OCP.Toast.info(message);
		}
	}
	// Fallback: use console
	else {
		console.log('[' + type.toUpperCase() + ']', message);
		alert(message);
	}
}
