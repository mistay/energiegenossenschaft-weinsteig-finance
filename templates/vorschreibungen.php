<?php
/** @var \OCP\IL10N $l */
$currentPage = 'vorschreibungen';
?>

<div class="app-weinsteigfinance">
	<?php include 'nav.php'; ?>

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
	background: white;
	color: #333;
	border: 1px solid #0082c9;
	padding: 5px 10px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 12px;
	margin: 2px;
	text-decoration: none;
	display: inline-block;
	transition: all 0.2s;
}

.download-btn:hover {
	background: #0082c9;
	color: white;
	text-decoration: none;
}
</style>
