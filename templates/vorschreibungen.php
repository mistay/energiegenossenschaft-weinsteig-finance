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
.member-card {
	background: white;
}

.download-btn {
	background: white;
	color: #0082c9;
	border: 1px solid #0082c9;
	padding: 6px 10px;
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
	text-decoration: none;
}

/* Responsive Layout */
@media (max-width: 768px) {
	.member-card {
		margin-bottom: 20px;
	}

	.download-btn {
		padding: 5px 8px;
		font-size: 11px;
	}
}
</style>
