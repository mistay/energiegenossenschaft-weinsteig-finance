<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;

class Version0002Date20260814000100 implements IMigrationStep {
	public function getName(): string {
		return 'Create weinsteig_user_members table';
	}

	public function getDescription(): string {
		return 'Creates junction table for users and members';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		try {
			$schema->getTable('weinsteig_user_members');
		} catch (\Exception) {
			$table = $schema->createTable('weinsteig_user_members');
			$table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
			$table->addColumn('user_id', 'string', ['length' => 255, 'notnull' => true]);
			$table->addColumn('member_id', 'integer', ['notnull' => true]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['user_id', 'member_id']);
			$table->addForeignKeyConstraint('weinsteig_members', ['member_id'], ['id'], ['onDelete' => 'CASCADE']);
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}
}
