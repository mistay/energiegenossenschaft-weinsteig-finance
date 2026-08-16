<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
$currentPage = 'admin-members';
?>
<div id="weinsteigfinance-admin-members" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2><?php p($l->t('Mitglieder und Personen')); ?></h2>
	<p><?php p($l->t('Ordne Nextcloud-Benutzerkonten den 22 Häusern zu.')); ?></p>

	<table>
		<thead>
			<tr>
				<th><?php p($l->t('Haus')); ?></th>
				<th><?php p($l->t('Zugeordnete Benutzer')); ?></th>
				<th><?php p($l->t('Offene Beträge')); ?></th>
				<th><?php p($l->t('Aktionen')); ?></th>
			</tr>
		</thead>
		<tbody id="members-table">
			<tr><td colspan="4"><?php p($l->t('Lädt...')); ?></td></tr>
		</tbody>
	</table>

	<style>
		#members-table td.amount-positive {
			color: #28a745 !important;
			font-weight: bold;
		}

		#members-table td.amount-negative {
			color: #dc3545 !important;
			font-weight: bold;
		}

		#members-table td.amount-zero {
			color: #999;
		}
	</style>

	<div id="assign-modal" style="display:none; margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f5f5f5;">
		<h3><?php p($l->t('Benutzer zuordnen')); ?></h3>
		<label>
			<?php p($l->t('Haus:')); ?> <span id="modal-member-address"></span>
		</label><br>
		<label>
			<?php p($l->t('Benutzer:')); ?>
			<select id="user-select">
				<option value=""><?php p($l->t('-- Bitte wählen --')); ?></option>
			</select>
		</label><br>
		<button id="assign-btn"><?php p($l->t('Zuordnen')); ?></button>
		<button id="modal-close"><?php p($l->t('Abbrechen')); ?></button>
	</div>
</div>
