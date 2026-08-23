<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0013Date20260823001200 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Add reminders (Mahnungen) functionality';
	}

	public function description(): string {
		return 'Create reminder table and add reminder_stop_until column to members table';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		// Create reminders table
		if (!$schema->hasTable('weinsteig_reminders')) {
			$table = $schema->createTable('weinsteig_reminders');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('member_id', 'bigint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('reminder_stage', 'smallint', [
				'notnull' => true,
				'default' => 1,
				'unsigned' => true,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);
			$table->addColumn('sent_at', 'datetime', [
				'notnull' => false,
			]);
			$table->addColumn('email_address', 'string', [
				'notnull' => false,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['member_id'], 'idx_reminders_member_id');
			$table->addIndex(['reminder_stage'], 'idx_reminders_stage');
			$table->addIndex(['created_at'], 'idx_reminders_created_at');
		}

		// Add reminder_stop_until column to members table
		$membersTable = $schema->getTable('weinsteig_members');
		if (!$membersTable->hasColumn('reminder_stop_until')) {
			$membersTable->addColumn('reminder_stop_until', 'datetime', [
				'notnull' => false,
			]);
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
