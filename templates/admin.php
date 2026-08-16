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

	<div id="config-box" style="margin-bottom: 25px;">
		<div style="margin-bottom: 6px;">
			<label>
				<?php p($l->t('Creditor ID:')); ?>
				<input type="text" id="creditor-id" placeholder="AT00ZZZ00000000000" style="width: 220px;">
			</label>
		</div>
		<div style="margin-bottom: 6px;">
			<label>
				<?php p($l->t('IBAN:')); ?>
				<input type="text" id="creditor-iban" placeholder="AT00 0000 0000 0000 0000" style="width: 220px;">
			</label>
		</div>
		<div style="margin-bottom: 6px;">
			<label>
				<?php p($l->t('BIC:')); ?>
				<input type="text" id="creditor-bic" placeholder="GIBAATWWXXX" style="width: 220px;">
			</label>
		</div>
		<button id="config-save"><?php p($l->t('Speichern')); ?></button>
		<span id="config-status" style="margin-left: 10px;"></span>
	</div>
</div>
