<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;

class Version0006Date20260814000500 implements IMigrationStep {
	public function name(): string {
		return 'Add mandate withdrawal tracking';
	}

	public function description(): string {
		return 'Add mandate_withdrawn_date and mandate_withdrawn_reason columns';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		try {
			$table = $schema->getTable('weinsteig_members');
			if (!$table->hasColumn('mandate_withdrawn_date')) {
				$table->addColumn('mandate_withdrawn_date', 'datetime', ['notnull' => false]);
			}
			if (!$table->hasColumn('mandate_withdrawn_reason')) {
				$table->addColumn('mandate_withdrawn_reason', 'text', ['notnull' => false]);
			}
		} catch (\Exception) {}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}
}
