<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
/** @var array $_ */
$currentPage = 'admin';
?>
<div id="weinsteigfinance-admin" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2><?php p($l->t('Einstellungen')); ?></h2>
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
</div>
