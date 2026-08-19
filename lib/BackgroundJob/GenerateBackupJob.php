<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\BackgroundJob;

use OCA\WeinsteigFinance\Service\BackupService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

class GenerateBackupJob extends TimedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		private BackupService $backupService,
		private IConfig $config,
	) {
		parent::__construct($timeFactory);
		// Run daily at 2 AM
		$this->setInterval(24 * 3600);
	}

	protected function run($argument): void {
		try {
			$this->backupService->createBackup();
			$this->config->setAppValue('weinsteigfinance', 'last_backup_run', (string)(new \DateTime())->getTimestamp());
		} catch (\Exception $e) {
			\OCP\Server::get(\OCP\Log\ILogFactory::class)->getLogFile()?->log(0, 'Backup job error: ' . $e->getMessage());
		}
	}
}
