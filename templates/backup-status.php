<?php
declare(strict_types=1);
style('weinsteigfinance', 'style');
script('weinsteigfinance', 'backup-status');
$currentPage = 'backup-status';
?>
<div id="weinsteigfinance-backup-status" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h1 style="margin-bottom: 30px;">💾 Backup-Status</h1>

	<div style="background: #e3f2fd; border-left: 4px solid #0082c9; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
		<p style="margin: 0; color: #0082c9;">
			<strong>ℹ️ Hinweis:</strong> Du hast Zugriff auf diese Funktion, weil dieses Nutzerkonto in der Gruppe <strong>👑 obpersonen</strong> geführt wird.
		</p>
	</div>

	<div style="margin-bottom: 20px;">
		<button id="create-backup-btn" style="
			padding: 12px 24px;
			font-size: 16px;
			background: #28a745;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-weight: 600;
		">
			⚡ Backup jetzt erstellen
		</button>
		<span id="create-status" style="margin-left: 12px; vertical-align: middle;"></span>
	</div>

	<div style="background: #e8f5e9; border-left: 4px solid #28a745; padding: 20px; border-radius: 4px; margin-bottom: 30px; max-width: 900px;">
		<p style="margin: 0; color: #2e7d32;">
			<strong>✅ Automatische Backups:</strong><br>
			Die Datenbank wird täglich um 02:00 Uhr automatisch gesichert.
			Alle Backups werden im Ordner <code style="background: #f0f0f0; padding: 2px 4px;">/data/backup/</code> gespeichert.
		</p>
	</div>

	<div id="status-container" style="max-width: 900px;">
		<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
			<h2 style="margin-top: 0; font-size: 18px; margin-bottom: 24px;">📊 Status</h2>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
				<div style="background: #f0f7ff; border: 1px solid #0082c9; border-radius: 4px; padding: 16px;">
					<div style="font-size: 12px; color: #0082c9; font-weight: 600; margin-bottom: 8px;">🕐 LETZTES BACKUP</div>
					<div style="font-size: 20px; font-weight: bold; color: #333;" id="last-backup">—</div>
				</div>

				<div style="background: #e8f5e9; border: 1px solid #28a745; border-radius: 4px; padding: 16px;">
					<div style="font-size: 12px; color: #28a745; font-weight: 600; margin-bottom: 8px;">⏱️ NÄCHSTES BACKUP</div>
					<div style="font-size: 20px; font-weight: bold; color: #333;" id="next-backup">—</div>
				</div>
			</div>

			<div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 16px;">
				<div style="font-size: 12px; color: #856404; font-weight: 600; margin-bottom: 8px;">⏳ VERBLEIBENDE ZEIT</div>
				<div style="font-size: 24px; font-weight: bold; color: #856404;" id="remaining-time">—</div>
			</div>
		</div>

		<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0; font-size: 18px; margin-bottom: 16px;">📥 Verfügbare Backups</h2>

			<div id="backups-list" style="overflow-x: auto;">
				<table style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="background: #f5f5f5;">
							<th style="text-align: left; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600;">Dateiname</th>
							<th style="text-align: left; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600;">Größe</th>
							<th style="text-align: center; padding: 12px; border-bottom: 2px solid #ddd; font-weight: 600;">Aktion</th>
						</tr>
					</thead>
					<tbody id="backups-tbody">
						<tr><td colspan="3" style="text-align: center; padding: 20px; color: #999;">lädt...</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
