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
#sepa-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 20px;
	background: white;
}

#sepa-table th, #sepa-table td {
	border: 1px solid #ddd;
	padding: 12px;
	text-align: left;
}

#sepa-table th {
	background: #f5f5f5;
	font-weight: bold;
	color: #333;
}

#sepa-table tr:hover {
	background: #f9f9f9;
}

#sepa-table td {
	color: #555;
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
	transition: all 0.2s;
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
}

.stat-item {
	text-align: center;
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
</style>
