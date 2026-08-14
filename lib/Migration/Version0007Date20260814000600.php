<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\DB\ISchemaTools;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0007Date20260814000600 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Add mandate_granted_date column';
	}

	public function description(): string {
		return 'Add mandate_granted_date to weinsteig_members table';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();
		$table = $schema->getTable('weinsteig_members');

		if (!$table->hasColumn('mandate_granted_date')) {
			$table->addColumn('mandate_granted_date', 'datetime', [
				'notnull' => false,
				'default' => null,
			]);
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
