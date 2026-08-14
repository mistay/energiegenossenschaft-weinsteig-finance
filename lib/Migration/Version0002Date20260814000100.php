<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Version0002Date20260814000100 implements IRepairStep {
	public function __construct(private IDBConnection $db) {}

	public function getName(): string {
		return 'Create weinsteig_user_members table';
	}

	public function run(IOutput $output): void {
		if ($this->db->tableExists('weinsteig_user_members')) {
			return;
		}

		$sql = "CREATE TABLE `weinsteig_user_members` (
			`id` INT AUTO_INCREMENT PRIMARY KEY,
			`user_id` VARCHAR(255) NOT NULL,
			`member_id` INT NOT NULL,
			UNIQUE KEY `unique_assignment` (`user_id`, `member_id`),
			FOREIGN KEY (`member_id`) REFERENCES `weinsteig_members`(`id`) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

		$this->db->executeUpdate($sql);
		$output->info('Created table weinsteig_user_members');
	}
}
