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
}

#vorschreibungen-table th, #vorschreibungen-table td {
	border: 1px solid #ddd;
	padding: 10px;
	text-align: left;
}

#vorschreibungen-table th {
	background: #f5f5f5;
	font-weight: bold;
	position: relative;
	white-space: nowrap;
}

#vorschreibungen-table tbody tr:hover {
	background: #f9f9f9;
}

.download-btn {
	background: white;
	color: #0082c9;
	border: 1px solid #0082c9;
	padding: 6px 8px;
	border-radius: 3px;
	cursor: pointer;
	font-size: 12px;
	text-decoration: none;
	display: inline-block;
	transition: all 0.2s;
	white-space: nowrap;
}

.download-btn:hover {
	background: #0082c9;
	color: white;
}

/* Responsive: Monat-Spalte bleibt sichtbar beim Scrollen */
@media (max-width: 768px) {
	#vorschreibungen-table th, #vorschreibungen-table td {
		padding: 8px;
		font-size: 13px;
	}

	.download-btn {
		padding: 4px 6px;
		font-size: 11px;
	}
}
</style>
