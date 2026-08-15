<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'profil';
?>

<div id="weinsteigfinance-profil" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>👤 Mein Profil</h2>

	<div id="profil-container">
		<p style="color: #999;">Lädt...</p>
	</div>
</div>

<style>
	.profil-card {
		background: white;
		border: 1px solid #ecf0f1;
		border-radius: 6px;
		padding: 20px;
		margin-bottom: 20px;
	}

	.profil-field {
		display: grid;
		grid-template-columns: 150px 1fr;
		gap: 16px;
		padding: 12px 0;
		border-bottom: 1px solid #ecf0f1;
	}

	.profil-field:last-child {
		border-bottom: none;
	}

	.profil-field-label {
		font-weight: 600;
		color: #2c3e50;
	}

	.profil-field-value {
		color: #555;
	}

	.liegenschaft-box {
		background: #f0f8ff;
		border-left: 4px solid #0082c9;
		padding: 16px;
		border-radius: 4px;
		margin-top: 20px;
	}
</style>
