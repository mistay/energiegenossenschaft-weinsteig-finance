<?php
declare(strict_types=1);
/** @var array $_ */
/** @var \OCP\IL10N $l */
$currentPage = 'zahlungen-uebersicht';
?>

<div id="zahlungen-uebersicht-container" style="padding: 20px; max-width: 1200px; margin: 0 auto;">
	<?php include 'nav.php'; ?>

	<h2>Meine Zahlungen</h2>

	<!-- Statistik Box -->
	<div id="stats-box" style="background: #f0f8ff; padding: 15px; border-radius: 3px; margin-bottom: 20px; display: none;">
		<strong>📊 Statistik:</strong>
		<span>Gesamt: <strong id="stat-gesamt">0</strong> €</span> |
		<span>Zugeordnet: <strong id="stat-zugeordnet" style="color: #28a745;">0</strong> €</span> |
		<span>Unzugeordnet: <strong id="stat-unzugeordnet" style="color: #ff9800;">0</strong> €</span>
	</div>

	<!-- Monatlicher Filter -->
	<div style="margin-bottom: 20px;">
		<label for="month-filter" style="font-weight: bold;">Monat:</label>
		<select id="month-filter" style="padding: 8px; border: 1px solid #ccc; border-radius: 3px; margin-left: 10px;">
			<option value="">-- Alle Monate --</option>
		</select>
	</div>

	<!-- Zahlungen Tabelle -->
	<div id="zahlungen-list">
		<p style="color: #999;">Lädt...</p>
	</div>
</div>
