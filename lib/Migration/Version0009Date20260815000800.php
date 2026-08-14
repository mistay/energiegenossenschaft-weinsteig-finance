<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\DB\ISchemaTools;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0009Date20260815000800 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Create vorschreibungen table';
	}

	public function description(): string {
		return 'Create vorschreibungen (invoices) table for tracking monthly invoices';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!$schema->hasTable('weinsteig_vorschreibungen')) {
			$table = $schema->createTable('weinsteig_vorschreibungen');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('member_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('year', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('month', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('amount', 'decimal', [
				'precision' => 10,
				'scale' => 2,
				'notnull' => true,
			]);
			$table->addColumn('status', 'string', [
				'length' => 50,
				'default' => 'open',
				'notnull' => true,
			]);
			$table->addColumn('due_date', 'date', [
				'notnull' => false,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);
			$table->addColumn('updated_at', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['member_id', 'year', 'month'], 'idx_member_year_month');
			$table->addIndex(['member_id'], 'idx_vorschr_member');
			$table->addIndex(['status'], 'idx_vorschr_status');
			$table->addIndex(['year', 'month'], 'idx_vorschr_period');
			$table->addForeignKeyConstraint('weinsteig_members', ['member_id'], ['id'], [], 'fk_vorschr_member');
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
