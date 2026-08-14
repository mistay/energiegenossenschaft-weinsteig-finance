<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\DB\ISchemaTools;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigration;

class Version0002Date20260814000100 extends SimpleMigration {
	public function changeSchema(IOutput $output, Closure $schemaClosure, ISchemaTools $schemaTools): void {
		$schema = $schemaClosure();

		if (!$schemaTools->tableExists('weinsteig_user_members')) {
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
