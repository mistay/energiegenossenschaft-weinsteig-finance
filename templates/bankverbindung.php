<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
use OCP\Util;
Util::addScript('weinsteigfinance', 'iban-validator');
?>
<div id="weinsteigfinance-bankverbindung" class="app-weinsteigfinance">
	<h2><?php p($l->t('Bankverbindung verwalten')); ?></h2>

	<div id="members-list">
		<p><?php p($l->t('Lädt...')); ?></p>
	</div>

	<div id="edit-modal" style="display:none; margin-top: 20px; padding: 15px; border: 1px solid #ccc; background: #f5f5f5;">
		<h3><?php p($l->t('Bankverbindung für')); ?> <span id="edit-address"></span></h3>
		<form id="edit-form">
			<label>
				<?php p($l->t('Zahlungspflichtige Person:')); ?><br>
				<input type="text" id="edit-zahlungspflichtig" style="width: 300px; padding: 5px;" placeholder="Name">
			</label><br><br>
			<label>
				<?php p($l->t('IBAN:')); ?><br>
				<input type="text" id="edit-iban" style="width: 300px; padding: 5px;" placeholder="AT00 1234 5678 9012 3456">
				<span id="iban-status" style="margin-left: 10px; font-weight: bold;"></span>
			</label><br><br>
			<button type="button" id="save-btn"><?php p($l->t('Speichern')); ?></button>
			<button type="button" id="save-force-btn" style="background-color: #f39200;"><?php p($l->t('Trotzdem speichern')); ?></button>
			<button type="button" id="withdraw-btn" style="background-color: #dc3545;"><?php p($l->t('Mandat aufheben')); ?></button>
			<button type="button" id="cancel-btn"><?php p($l->t('Abbrechen')); ?></button>
		</form>
	</div>

	<div id="withdraw-modal" style="display:none; margin-top: 20px; padding: 15px; border: 1px solid #cc0000; background: #ffe0e0;">
		<h3><?php p($l->t('Mandat aufheben für')); ?> <span id="withdraw-address"></span></h3>
		<p style="color: #cc0000;"><?php p($l->t('Warnung: Dies kann nicht rückgängig gemacht werden.')); ?></p>
		<label>
			<?php p($l->t('Grund:')); ?><br>
			<textarea id="withdraw-reason" style="width: 300px; height: 80px; padding: 5px;"></textarea>
		</label><br><br>
		<button type="button" id="withdraw-confirm-btn" style="background-color: #dc3545;"><?php p($l->t('Wirklich aufheben')); ?></button>
		<button type="button" id="withdraw-cancel-btn"><?php p($l->t('Abbrechen')); ?></button>
	</div>
</div>
