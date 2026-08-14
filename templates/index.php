<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
?>
<div id="weinsteigfinance" class="app-weinsteigfinance">
	<h2>Hello, Weinsteig!</h2>
	<p><?php p($l->t('Die App läuft. Hier entstehen Vorschreibungen, SEPA-Mandate und Zahlungen.')); ?></p>
	<hr>
	<p><a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('weinsteigfinance.page.admin')); ?>"><?php p($l->t('→ Admin: Mitglieder verwalten')); ?></a></p>
</div>
