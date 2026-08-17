document.addEventListener('DOMContentLoaded', function() {
	const container = document.getElementById('sepa-container');

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function load() {
		// Get user groups and SEPA data in parallel
		Promise.all([
			fetch(OC.generateUrl('/apps/weinsteigfinance/api/my-groups')).then(r => r.json()),
			fetch(OC.generateUrl('/apps/weinsteigfinance/api/sepa-datentraeger')).then(r => r.json())
		])
			.then(([userInfo, data]) => {
				if (data.error) {
					container.innerHTML = '<p style="color: red;">Fehler: ' + escapeHtml(data.error) + '</p>';
					return;
				}

				const mandates = data.mandates || [];
				const userGroups = userInfo.groups || [];

				let html = '';

				// Info-Box für kassier:innen
				const authorizedGroups = ['obpersonen', 'kassier:innen'];
				const hasAuthorization = userGroups.some(g => authorizedGroups.includes(g));
				if (hasAuthorization && userGroups.length > 0) {
					const groupIcons = {
						'obpersonen': '👑',
						'kassier:innen': '💰'
					};
					const visibleGroups = userGroups
						.filter(g => authorizedGroups.includes(g))
						.map(g => (groupIcons[g] || '') + ' ' + g)
						.join(', ');

					if (visibleGroups) {
						html += '<div style="background: #e3f2fd; border-left: 4px solid #0082c9; padding: 16px; border-radius: 4px; margin-bottom: 20px; color: #0082c9;">';
						html += '<strong>ℹ️ Hinweis:</strong> Es werden alle SEPA Datenträger angezeigt, weil dieses Nutzerkonto in der Gruppe ' + escapeHtml(visibleGroups) + ' geführt wird.';
						html += '</div>';
					}
				}

				// Info-Box
				html += '<div class="info-box">';
				html += '💡 <strong>SEPA-Datenträger</strong> – Übersicht aller gültigen SEPA-Mandate und offenen Beträge in Kundenkonten';
				html += '</div>';

				// Export Button
				html += '<button id="export-btn" class="export-btn">📥 Als CSV exportieren</button>';

				// Statistiken
				if (mandates.length > 0) {
					let totalOpen = 0;
					let openCount = 0;
					mandates.forEach(m => {
						totalOpen += m.open_amount;
						if (m.open_amount > 0) openCount++;
					});

					html += '<div class="stats-box">';
					html += '<div class="stat-item">';
					html += '<div class="stat-label">Gültige Mandate</div>';
					html += '<div class="stat-value">' + mandates.length + '</div>';
					html += '</div>';
					html += '<div class="stat-item">';
					html += '<div class="stat-label">Mit offenen Beträgen</div>';
					html += '<div class="stat-value">' + openCount + '</div>';
					html += '</div>';
					html += '<div class="stat-item">';
					html += '<div class="stat-label">Total offene Beträge</div>';
					html += '<div class="stat-value">' + totalOpen.toFixed(2) + ' €</div>';
					html += '</div>';
					html += '</div>';
				}

				// Mandate als Cards
				if (mandates.length === 0) {
					html += '<p style="text-align: center; color: #999; padding: 20px;">Keine gültigen Mandate gefunden</p>';
				} else {
					html += '<div id="sepa-cards">';
					mandates.forEach(m => {
						const amountClass = m.open_amount > 0 ? 'amount-negative' : (m.open_amount < 0 ? 'amount-positive' : 'amount-zero');
						const amountText = m.open_amount.toFixed(2) + ' €';
						const amountIcon = m.open_amount > 0 ? '⚠️' : '✓';

						html += '<div class="sepa-card" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">';

						// Header: Haus
						html += '<div style="background: #0082c9; color: white; padding: 12px 14px; font-weight: bold; font-size: 14px;">📍 ' + escapeHtml(m.address) + '</div>';

						// Body: Info-Zeilen
						html += '<div style="padding: 12px 14px;">';

						// Kontoinhaber
						html += '<div style="display: grid; grid-template-columns: 120px 1fr; gap: 12px; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee;">';
						html += '<span style="color: #666; font-size: 12px; font-weight: 500;">Kontoinhaber</span>';
						html += '<span style="color: #333; font-size: 13px;">' + escapeHtml(m.zahlungspflichtig) + '</span>';
						html += '</div>';

						// IBAN
						html += '<div style="display: grid; grid-template-columns: 120px 1fr; gap: 12px; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; word-break: break-all;">';
						html += '<span style="color: #666; font-size: 12px; font-weight: 500;">IBAN</span>';
						html += '<code style="background: #f5f5f5; padding: 4px 6px; border-radius: 3px; font-size: 12px; font-family: monospace;">' + escapeHtml(m.iban) + '</code>';
						html += '</div>';

						// Mandat
						html += '<div style="display: grid; grid-template-columns: 120px 1fr; gap: 12px; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee;">';
						html += '<span style="color: #666; font-size: 12px; font-weight: 500;">Mandat seit</span>';
						html += '<span style="color: #333; font-size: 13px;">' + escapeHtml(m.mandate_granted_date) + '</span>';
						html += '</div>';

						// Offene Beträge (Highlight)
						html += '<div style="display: grid; grid-template-columns: 120px 1fr; gap: 12px; align-items: center; padding: 8px 0;">';
						html += '<span style="color: #666; font-size: 12px; font-weight: 500;">Offene Beträge</span>';
						html += '<span style="font-size: 14px; font-weight: bold;">' + amountIcon + ' <span class="' + amountClass + '" style="padding: 2px 6px; border-radius: 3px;">' + amountText + '</span></span>';
						html += '</div>';

						html += '</div>';
						html += '</div>';
					});
					html += '</div>';
				}
				container.innerHTML = html;

				// Export Button Handler
				document.getElementById('export-btn').addEventListener('click', function() {
					window.location.href = OC.generateUrl('/apps/weinsteigfinance/api/sepa-datentraeger/export');
				});
			})
			.catch(err => {
				container.innerHTML = '<p style="color: red;">Fehler beim Laden: ' + escapeHtml(err.message) + '</p>';
			});
	}

	load();
});
