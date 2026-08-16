<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0011Date20260816001000 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Create config table';
	}

	public function description(): string {
		return 'Create key/value table for app configuration (e.g. SEPA Creditor ID)';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!$schema->hasTable('weinsteig_config')) {
			$table = $schema->createTable('weinsteig_config');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('config_key', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('config_value', 'string', [
				'notnull' => false,
				'length' => 4000,
			]);
			$table->addColumn('updated_at', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['config_key'], 'idx_unique_config_key');
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
