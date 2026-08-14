<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Version0003Date20260814000200 implements IRepairStep {
	public function __construct(private IDBConnection $db) {}

	public function getName(): string {
		return 'Seed weinsteig_members with 22 houses';
	}

	public function run(IOutput $output): void {
		$addresses = [
			'Weinsteig 2a', 'Weinsteig 2b', 'Weinsteig 4', 'Weinsteig 6a', 'Weinsteig 6b',
			'Weinsteig 8a', 'Weinsteig 8b', 'Weinsteig 8c', 'Weinsteig 10', 'Weinsteig 12',
			'Weinsteig 13a', 'Weinsteig 13b', 'Weinsteig 13c', 'Weinsteig 14a', 'Weinsteig 14b',
			'Weinsteig 14c', 'Weinsteig 15a', 'Weinsteig 15b', 'Weinsteig 17a', 'Weinsteig 17b',
			'Weinsteig 19a', 'Weinsteig 19b',
		];

		$qb = $this->db->getQueryBuilder();
		foreach ($addresses as $address) {
			try {
				$qb->insert('weinsteig_members')
					->values(['address' => $qb->createNamedParameter($address)])
					->executeStatement();
			} catch (\Exception) {
				// Already exists
			}
		}

		$output->info('Seeded ' . count($addresses) . ' houses');
	}
}
