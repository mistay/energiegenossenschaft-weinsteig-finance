<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;

class Version0001Date20260814000000 implements IMigrationStep {
	public function name(): string {
		return 'Create weinsteig_members table';
	}

	public function description(): string {
		return 'Creates table for storing house members';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		try {
			$schema->getTable('weinsteig_members');
		} catch (\Exception) {
			$table = $schema->createTable('weinsteig_members');
			$table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
			$table->addColumn('address', 'string', ['length' => 64, 'notnull' => true]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['address']);
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}
}
