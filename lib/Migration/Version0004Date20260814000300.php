<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;

class Version0004Date20260814000300 implements IMigrationStep {
	public function name(): string {
		return 'Remove unique constraint from user_members';
	}

	public function description(): string {
		return 'Allows multiple users per member and multiple members per user';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		try {
			$table = $schema->getTable('weinsteig_user_members');
			$table->dropIndex('unique_assignment');
		} catch (\Exception) {
			// Index existiert nicht
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}
}
