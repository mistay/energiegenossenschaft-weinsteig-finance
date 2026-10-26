<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0016Date20261026001400 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Normalize member names to comma-separated format';
	}

	public function description(): string {
		return 'Replace "und"/"oder" with commas in zahlungspflichtig field for modern formatting';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		// Schema doesn't change, only data migration
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$connection = $this->connection;
		$qb = $connection->getQueryBuilder();

		// Get all members with zahlungspflichtig containing "und" or "oder"
		$members = $qb->select('*')
			->from('weinsteig_members')
			->where($qb->expr()->isNotNull('zahlungspflichtig'))
			->executeQuery()
			->fetchAll();

		foreach ($members as $member) {
			$zahlungspflichtig = $member['zahlungspflichtig'];

			// Replace " und " with ", " (case-insensitive)
			$normalized = preg_replace('/ und /i', ', ', $zahlungspflichtig);
			// Replace " oder " with ", " (case-insensitive)
			$normalized = preg_replace('/ oder /i', ', ', $normalized);

			if ($normalized !== $zahlungspflichtig) {
				$qb = $connection->getQueryBuilder();
				$qb->update('weinsteig_members')
					->set('zahlungspflichtig', $qb->createNamedParameter($normalized))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($member['id'])))
					->executeStatement();
			}
		}
	}
}
