<?php
declare(strict_types=1);
style('weinsteigfinance', 'style');
script('weinsteigfinance', 'profil');
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

	.edit-name-btn {
		padding: 4px 12px;
		background: #0082c9;
		color: white;
		border: none;
		border-radius: 3px;
		cursor: pointer;
		font-size: 12px;
		margin-left: 8px;
	}

	.edit-name-btn:hover {
		background: #0066a3;
	}

	.edit-name-input {
		padding: 6px 8px;
		border: 1px solid #0082c9;
		border-radius: 3px;
		font-size: 14px;
		flex: 1;
		max-width: 300px;
	}

	.edit-name-actions {
		margin-top: 12px;
		display: flex;
		gap: 8px;
	}

	.edit-name-actions button {
		padding: 6px 16px;
		border: none;
		border-radius: 3px;
		cursor: pointer;
		font-size: 13px;
	}

	.edit-name-save {
		background: #28a745;
		color: white;
	}

	.edit-name-save:hover {
		background: #218838;
	}

	.edit-name-cancel {
		background: #ccc;
		color: #333;
	}

	.edit-name-cancel:hover {
		background: #bbb;
	}

	.edit-name-message {
		margin-top: 8px;
		font-size: 12px;
		padding: 8px;
		border-radius: 3px;
	}

	.edit-name-success {
		background: #d4edda;
		color: #155724;
	}

	.edit-name-error {
		background: #f8d7da;
		color: #721c24;
	}
</style>
