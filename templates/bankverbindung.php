<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
use OCP\Util;
Util::addScript('weinsteigfinance', 'iban-validator');
$currentPage = 'bankverbindung';
?>
<div id="weinsteigfinance-bankverbindung" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2><?php p($l->t('Bankverbindung verwalten')); ?></h2>

	<div id="members-list">
		<p><?php p($l->t('Lädt...')); ?></p>
	</div>

	<div id="edit-modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>
	<div id="edit-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 90%; max-height: 90vh; overflow-y: auto; width: 400px;">
		<h3><?php p($l->t('Bankverbindung für')); ?> <span id="edit-address"></span></h3>
		<form id="edit-form">
			<label style="display: block; margin-bottom: 12px;">
				<?php p($l->t('Zahlungspflichtige Person:')); ?><br>
				<input type="text" id="edit-zahlungspflichtig" style="width: 100%; padding: 8px; box-sizing: border-box; margin-top: 4px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Name">
			</label>
			<label style="display: block; margin-bottom: 12px;">
				<?php p($l->t('IBAN:')); ?><br>
				<input type="text" id="edit-iban" style="width: 100%; padding: 8px; box-sizing: border-box; margin-top: 4px; border: 1px solid #ddd; border-radius: 4px;" placeholder="AT00 1234 5678 9012 3456">
				<span id="iban-status" style="display: block; margin-top: 4px; font-weight: bold;"></span>
			</label>
			<div style="display: flex; gap: 8px; flex-wrap: wrap;">
				<button type="button" id="save-btn" style="flex: 1; padding: 8px 12px; background: #0082c9; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php p($l->t('Speichern')); ?></button>
				<button type="button" id="save-force-btn" style="flex: 1; padding: 8px 12px; background: #f39200; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php p($l->t('Trotzdem speichern')); ?></button>
			</div>
			<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
				<button type="button" id="withdraw-btn" style="flex: 1; padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php p($l->t('Mandat aufheben')); ?></button>
				<button type="button" id="cancel-btn" style="flex: 1; padding: 8px 12px; background: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer;"><?php p($l->t('Abbrechen')); ?></button>
			</div>
		</form>
	</div>

	<div id="withdraw-modal-backdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>
	<div id="withdraw-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 90%; max-height: 90vh; overflow-y: auto; width: 400px;">
		<h3><?php p($l->t('Mandat aufheben für')); ?> <span id="withdraw-address"></span></h3>
		<p style="color: #cc0000;"><?php p($l->t('Warnung: Dies kann nicht rückgängig gemacht werden.')); ?></p>
		<label style="display: block; margin-bottom: 12px;">
			<?php p($l->t('Grund:')); ?><br>
			<textarea id="withdraw-reason" style="width: 100%; height: 80px; padding: 8px; box-sizing: border-box; margin-top: 4px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
		</label>
		<div style="display: flex; gap: 8px;">
			<button type="button" id="withdraw-confirm-btn" style="flex: 1; padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;"><?php p($l->t('Wirklich aufheben')); ?></button>
			<button type="button" id="withdraw-cancel-btn" style="flex: 1; padding: 8px 12px; background: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer;"><?php p($l->t('Abbrechen')); ?></button>
		</div>
	</div>
</div>
