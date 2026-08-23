<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Migration;

use Closure;
use OCP\Migration\IOutput;
use OCP\Migration\IMigrationStep;
use OCP\IDBConnection;

class Version0014Date20260824001300 implements IMigrationStep {
	public function __construct(private IDBConnection $connection) {}

	public function name(): string {
		return 'Create reminder texts table';
	}

	public function description(): string {
		return 'Create table for storing customizable reminder (Mahnung) texts per stage';
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): void {
		$schema = $schemaClosure();

		if (!$schema->hasTable('weinsteig_reminder_texts')) {
			$table = $schema->createTable('weinsteig_reminder_texts');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('stage', 'smallint', [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('subject', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('body', 'text', [
				'notnull' => true,
			]);
			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);
			$table->addColumn('updated_at', 'datetime', [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['stage'], 'idx_unique_stage');
		}
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Insert default texts for 2 reminder stages
		$connection = $this->connection;
		$now = date('Y-m-d H:i:s');

		$defaults = [
			1 => [
				'subject' => 'Zahlungserinnerung - Energiegenossenschaft Weinsteig',
				'body' => 'Liebe/r {name},

wir möchten Sie höflich an folgende ausstehende Zahlung erinnern:

Haus: {address}
Offener Betrag: {amount}€
Fälligkeitsdatum: {duedate}

Falls Sie die Zahlung bereits getätigt haben, können Sie diese Nachricht ignorieren.

Vielen Dank!
Energiegenossenschaft Weinsteig',
			],
			2 => [
				'subject' => 'Mahnung - Energiegenossenschaft Weinsteig',
				'body' => 'Liebe/r {name},

trotz Zahlungserinnerung ist uns der folgende Betrag noch nicht eingegangen:

Haus: {address}
Offener Betrag: {amount}€
Ursprüngliches Fälligkeitsdatum: {duedate}

Bitte überweisen Sie den ausstehenden Betrag innerhalb von 14 Tagen.

Bei Fragen: office@langhofer.at

Energiegenossenschaft Weinsteig',
			],
		];

		foreach ($defaults as $stage => $data) {
			$qb = $connection->getQueryBuilder();
			$qb->insert('weinsteig_reminder_texts')
				->setValue('stage', $qb->createNamedParameter($stage))
				->setValue('subject', $qb->createNamedParameter($data['subject']))
				->setValue('body', $qb->createNamedParameter($data['body']))
				->setValue('created_at', $qb->createNamedParameter($now))
				->setValue('updated_at', $qb->createNamedParameter($now))
				->executeStatement();
		}
	}
}
