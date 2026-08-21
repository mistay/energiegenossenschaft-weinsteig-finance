<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
$currentPage = 'admin-members';
?>
<div id="weinsteigfinance-admin-members" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2><?php p($l->t('Mitglieder und Personen')); ?></h2>
	<p><?php p($l->t('Ordne Nextcloud-Benutzerkonten den 22 Häusern zu.')); ?></p>

	<div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
		<button id="export-users-csv-btn" style="padding: 10px 20px; background: #0082c9; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; font-weight: 500; transition: background 0.2s;">
			📥 CSV exportieren
		</button>
		<button id="export-users-pdf-btn" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 14px; font-weight: 500; transition: background 0.2s;">
			📄 PDF exportieren
		</button>
	</div>

	<div id="members-table" style="margin-top: 20px;">
		<p style="color: #999;">Lädt...</p>
	</div>

	<style>
		.amount-positive {
			color: #28a745 !important;
			font-weight: bold;
		}

		.amount-negative {
			color: #dc3545 !important;
			font-weight: bold;
		}

		.amount-zero {
			color: #999;
		}

		.member-card {
			transition: box-shadow 0.2s;
		}

		.member-card:hover {
			box-shadow: 0 2px 6px rgba(0,0,0,0.12);
		}

		#members-cards .users-col-0,
		#members-cards [class*="users-col-"] {
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		#members-cards [class*="users-col-"] > div {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 6px;
			background: white;
			border-radius: 2px;
			font-size: 12px;
		}

		.remove-btn {
			padding: 2px 6px;
			background: #dc3545;
			color: white;
			border: none;
			border-radius: 2px;
			cursor: pointer;
			font-size: 11px;
			transition: background 0.2s;
		}

		.remove-btn:hover {
			background: #c82333;
		}

		@media (max-width: 768px) {
			.member-card {
				margin-bottom: 10px;
			}

			.assign-btn,
			.journal-link {
				font-size: 11px;
				padding: 5px 8px;
			}
		}
	</style>

	<div id="assign-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #ccc; border-radius: 4px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; min-width: 300px;">
		<h3 style="margin-top: 0;"><?php p($l->t('Benutzer zuordnen')); ?></h3>
		<div style="margin-bottom: 15px;">
			<label style="display: block; margin-bottom: 4px; font-weight: 500;">
				<?php p($l->t('Haus:')); ?>
			</label>
			<span id="modal-member-address" style="padding: 8px; background: #f5f5f5; border-radius: 3px; display: block;"></span>
		</div>
		<div style="margin-bottom: 15px;">
			<label style="display: block; margin-bottom: 4px; font-weight: 500;">
				<?php p($l->t('Benutzer:')); ?>
			</label>
			<select id="user-select" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
				<option value=""><?php p($l->t('-- Bitte wählen --')); ?></option>
			</select>
		</div>
		<div style="display: flex; gap: 8px; justify-content: flex-end;">
			<button id="modal-close" style="padding: 8px 16px; background: #ddd; color: #333; border: none; border-radius: 3px; cursor: pointer; font-weight: 500;"><?php p($l->t('Abbrechen')); ?></button>
			<button id="assign-btn" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 500;"><?php p($l->t('Zuordnen')); ?></button>
		</div>
	</div>

	<!-- Modal Backdrop -->
	<div id="assign-modal-backdrop" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"></div>
</div>
