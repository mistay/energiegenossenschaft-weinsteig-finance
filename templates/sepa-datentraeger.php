<?php
declare(strict_types=1);
/** @var \OCP\IL10N $l */
$currentPage = 'sepa-datentraeger';
?>

<div id="weinsteigfinance-sepa-datentraeger" class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>🏦 SEPA Core Datenträger</h2>

	<div id="sepa-container" style="margin-top: 20px;">
		<p style="color: #999;">Lädt...</p>
	</div>
</div>

<style>
.sepa-card {
	transition: box-shadow 0.2s, transform 0.2s;
}

.sepa-card:hover {
	box-shadow: 0 2px 6px rgba(0,0,0,0.12);
	transform: translateY(-1px);
}

.amount-positive {
	color: #28a745;
	font-weight: bold;
}

.amount-negative {
	color: #dc3545;
	font-weight: bold;
}

.amount-zero {
	color: #999;
}

.export-btn {
	background: #0082c9;
	color: white;
	border: none;
	padding: 10px 20px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 14px;
	margin-bottom: 20px;
	transition: background 0.2s;
	font-weight: 500;
}

.export-btn:hover {
	background: #0070a8;
}

.stats-box {
	background: #f0f8ff;
	border-left: 4px solid #0082c9;
	padding: 15px;
	border-radius: 4px;
	margin-bottom: 20px;
	display: flex;
	justify-content: space-around;
	gap: 20px;
	flex-wrap: wrap;
}

.stat-item {
	text-align: center;
	flex: 1;
	min-width: 100px;
}

.stat-label {
	font-size: 12px;
	color: #999;
	margin-bottom: 5px;
}

.stat-value {
	font-size: 18px;
	font-weight: bold;
	color: #0082c9;
}

.info-box {
	background: #fff3e0;
	border-left: 4px solid #ff9800;
	padding: 12px;
	border-radius: 4px;
	margin-bottom: 20px;
	font-size: 13px;
	color: #666;
}

/* Responsive */
@media (max-width: 768px) {
	.sepa-card {
		margin-bottom: 10px;
	}

	.stat-item {
		min-width: 80px;
	}

	.stat-label {
		font-size: 11px;
	}

	.stat-value {
		font-size: 16px;
	}

	.export-btn {
		padding: 8px 16px;
		font-size: 13px;
	}
}
</style>
