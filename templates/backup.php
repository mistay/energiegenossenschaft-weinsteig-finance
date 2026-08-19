<?php
declare(strict_types=1);
style('weinsteigfinance', 'style');
script('weinsteigfinance', 'backup');

// Set current page for nav.php
$currentPage = 'backup';
?>

<?php require_once __DIR__ . '/nav.php'; ?>

<div style="max-width: 800px; margin: 0 auto; padding: 20px;">
	<h1 style="margin-bottom: 30px;">💾 Datenbank-Backup</h1>

	<div style="background: #e3f2fd; border-left: 4px solid #0082c9; padding: 20px; border-radius: 4px; margin-bottom: 30px;">
		<p style="margin: 0; color: #0082c9;">
			<strong>Sicherung aller Daten</strong><br>
			Exportieren Sie die gesamte Datenbank als JSON-Datei für Sicherungszwecke.
			Die Datei enthält alle Häuser, Benutzer, Vorschreibungen, Zahlungen und Mandate.
		</p>
	</div>

	<div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
		<h2 style="margin-top: 0; font-size: 18px; margin-bottom: 16px;">📦 Backup herunterladen</h2>

		<p style="color: #666; margin-bottom: 24px;">
			Klicken Sie auf den Button unten, um ein Backup der kompletten Datenbank herunterzuladen.
		</p>

		<button id="export-btn" style="
			padding: 12px 24px;
			font-size: 16px;
			background: #0082c9;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-weight: 600;
		">
			💾 Backup jetzt herunterladen
		</button>

		<p style="font-size: 12px; color: #999; margin-top: 16px;">
			<span id="status"></span>
		</p>
	</div>

	<div style="background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-top: 30px;">
		<h3 style="margin-top: 0;">📋 Im Backup enthaltene Tabellen:</h3>
		<ul style="margin: 0; padding-left: 20px; color: #666; font-size: 14px;">
			<li>Mitglieder (Häuser)</li>
			<li>Benutzer-Zuordnungen</li>
			<li>Vorschreibungen (Rechnungen)</li>
			<li>Zahlungen</li>
			<li>Zahlungs-Vorschreibungs-Zuordnungen</li>
			<li>Konfiguration (SEPA-Daten)</li>
			<li>Mandat-Genehmigungen</li>
		</ul>
	</div>

	<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 4px; margin-top: 30px;">
		<p style="margin: 0; color: #856404;">
			<strong>⚠️ Hinweis:</strong> Backups sollten regelmäßig gemacht und an einem sicheren Ort gespeichert werden.
			Hochgeladene Mandate-PDFs sind in diesem Backup nicht enthalten (diese werden separat im Dateisystem gespeichert).
		</p>
	</div>
</div>
