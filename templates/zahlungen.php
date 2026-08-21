<?php
/** @var \OCP\IL10N $l */
$currentPage = 'zahlungen-import';
?>

<div class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

	<h2>Zahlungen</h2>
	<div id="zahlungen-container"></div>
</div>

<style>
#zahlungen-import {
	background: #f5f5f5;
	padding: 20px;
	border-radius: 4px;
	margin-bottom: 20px;
}

#csv-input {
	width: 100%;
	height: 150px;
	font-family: monospace;
	font-size: 12px;
	padding: 10px;
	border: 1px solid #ddd;
	border-radius: 3px;
	margin-bottom: 10px;
	box-sizing: border-box;
}

.import-btn {
	background: #0082c9;
	color: white;
	border: none;
	padding: 8px 15px;
	border-radius: 3px;
	cursor: pointer;
	font-weight: bold;
	transition: background 0.2s;
}

.import-btn:hover {
	background: #0070a8;
}

.zahlung-card {
	box-shadow: 0 1px 3px rgba(0,0,0,0.08);
	transition: box-shadow 0.2s;
}

.zahlung-card:hover {
	box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.match-status-pending {
	background: #fff3cd;
	color: #856404;
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	white-space: nowrap;
}

.match-status-matched {
	background: #d4edda;
	color: #155724;
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	white-space: nowrap;
}

.assign-btn {
	background: #28a745;
	color: white;
	border: none;
	padding: 6px 12px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 13px;
	font-weight: 500;
	transition: background 0.2s;
}

.assign-btn:hover {
	background: #218838;
}

.unassign-btn {
	background: #dc3545;
	color: white;
	border: none;
	padding: 6px 12px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 13px;
	font-weight: 500;
	transition: background 0.2s;
}

.unassign-btn:hover {
	background: #c82333;
}

.assign-select {
	padding: 6px;
	font-size: 13px;
	border: 1px solid #ddd;
	border-radius: 3px;
	background: white;
}

/* Responsive */
@media (max-width: 768px) {
	#zahlungen-import {
		padding: 15px;
	}

	#csv-input {
		font-size: 11px;
		height: 120px;
	}

	.zahlung-card {
		padding: 12px !important;
	}

	.assign-btn,
	.unassign-btn {
		padding: 5px 10px;
		font-size: 12px;
	}
}
</style>
