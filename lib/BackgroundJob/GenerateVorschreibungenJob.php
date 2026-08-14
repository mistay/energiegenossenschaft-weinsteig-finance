<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\BackgroundJob;

use OCA\WeinsteigFinance\Service\VorschreibungService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use DateTime;

class GenerateVorschreibungenJob extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private VorschreibungService $vorschreibungService,
		private IConfig $config,
	) {
		parent::__construct($timeFactory);

		// Täglich um 01:00 Uhr laufen
		$this->setInterval(24 * 3600);
	}

	protected function run($argument): void {
		$now = new DateTime();
		$day = (int)$now->format('d');

		// Speichere den letzten Lauf-Timestamp (egal ob generiert oder nicht)
		$this->config->setAppValue('weinsteigfinance', 'last_cron_run', $now->format('Y-m-d H:i:s'));

		// Nur am 1. des Monats generieren
		if ($day !== 1) {
			return;
		}

		$year = (int)$now->format('Y');
		$month = (int)$now->format('m');

		// Generiere alle Vorschreibungen für diesen Monat
		$generated = $this->vorschreibungService->generateAllForMonth($year, $month);

		// Log
		$message = 'Generated ' . count($generated) . ' invoices for ' . $month . '/' . $year;
		\OCP\Server::get(\OCP\Log\ILogFactory::class)->getLogFile()?->log(0, $message);

		// Speichere auch letzten Generierungs-Timestamp
		$this->config->setAppValue('weinsteigfinance', 'last_vorschreibungen_generated', $now->format('Y-m-d H:i:s'));
	}
}
