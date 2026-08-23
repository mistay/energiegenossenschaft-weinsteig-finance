<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'reminders';
script('weinsteigfinance', 'reminders');
?>

<div id="weinsteigfinance-reminders" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>💬 Mahnmanagement</h2>

	<!-- Process Explanation -->
	<div id="process-info" style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
		<div style="display: flex; gap: 20px; flex-wrap: wrap;">
			<div style="flex: 1; min-width: 250px;">
				<h3 style="margin: 0 0 10px 0; color: #2e7d32;">📊 Wie das System funktioniert</h3>
				<ol style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.6; color: #333;">
					<li><strong>Automatische Prüfung</strong> täglich um 02:00 Uhr
						<ul style="margin: 5px 0; font-size: 12px;">
							<li>✓ Kontoauszug muss < 7 Tage alt sein</li>
							<li>✓ Offener Betrag ≥ 10€</li>
							<li>✓ Rechnung > 30 Tage alt</li>
							<li>✓ Letzte Mahnung > 14 Tage her</li>
						</ul>
					</li>
					<li><strong>Mahnung erstellen</strong> (Stufe 1 → 2 → 3)
						<ul style="margin: 5px 0; font-size: 12px;">
							<li>🟡 Stufe 1: Zahlungserinnerung (nett)</li>
							<li>🔴 Stufe 2: Mahnung (ernster)</li>
							<li>⛔ Stufe 3: Letzte Mahnung (final)</li>
						</ul>
					</li>
					<li><strong>Email versendet</strong> mit entsprechendem Text</li>
					<li><strong>Verlauf gespeichert</strong> (wann, welche Stufe, Email)</li>
				</ol>
			</div>
			<div style="flex: 1; min-width: 250px;">
				<h3 style="margin: 0 0 10px 0; color: #2e7d32;">⚙️ Manuelle Funktionen</h3>
				<div style="font-size: 13px; line-height: 1.8; color: #333;">
					<p style="margin: 0 0 8px 0;">
						<strong>💬 Mahnung</strong><br>
						Erstelle sofort eine Mahnung für ein Haus.<br>
						<em style="color: #666; font-size: 12px;">Springt zur nächsten Stufe, auch wenn Bedingungen nicht erfüllt.</em>
					</p>
					<p style="margin: 8px 0;">
						<strong>📋 Verlauf</strong><br>
						Zeige alle bisherigen Mahnungen<br>
						<em style="color: #666; font-size: 12px;">Datum, Stufe, Email-Adresse.</em>
					</p>
					<p style="margin: 8px 0;">
						<strong>🔇 Stop</strong><br>
						Pausiere Mahnungen für dieses Haus<br>
						<em style="color: #666; font-size: 12px;">Für N Tage oder unbegrenzt.</em>
					</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Info Box -->
	<div id="status-box" style="background: #f0f8ff; border-left: 4px solid #0082c9; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
		<p style="margin: 0; color: #333; font-weight: 600;">
			ℹ️ <span id="status-text">Lädt...</span>
		</p>
		<p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">
			🔄 Nächste automatische Mahnungs-Generierung: <span id="next-generation" style="font-weight: bold;">--</span>
		</p>
		<button id="trigger-now-btn" class="button" style="margin-top: 15px; background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 3px; cursor: pointer; font-weight: 500;">
			↻ Jetzt manuell auslösen
		</button>
	</div>

	<!-- Reminders Table -->
	<div id="reminders-container" style="margin-top: 20px;">
		<p style="color: #999;">Lädt...</p>
	</div>
</div>

<style>
.reminder-row {
	display: table;
	width: 100%;
	border-bottom: 1px solid #ddd;
	padding: 15px 0;
}

.reminder-cell {
	display: table-cell;
	padding: 10px 15px;
	vertical-align: middle;
}

.reminder-address {
	font-weight: 600;
	min-width: 200px;
}

.reminder-amount {
	font-family: monospace;
	text-align: right;
	min-width: 120px;
}

.reminder-status {
	text-align: center;
	min-width: 140px;
	font-weight: 600;
}

.reminder-actions {
	text-align: right;
	min-width: 250px;
}

.reminder-badge {
	display: inline-block;
	padding: 5px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
}

.badge-none {
	background: #d4edda;
	color: #155724;
}

.badge-stage1 {
	background: #fff3cd;
	color: #856404;
}

.badge-stage2 {
	background: #f8d7da;
	color: #721c24;
}

.badge-stage3 {
	background: #f5c6cb;
	color: #721c24;
}

.reminder-button {
	background: #0082c9;
	color: white;
	border: none;
	padding: 6px 12px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 12px;
	margin: 0 3px;
	transition: background 0.2s;
}

.reminder-button:hover {
	background: #0070a8;
}

.reminder-button-danger {
	background: #dc3545;
}

.reminder-button-danger:hover {
	background: #c82333;
}

.reminder-button-disabled {
	background: #ccc;
	cursor: not-allowed;
	opacity: 0.6;
}

.reminder-stop-input {
	padding: 6px 10px;
	border: 1px solid #ddd;
	border-radius: 3px;
	font-size: 12px;
	width: 140px;
}

.header-row {
	display: table;
	width: 100%;
	background: #f5f5f5;
	border-bottom: 2px solid #ddd;
	padding: 0;
	font-weight: 600;
	margin-bottom: 10px;
}

.header-cell {
	display: table-cell;
	padding: 12px 15px;
	font-size: 13px;
	color: #333;
}

.modal {
	display: none;
	position: fixed;
	z-index: 1000;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	background-color: rgba(0, 0, 0, 0.4);
	border-radius: 4px;
}

.modal.active {
	display: block;
}

.modal-content {
	background-color: white;
	margin: 10% auto;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 4px;
	width: 80%;
	max-width: 500px;
	max-height: 80vh;
	overflow-y: auto;
}

.modal-header {
	font-size: 18px;
	font-weight: 600;
	margin-bottom: 15px;
	border-bottom: 1px solid #ddd;
	padding-bottom: 10px;
}

.modal-body {
	margin: 15px 0;
}

.modal-footer {
	border-top: 1px solid #ddd;
	padding-top: 15px;
	text-align: right;
}

.modal-close {
	background: #6c757d;
	color: white;
	border: none;
	padding: 8px 16px;
	border-radius: 3px;
	cursor: pointer;
	font-weight: 500;
}

.modal-close:hover {
	background: #5a6268;
}

.reminder-entry {
	padding: 10px;
	margin: 5px 0;
	background: #f9f9f9;
	border-left: 4px solid #0082c9;
	border-radius: 3px;
	font-size: 12px;
}

.reminder-entry-date {
	color: #666;
	font-weight: 600;
}

.reminder-entry-stage {
	display: inline-block;
	margin-left: 10px;
	padding: 2px 8px;
	border-radius: 3px;
	background: #e7f3ff;
	color: #0082c9;
	font-weight: 600;
}

@media (max-width: 768px) {
	.reminder-cell {
		display: block;
		padding: 8px 0;
		border-bottom: 1px solid #eee;
	}

	.reminder-address::before {
		content: "Haus: ";
		font-weight: 600;
	}

	.reminder-actions {
		margin-top: 10px;
	}
}
</style>

<!-- History Modal -->
<div id="history-modal" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			📋 Mahnhistorie - <span id="history-address">--</span>
		</div>
		<div class="modal-body" id="history-list">
			Lädt...
		</div>
		<div class="modal-footer">
			<button class="modal-close" id="history-modal-close">
				Schließen
			</button>
		</div>
	</div>
</div>
