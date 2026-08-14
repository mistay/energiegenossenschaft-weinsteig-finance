<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\DB\ISchemaTools;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0010Date20260815000900 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Create zahlung_vorschreibung junction table';
	}

	public function description(): string {
		return 'Create junction table for matching payments to invoices (many-to-many)';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!$schema->hasTable('weinsteig_zahlung_vorschreibung')) {
			$table = $schema->createTable('weinsteig_zahlung_vorschreibung');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('zahlung_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('vorschreibung_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('amount', 'decimal', [
				'precision' => 10,
				'scale' => 2,
				'notnull' => true,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['zahlung_id', 'vorschreibung_id'], 'idx_unique_zahlung_vorschr');
			$table->addIndex(['zahlung_id'], 'idx_zv_zahlung');
			$table->addIndex(['vorschreibung_id'], 'idx_zv_vorschr');
			$table->addForeignKeyConstraint('weinsteig_zahlungen', ['zahlung_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_zv_zahlung');
			$table->addForeignKeyConstraint('weinsteig_vorschreibungen', ['vorschreibung_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_zv_vorschr');
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
