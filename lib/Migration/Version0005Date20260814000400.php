<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;

class Version0005Date20260814000400 implements IMigrationStep {
	public function name(): string {
		return 'Add payment person and IBAN to members';
	}

	public function description(): string {
		return 'Add zahlungspflichtig and iban columns';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		try {
			$table = $schema->getTable('weinsteig_members');
			if (!$table->hasColumn('zahlungspflichtig')) {
				$table->addColumn('zahlungspflichtig', 'string', ['length' => 255, 'notnull' => false]);
			}
			if (!$table->hasColumn('iban')) {
				$table->addColumn('iban', 'string', ['length' => 34, 'notnull' => false]);
			}
		} catch (\Exception) {}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}
}
