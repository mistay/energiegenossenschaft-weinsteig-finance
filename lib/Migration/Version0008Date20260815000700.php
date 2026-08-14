<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\DB\ISchemaTools;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0008Date20260815000700 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Create zahlungen table';
	}

	public function description(): string {
		return 'Create zahlungen (payments) table for tracking bank transactions';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!$schema->hasTable('weinsteig_zahlungen')) {
			$table = $schema->createTable('weinsteig_zahlungen');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('member_id', 'integer', [
				'notnull' => false,
			]);
			$table->addColumn('buchungsdatum', 'date', [
				'notnull' => true,
			]);
			$table->addColumn('valutadatum', 'date', [
				'notnull' => true,
			]);
			$table->addColumn('partnername', 'string', [
				'length' => 255,
				'notnull' => true,
			]);
			$table->addColumn('verwendungszweck', 'text', [
				'notnull' => true,
			]);
			$table->addColumn('betrag', 'decimal', [
				'precision' => 10,
				'scale' => 2,
				'notnull' => true,
			]);
			$table->addColumn('waehrung', 'string', [
				'length' => 3,
				'default' => 'EUR',
				'notnull' => true,
			]);
			$table->addColumn('iban', 'string', [
				'length' => 34,
				'notnull' => false,
			]);
			$table->addColumn('bic', 'string', [
				'length' => 11,
				'notnull' => false,
			]);
			$table->addColumn('match_type', 'string', [
				'length' => 50,
				'default' => 'pending',
				'notnull' => true,
			]);
			$table->addColumn('match_confidence', 'integer', [
				'default' => 0,
				'notnull' => true,
			]);
			$table->addColumn('status', 'string', [
				'length' => 50,
				'default' => 'pending',
				'notnull' => true,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);
			$table->addColumn('updated_at', 'datetime', [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['member_id'], 'idx_member');
			$table->addIndex(['buchungsdatum'], 'idx_buchungsdatum');
			$table->addIndex(['status'], 'idx_status');
			$table->addForeignKeyConstraint('weinsteig_members', ['member_id'], ['id'], [], 'fk_zahlungen_member');
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
