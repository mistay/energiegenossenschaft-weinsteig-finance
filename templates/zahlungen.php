<?php
/** @var \OCP\IL10N $l */
?>

<div class="app-weinsteigfinance">
	<h2>Zahlungen</h2>
	<div id="zahlungen-container"></div>
</div>

<style>
#zahlungen-import {
	background: #f5f5f5;
	padding: 20px;
	border-radius: 3px;
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
}

.import-btn {
	background: #0082c9;
	color: white;
	border: none;
	padding: 8px 15px;
	border-radius: 3px;
	cursor: pointer;
	font-weight: bold;
}

.import-btn:hover {
	background: #0070a8;
}

#zahlungen-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 20px;
}

#zahlungen-table th, #zahlungen-table td {
	border: 1px solid #ddd;
	padding: 8px;
	text-align: left;
}

#zahlungen-table th {
	background: #f5f5f5;
	font-weight: bold;
}

.match-status-pending {
	background: #fff3cd;
	color: #856404;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 12px;
}

.match-status-matched {
	background: #d4edda;
	color: #155724;
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 12px;
}

.assign-btn {
	background: #28a745;
	color: white;
	border: none;
	padding: 4px 8px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 12px;
}

.assign-select {
	padding: 4px;
	font-size: 12px;
	margin-right: 5px;
}
</style>
