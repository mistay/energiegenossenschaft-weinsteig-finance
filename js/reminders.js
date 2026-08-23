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
				<strong>${openAmount.toFixed(2)}€</strong>
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
					onclick="createReminderManual(${member.id})">
					💬 Mahnung
				</button>
				<button class="reminder-button" onclick="openHistoryModal(${member.id}, '${escapeHtml(member.address)}')">
					📋 Verlauf
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
		return `<button class="reminder-button reminder-button-danger" onclick="clearReminderStop(${memberId})">
			🔓 Stop aufheben (bis ${date})
		</button>`;
	} else {
		return `<button class="reminder-button" onclick="openSuppressDialog(${memberId})">
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
	const triggerBtn = document.getElementById('trigger-now-btn');
	if (triggerBtn) {
		triggerBtn.addEventListener('click', triggerManualGeneration);
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
