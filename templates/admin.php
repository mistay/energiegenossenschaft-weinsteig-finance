<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
/** @var array $_ */
$currentPage = 'admin';
?>
<div id="weinsteigfinance-admin" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2><?php p($l->t('Einstellungen')); ?></h2>

	<h3><?php p($l->t('SEPA-Konfiguration')); ?></h3>
	<p><?php p($l->t('Bankverbindung der Genossenschaft und Gläubiger-Identifikationsnummer für SEPA-Mandate.')); ?></p>

	<div id="config-box" style="margin-bottom: 25px; max-width: 400px;">
		<div style="margin-bottom: 12px;">
			<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">
				<?php p($l->t('Creditor ID:')); ?>
			</label>
			<input type="text" id="creditor-id" placeholder="AT00ZZZ00000000000" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
		</div>
		<div style="margin-bottom: 12px;">
			<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">
				<?php p($l->t('IBAN:')); ?>
			</label>
			<input type="text" id="creditor-iban" placeholder="AT00 0000 0000 0000 0000" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
		</div>
		<div style="margin-bottom: 12px;">
			<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">
				<?php p($l->t('BIC:')); ?>
			</label>
			<input type="text" id="creditor-bic" placeholder="GIBAATWWXXX" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
		</div>
		<div style="display: flex; gap: 10px; flex-wrap: wrap;">
			<button id="config-save" style="padding: 8px 16px; background: #0082c9; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 500; transition: background 0.2s;">Speichern</button>
			<span id="config-status" style="align-self: center;"></span>
		</div>
	</div>

	<hr style="margin: 35px 0; border: none; border-top: 1px solid #ddd;">

	<h3><?php p($l->t('Mahnstufen-Texte')); ?></h3>
	<p><?php p($l->t('Bearbeiten Sie die Texte für die automatisierten Mahnstufen (2-stufiges System).')); ?></p>

	<div id="reminder-texts-box" style="margin-bottom: 25px; max-width: 800px;">
		<!-- Stage 1: Zahlungserinnerung -->
		<div style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 3px;">
			<h4 style="margin-top: 0; color: #333;">🟢 Stufe 1: Zahlungserinnerung</h4>
			<div style="margin-bottom: 10px;">
				<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">Betreff:</label>
				<input type="text" id="reminder-subject-1" placeholder="Zahlungserinnerung" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
			</div>
			<div style="margin-bottom: 10px;">
				<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">Nachrichtentext:</label>
				<textarea id="reminder-body-1" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-family: monospace; min-height: 150px; resize: vertical;"></textarea>
				<small style="color: #666; display: block; margin-top: 4px;">Platzhalter: {name}, {address}, {amount}, {duedate}</small>
			</div>
			<button class="reminder-save-btn" data-stage="1" style="padding: 8px 16px; background: #0082c9; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 500; transition: background 0.2s;">Speichern</button>
			<span class="reminder-status" data-stage="1" style="margin-left: 10px; display: none;"></span>
		</div>

		<!-- Stage 2: Mahnung -->
		<div style="margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; border-radius: 3px;">
			<h4 style="margin-top: 0; color: #333;">🔴 Stufe 2: Mahnung (Letzte)</h4>
			<div style="margin-bottom: 10px;">
				<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">Betreff:</label>
				<input type="text" id="reminder-subject-2" placeholder="Mahnung" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box;">
			</div>
			<div style="margin-bottom: 10px;">
				<label style="display: block; margin-bottom: 4px; font-weight: 500; color: #333;">Nachrichtentext:</label>
				<textarea id="reminder-body-2" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; box-sizing: border-box; font-family: monospace; min-height: 150px; resize: vertical;"></textarea>
				<small style="color: #666; display: block; margin-top: 4px;">Platzhalter: {name}, {address}, {amount}, {duedate}</small>
			</div>
			<button class="reminder-save-btn" data-stage="2" style="padding: 8px 16px; background: #0082c9; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 500; transition: background 0.2s;">Speichern</button>
			<span class="reminder-status" data-stage="2" style="margin-left: 10px; display: none;"></span>
		</div>
	</div>
</div>
