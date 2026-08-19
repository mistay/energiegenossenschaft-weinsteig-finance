<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0012Date20260819001100 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Create mandate approvals table';
	}

	public function description(): string {
		return 'Create table to track approval status of uploaded signed mandate PDFs';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!$schema->hasTable('weinsteig_mandate_approvals')) {
			$table = $schema->createTable('weinsteig_mandate_approvals');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('member_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('version', 'integer', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('approved', 'boolean', [
				'notnull' => true,
				'default' => false,
			]);
			$table->addColumn('approved_by', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('approved_at', 'datetime', [
				'notnull' => false,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['member_id', 'version'], 'idx_unique_member_version');
			$table->addIndex(['member_id'], 'idx_member_id');
			$table->addIndex(['approved'], 'idx_approved');
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
