<?php
/** @var \OCP\IL10N $l */
?>

<div class="app-weinsteigfinance">
	<h2>Vorschreibungen</h2>
	<div id="vorschreibungen-container"></div>
</div>

<style>
#vorschreibungen-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 20px;
}

#vorschreibungen-table th, #vorschreibungen-table td {
	border: 1px solid #ddd;
	padding: 10px;
	text-align: left;
}

#vorschreibungen-table th {
	background: #f5f5f5;
	font-weight: bold;
}

#vorschreibungen-table tr:hover {
	background: #f9f9f9;
}

.download-btn {
	background: #0082c9;
	color: white;
	border: none;
	padding: 5px 10px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 12px;
	margin: 2px;
}

.download-btn:hover {
	background: #0070a8;
}
</style>
