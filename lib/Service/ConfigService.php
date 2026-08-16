<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

use OCP\IDBConnection;
use DateTime;

/**
 * Konfigurationswerte der App aus der Tabelle weinsteig_config.
 *
 * Werte wie die SEPA Creditor ID stehen bewusst nicht im Quellcode,
 * sondern werden in der Verwaltung eingetragen.
 */
class ConfigService {
	public const KEY_CREDITOR_ID = 'creditor_id';

	public function __construct(private IDBConnection $db) {}

	public function get(string $key, string $default = ''): string {
		$qb = $this->db->getQueryBuilder();
		$value = $qb->select('config_value')
			->from('weinsteig_config')
			->where($qb->expr()->eq('config_key', $qb->createNamedParameter($key)))
			->executeQuery()
			->fetchOne();

		if ($value === false || $value === null || $value === '') {
			return $default;
		}

		return (string)$value;
	}

	public function set(string $key, string $value): void {
		$now = (new DateTime())->format('Y-m-d H:i:s');

		$qb = $this->db->getQueryBuilder();
		$exists = $qb->select('id')
			->from('weinsteig_config')
			->where($qb->expr()->eq('config_key', $qb->createNamedParameter($key)))
			->executeQuery()
			->fetchOne();

		$qb = $this->db->getQueryBuilder();
		if ($exists === false) {
			$qb->insert('weinsteig_config')
				->values([
					'config_key' => $qb->createNamedParameter($key),
					'config_value' => $qb->createNamedParameter($value),
					'updated_at' => $qb->createNamedParameter($now),
				])
				->executeStatement();
		} else {
			$qb->update('weinsteig_config')
				->set('config_value', $qb->createNamedParameter($value))
				->set('updated_at', $qb->createNamedParameter($now))
				->where($qb->expr()->eq('config_key', $qb->createNamedParameter($key)))
				->executeStatement();
		}
	}

	/**
	 * Creditor ID (Gläubiger-Identifikationsnummer) des Zahlungsempfängers.
	 */
	public function getCreditorId(): string {
		return $this->get(self::KEY_CREDITOR_ID);
	}
}
