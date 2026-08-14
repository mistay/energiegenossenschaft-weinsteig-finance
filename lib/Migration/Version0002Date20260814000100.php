<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;

class Version0002Date20260814000100 implements IMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!isset($schema->getTable('weinsteig_user_members'))) {
			$table = $schema->createTable('weinsteig_user_members');
			$table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
			$table->addColumn('user_id', 'string', ['length' => 255, 'notnull' => true]);
			$table->addColumn('member_id', 'integer', ['notnull' => true]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['user_id', 'member_id']);
			$table->addForeignKeyConstraint('weinsteig_members', ['member_id'], ['id'], ['onDelete' => 'CASCADE']);
		}
	}
}
