<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Version0001Date20260814000000 implements IRepairStep {
	public function __construct(private IDBConnection $db) {}

	public function getName(): string {
		return 'Create weinsteig_members table';
	}

	public function run(IOutput $output): void {
		if ($this->db->tableExists('weinsteig_members')) {
			return;
		}

		$sql = "CREATE TABLE `weinsteig_members` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`address` VARCHAR(64) NOT NULL UNIQUE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

		$this->db->executeUpdate($sql);
		$output->info('Created table weinsteig_members');
	}
}
