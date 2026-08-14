<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'journal';
?>

<div id="weinsteigfinance-journal" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>Kontojurnal</h2>

	<!-- Status Message -->
	<div id="status-message" style="padding: 16px; border-radius: 6px; margin-bottom: 20px; display: none; font-weight: 500; border-left: 4px solid;">
	</div>

	<!-- Statistik Box -->
	<div id="stats-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 5px; margin-bottom: 30px; display: none;">
		<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
			<div>
				<div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">💰 Kontosaldo</div>
				<div style="font-size: 28px; font-weight: bold;" id="stat-saldo">0,00 €</div>
			</div>
			<div>
				<div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">📋 Offene Vorschreibungen</div>
				<div style="font-size: 28px; font-weight: bold; color: #ffb81c;" id="stat-open">0,00 €</div>
			</div>
			<div>
				<div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">✓ Eingegangene Zahlungen</div>
				<div style="font-size: 28px; font-weight: bold; color: #4caf50;" id="stat-zahlungen">0,00 €</div>
			</div>
		</div>
	</div>

	<!-- Vorschreibungen Tabelle -->
	<div id="vorschreibungen-section" style="margin-bottom: 40px; display: none;">
		<h3 style="border-bottom: 2px solid #0082c9; padding-bottom: 10px;">📋 Vorschreibungen</h3>
		<table id="vorschreibungen-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
			<thead style="background: #f5f5f5;">
				<tr>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Zeitraum</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Betrag</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: center;">Status</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Erstellt</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	<!-- Zahlungen Tabelle -->
	<div id="zahlungen-section" style="margin-bottom: 40px; display: none;">
		<h3 style="border-bottom: 2px solid #28a745; padding-bottom: 10px;">💳 Zahlungseingänge</h3>
		<table id="zahlungen-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
			<thead style="background: #f5f5f5;">
				<tr>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Valutadatum</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Partner</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Zweck</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: right;">Betrag</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: center;">Status</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>

	<div id="error-message" style="color: red; padding: 15px; background: #ffe6e6; border-radius: 3px; display: none;"></div>
	<div id="loading" style="color: #666; padding: 20px; text-align: center;">⏳ Lädt...</div>
</div>

<style>
	#vorschreibungen-table tbody tr:nth-child(odd) { background: #fafafa; }
	#vorschreibungen-table tbody tr:hover { background: #f0f0f0; }
	#zahlungen-table tbody tr:nth-child(odd) { background: #fafafa; }
	#zahlungen-table tbody tr:hover { background: #f0f0f0; }

	.status-open { color: #ff9800; font-weight: bold; }
	.status-paid { color: #28a745; font-weight: bold; }
	.status-matched { color: #28a745; font-weight: bold; }
	.status-pending { color: #ff9800; font-weight: bold; }
</style>
