<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\DB\ISchemaTools;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigration;

class Version0001Date20260814000000 extends SimpleMigration {
	public function changeSchema(IOutput $output, Closure $schemaClosure, ISchemaTools $schemaTools): void {
		$schema = $schemaClosure();

		if (!$schemaTools->tableExists('weinsteig_members')) {
			$table = $schema->createTable('weinsteig_members');
			$table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
			$table->addColumn('address', 'string', ['length' => 64, 'notnull' => true]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['address']);
		}
	}
}
