<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
?>
<div id="weinsteigfinance" class="app-weinsteigfinance">
	<h2>Hello, Weinsteig!</h2>
	<p><?php p($l->t('Die App läuft. Hier entstehen Vorschreibungen, SEPA-Mandate und Zahlungen.')); ?></p>
	<hr>
	<ul>
		<li><a href="/index.php/apps/weinsteigfinance/admin"><?php p($l->t('Admin: Mitglieder & Benutzer')); ?></a></li>
		<li><a href="/index.php/apps/weinsteigfinance/bankverbindung"><?php p($l->t('Bankverbindung verwalten')); ?></a></li>
		<li><a href="/index.php/apps/weinsteigfinance/vorschreibungen"><?php p($l->t('Vorschreibungen')); ?></a></li>
		<li><a href="/index.php/apps/weinsteigfinance/zahlungen"><?php p($l->t('Zahlungen')); ?></a></li>
		<li><a href="/index.php/apps/weinsteigfinance/zahlungen-uebersicht"><?php p($l->t('Meine Zahlungen')); ?></a></li>
	</ul>
</div>
