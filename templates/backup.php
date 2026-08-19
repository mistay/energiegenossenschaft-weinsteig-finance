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
		<h2 style="margin-top: 0; font-size: 18px; margin-bottom: 16px;">📦 Vollständiges Backup herunterladen</h2>

		<p style="color: #666; margin-bottom: 24px;">
			Laden Sie ein ZIP-Archiv herunter, das die komplette Datenbank und alle hochgeladenen Mandate-Dateien enthält.
			Dieses Backup kann direkt zur Wiederherstellung verwendet werden.
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
		<h3 style="margin-top: 0;">📦 Im ZIP-Backup enthalten:</h3>
		<ul style="margin: 0; padding-left: 20px; color: #666; font-size: 14px;">
			<li><strong>database.sql</strong> - MySQL SQL-Dump aller Tabellen</li>
			<li><strong>generated/</strong> - Gesamte Verzeichnis-Struktur mit hochgeladenen Mandaten</li>
		</ul>
		<p style="margin: 12px 0 0 0; color: #666; font-size: 13px;">
			Die Struktur entspricht exakt dem Nextcloud Dateiverzeichnis, sodass das Backup direkt wiederhergestellt werden kann.
		</p>
	</div>

	<div style="background: #e8f5e9; border-left: 4px solid #28a745; padding: 20px; border-radius: 4px; margin-top: 30px;">
		<h4 style="margin-top: 0; color: #2e7d32;">✅ Wie Sie das Backup wiederherstellen:</h4>
		<ol style="margin: 0; padding-left: 20px; color: #2e7d32; font-size: 13px;">
			<li>ZIP-Datei extrahieren</li>
			<li>SQL einspielen: <code style="background: #f0f0f0; padding: 2px 4px; border-radius: 2px;">mysql &lt; database.sql</code></li>
			<li>Verzeichnis <code style="background: #f0f0f0; padding: 2px 4px; border-radius: 2px;">generated/</code> nach <code style="background: #f0f0f0; padding: 2px 4px; border-radius: 2px;">/var/www/nextcloud/data/</code> kopieren</li>
			<li>Fertig! ✅</li>
		</ol>
	</div>

	<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 4px; margin-top: 30px;">
		<p style="margin: 0; color: #856404;">
			<strong>⚠️ Empfehlung:</strong> Erstellen Sie regelmäßig Backups und speichern Sie diese an einem sicheren Ort.
			Testen Sie die Wiederherstellung in regelmäßigen Abständen!
		</p>
	</div>
</div>
