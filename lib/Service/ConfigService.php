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
	public const KEY_CREDITOR_IBAN = 'creditor_iban';
	public const KEY_CREDITOR_BIC = 'creditor_bic';

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

	/**
	 * IBAN des Genossenschaftskontos.
	 */
	public function getCreditorIban(): string {
		return $this->get(self::KEY_CREDITOR_IBAN);
	}

	/**
	 * BIC des Genossenschaftskontos.
	 */
	public function getCreditorBic(): string {
		return $this->get(self::KEY_CREDITOR_BIC);
	}

	/**
	 * Bankverbindung der Genossenschaft als HTML-Zeilen für PDFs.
	 */
	public function getBankAccountHtml(): string {
		$html = '';
		$iban = $this->getCreditorIban();
		$bic = $this->getCreditorBic();

		if ($iban !== '') {
			$html .= 'IBAN: ' . $iban . '<br>';
		}
		if ($bic !== '') {
			$html .= 'BIC: ' . $bic . '<br>';
		}

		return $html;
	}

	/**
	 * Formatiere die Cron-Status-Information für die UI.
	 *
	 * @param string|null $lastCronRun Timestamp von letztem Cron-Lauf (Y-m-d H:i:s)
	 * @param string|null $lastGenerated Timestamp von letzter Generierung (Y-m-d H:i:s)
	 * @return array mit 'cronLastRun', 'nextRunExpected' (Text für UI)
	 */
	public function formatCronStatus(?string $lastCronRun, ?string $lastGenerated): array {
		$now = new \DateTime();
		$result = [
			'cronLastRun' => null,
			'cronLastRunDetail' => null,
			'nextRunExpected' => null,
			'nextRunDate' => null,
			'daysUntilNext' => null,
		];

		// Cron Last Run
		if ($lastCronRun) {
			try {
				$lastRun = new \DateTime($lastCronRun);
				$result['cronLastRun'] = $this->formatRelativeTime($now, $lastRun);
				$result['cronLastRunDetail'] = $lastRun->format('d.m.Y H:i');
			} catch (\Exception) {
				$result['cronLastRun'] = 'unbekannt';
			}
		} else {
			$result['cronLastRun'] = 'Cron hat noch keine Ticks erhalten';
		}

		// Nächster erwarteter Lauf: 1. des Monats
		$nextFirst = new \DateTime($now->format('Y-m-01'));
		if ($nextFirst <= $now) {
			$nextFirst->add(new \DateInterval('P1M')); // nächster Monat
		}

		$diff = $now->diff($nextFirst);
		$daysRemaining = $diff->days;

		// Format: "in X Tagen" oder mit Stunden wenn < 2 Tage
		if ($daysRemaining === 0) {
			$result['nextRunExpected'] = "heute noch in {$diff->h}h {$diff->i}min";
		} elseif ($daysRemaining === 1) {
			$result['nextRunExpected'] = "morgen in {$diff->h}h {$diff->i}min";
		} elseif ($daysRemaining <= 2) {
			// Letzte 2 Tage: zeige Stunden
			$totalHours = $daysRemaining * 24 + $diff->h;
			$result['nextRunExpected'] = "in {$totalHours}h {$diff->i}min";
		} else {
			// Mehr als 2 Tage: nur Tage
			$result['nextRunExpected'] = "in {$daysRemaining} Tagen";
		}

		$result['nextRunDate'] = $nextFirst->format('d.m.Y');
		$result['daysUntilNext'] = $daysRemaining;

		return $result;
	}

	/**
	 * Formatiere Zeit-Differenz lesbar (z.B. "vor 2 Stunden", "in 3 Tagen").
	 */
	private function formatRelativeTime(\DateTime $now, \DateTime $other, bool $future = false): string {
		$diff = $now->diff($other);

		if ($diff->days > 0) {
			if ($diff->days === 1) {
				return $future ? 'morgen' : 'gestern';
			}
			return $future ? "in {$diff->days} Tagen" : "vor {$diff->days} Tagen";
		}

		if ($diff->h > 0) {
			return $future ? "in {$diff->h}h" : "vor {$diff->h}h";
		}

		if ($diff->i > 0) {
			return $future ? "in {$diff->i}min" : "vor {$diff->i}min";
		}

		return 'gerade eben';
	}
}
