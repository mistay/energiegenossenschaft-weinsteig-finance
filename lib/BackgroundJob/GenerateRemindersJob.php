<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCA\WeinsteigFinance\Service\ReminderService;

class GenerateRemindersJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private ReminderService $reminderService,
	) {
		parent::__construct($time);

		// Run daily at 02:00 AM
		$this->setInterval(24 * 60); // Every 24 hours
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		try {
			$this->reminderService->generateAutomaticReminders();
		} catch (\Exception $e) {
			// Log error but don't fail the job
		}
	}
}
