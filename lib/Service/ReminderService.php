<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

use OCP\IDBConnection;
use OCP\AppFramework\Utility\ITimeFactory;
use DateTime;

class ReminderService {
	private const REMINDER_STAGES = [
		1 => 'Zahlungserinnerung',
		2 => 'Mahnung',
		3 => 'Letzte Mahnung',
	];

	private const REMINDER_TEXTS = [
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
		3 => [
			'subject' => 'Letzte Mahnung - Energiegenossenschaft Weinsteig',
			'body' => 'Liebe/r {name},

leider haben Sie unsere bisherigen Zahlungsaufforderungen ignoriert.

Haus: {address}
Offener Betrag: {amount}€
Jetzt fällig: SOFORT

Falls wir bis {finaldate} keine Zahlung erhalten, sehen wir uns gezwungen, rechtliche Schritte einzuleiten.

Bitte zahlen Sie sofort.

Energiegenossenschaft Weinsteig
office@langhofer.at',
		],
	];

	public function __construct(
		private IDBConnection $db,
		private ITimeFactory $timeFactory,
	) {}

	/**
	 * Generate automatic reminders if last payment import is recent (< 7 days old)
	 */
	public function generateAutomaticReminders(): array {
		$result = [
			'generated' => 0,
			'sent' => 0,
			'errors' => [],
		];

		// Check if there's a recent payment import (< 7 days)
		$lastImportDate = $this->getLastPaymentImportDate();
		if (!$lastImportDate) {
			return $result;
		}

		$now = $this->timeFactory->getDateTime();
		$daysSinceImport = $now->diff($lastImportDate)->days;

		if ($daysSinceImport > 7) {
			// No recent import, skip automatic generation
			return $result;
		}

		// Get all members with open balance
		$members = $this->getMembersWithOpenBalance();

		foreach ($members as $member) {
			try {
				// Check if reminder is suppressed
				if ($member['reminder_stop_until']) {
					$stopDate = new DateTime($member['reminder_stop_until']);
					if ($stopDate > $now) {
						continue; // Reminder is suppressed
					}
				}

				// Check if oldest open bill is > 30 days old
				$oldestBill = $this->getOldestOpenBillDate($member['id']);
				if (!$oldestBill) {
					continue; // No open bills
				}

				$daysSinceBill = $now->diff($oldestBill)->days;
				if ($daysSinceBill < 30) {
					continue; // Bill is not old enough yet
				}

				// Check if last reminder was > 14 days ago
				$lastReminder = $this->getLastReminderDate($member['id']);
				if ($lastReminder) {
					$daysSinceReminder = $now->diff($lastReminder)->days;
					if ($daysSinceReminder < 14) {
						continue; // Too soon to send another reminder
					}
				}

				// Get next reminder stage
				$nextStage = $this->getNextReminderStage($member['id']);
				if ($nextStage > 3) {
					continue; // Already at stage 3
				}

				// Create reminder
				if ($this->createReminder($member['id'], $nextStage, $member)) {
					$result['generated']++;
				}
			} catch (\Exception $e) {
				$result['errors'][] = "Error for member {$member['id']}: {$e->getMessage()}";
			}
		}

		return $result;
	}

	/**
	 * Manually create reminder (always possible, ignores suppression)
	 */
	public function createReminderManual(int $memberId): bool {
		$qb = $this->db->getQueryBuilder();
		$member = $qb->select('*')
			->from('weinsteig_members')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($memberId)))
			->executeQuery()
			->fetch();

		if (!$member) {
			throw new \Exception("Member not found: $memberId");
		}

		$nextStage = $this->getNextReminderStage($memberId);
		if ($nextStage > 3) {
			throw new \Exception("Member already at maximum reminder stage (3)");
		}

		return $this->createReminder($memberId, $nextStage, $member);
	}

	/**
	 * Internal: Create reminder and send email
	 */
	private function createReminder(int $memberId, int $stage, array $member): bool {
		try {
			$now = $this->timeFactory->getDateTime();
			$address = $member['address'] ?? 'Unbekannt';
			$zahlungspflichtig = $member['zahlungspflichtig'] ?? 'Unbekannt';
			$openAmount = $this->calculateOpenAmount($memberId);

			// Create reminder record
			$qb = $this->db->getQueryBuilder();
			$qb->insert('weinsteig_reminders')
				->setValue('member_id', $qb->createNamedParameter($memberId))
				->setValue('reminder_stage', $qb->createNamedParameter($stage))
				->setValue('email_address', $qb->createNamedParameter($member['email'] ?? ''))
				->setValue('created_at', $qb->createNamedParameter($now, 'datetime'))
				->executeStatement();

			// Send email
			$this->sendReminderEmail($stage, $address, $zahlungspflichtig, $openAmount, $member);

			// Update sent_at
			$qb = $this->db->getQueryBuilder();
			$qb->update('weinsteig_reminders')
				->set('sent_at', $qb->createNamedParameter($now, 'datetime'))
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
				->andWhere($qb->expr()->eq('reminder_stage', $qb->createNamedParameter($stage)))
				->orderBy('created_at', 'DESC')
				->setMaxResults(1)
				->executeStatement();

			return true;
		} catch (\Exception $e) {
			throw new \Exception("Failed to create reminder: {$e->getMessage()}");
		}
	}

	/**
	 * Send reminder email
	 * TODO: Implement email sending when IMailer is properly configured
	 */
	private function sendReminderEmail(int $stage, string $address, string $name, float $amount, array $member): void {
		// Email functionality disabled for now
		// Will be implemented when Nextcloud mail service is properly configured
		// For now, reminders are created in database and can be viewed in UI
	}

	/**
	 * Get members with open balance
	 */
	private function getMembersWithOpenBalance(float $minAmount = 10.0): array {
		$qb = $this->db->getQueryBuilder();
		$members = $qb->select('*')
			->from('weinsteig_members')
			->executeQuery()
			->fetchAll();

		$result = [];
		foreach ($members as $member) {
			$openAmount = $this->calculateOpenAmount($member['id']);
			// Only include if member owes money (negative saldo) >= minAmount
			if ($openAmount <= -$minAmount) {
				$member['open_amount'] = $openAmount;
				$result[] = $member;
			}
		}
		return $result;
	}

	/**
	 * Calculate open amount for member
	 */
	private function calculateOpenAmount(int $memberId): float {
		$qb = $this->db->getQueryBuilder();
		$zahlungen = $qb->select('*')
			->from('weinsteig_zahlungen')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->executeQuery()
			->fetchAll();

		$totalZahlungen = 0;
		foreach ($zahlungen as $z) {
			$totalZahlungen += (float)$z['betrag'];
		}

		$qb = $this->db->getQueryBuilder();
		$vorschreibungen = $qb->select('*')
			->from('weinsteig_vorschreibungen')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->executeQuery()
			->fetchAll();

		$openVorschreibungen = 0;
		foreach ($vorschreibungen as $v) {
			if ($v['status'] !== 'paid') {
				$openVorschreibungen += (float)$v['amount'];
			}
		}

		return $totalZahlungen - $openVorschreibungen;
	}

	/**
	 * Get date of oldest open bill
	 */
	private function getOldestOpenBillDate(int $memberId): ?DateTime {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('year', 'month')
			->from('weinsteig_vorschreibungen')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->andWhere($qb->expr()->neq('status', $qb->createNamedParameter('paid')))
			->orderBy('year', 'ASC')
			->addOrderBy('month', 'ASC')
			->setMaxResults(1)
			->executeQuery()
			->fetch();

		if (!$result) {
			return null;
		}

		$year = (int)$result['year'];
		$month = (int)$result['month'];

		if ($year <= 0 || $month <= 0 || $month > 12) {
			return null;
		}

		try {
			return new DateTime(sprintf("%04d-%02d-01", $year, $month));
		} catch (\Exception) {
			return null;
		}
	}

	/**
	 * Get date of last reminder
	 */
	private function getLastReminderDate(int $memberId): ?DateTime {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('created_at')
			->from('weinsteig_reminders')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->orderBy('created_at', 'DESC')
			->setMaxResults(1)
			->executeQuery()
			->fetch();

		if (!$result || !$result['created_at']) {
			return null;
		}

		try {
			return new DateTime($result['created_at']);
		} catch (\Exception) {
			return null;
		}
	}

	/**
	 * Get next reminder stage (1, 2, 3, or 4 if already done)
	 */
	private function getNextReminderStage(int $memberId): int {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('MAX(reminder_stage) as max_stage')
			->from('weinsteig_reminders')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->executeQuery()
			->fetch();

		$currentStage = (int)($result['max_stage'] ?? 0);
		return $currentStage + 1;
	}

	/**
	 * Get last payment import date (based on created_at of zahlungen)
	 */
	private function getLastPaymentImportDate(): ?DateTime {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('created_at')
			->from('weinsteig_zahlungen')
			->orderBy('created_at', 'DESC')
			->setMaxResults(1)
			->executeQuery()
			->fetch();

		if (!$result || !$result['created_at']) {
			return null;
		}

		try {
			return new DateTime($result['created_at']);
		} catch (\Exception) {
			return null;
		}
	}

	/**
	 * Check all conditions for reminder (for debugging)
	 */
	public function checkReminderConditions(int $memberId): array {
		try {
			$now = $this->timeFactory->getDateTime();

			// Get member
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('*')
				->from('weinsteig_members')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($memberId)))
				->executeQuery()
				->fetch();

			if (!$member) {
				return ['error' => 'Mitglied nicht gefunden'];
			}
		} catch (\Exception $e) {
			return ['error' => 'Datenbankfehler: ' . $e->getMessage()];
		}

		$result = [
			'member_id' => $memberId,
			'address' => $member['address'] ?? 'Unbekannt',
			'checks' => [],
			'can_create_reminder' => false,
			'reason' => [],
		];

		try {
			// Check 1: Open amount >= 10€
			$openAmount = $this->calculateOpenAmount($memberId);
		$check1 = $openAmount <= -10.0;  // Negative = member owes money
		$debtAmount = abs($openAmount);
		$result['checks']['open_amount'] = [
			'passed' => $check1,
			'value' => $openAmount,
			'message' => $check1 ? "✓ Schuld: {$debtAmount}€ (≥ 10€)" : "❌ Schuld: {$debtAmount}€ (< 10€ - zu klein)"
		];
		if (!$check1) $result['reason'][] = "Schuld < 10€";

		// Check 2: Suppression
		$isSuppressed = false;
		if ($member['reminder_stop_until']) {
			$stopDate = new DateTime($member['reminder_stop_until']);
			$isSuppressed = $stopDate > $now;
		}
		$check2 = !$isSuppressed;
		$result['checks']['suppression'] = [
			'passed' => $check2,
			'value' => $isSuppressed ? $member['reminder_stop_until'] : null,
			'message' => $isSuppressed ? "❌ Mahnstop aktiv bis {$member['reminder_stop_until']}" : "✓ Kein Mahnstop aktiv"
		];
		if (!$check2) $result['reason'][] = "Mahnstop aktiv";

		// Check 3: Oldest open bill > 30 days
		$oldestBill = $this->getOldestOpenBillDate($memberId);
		$check3 = false;
		if ($oldestBill) {
			$daysSinceBill = $now->diff($oldestBill)->days;
			$check3 = $daysSinceBill >= 30;
			$result['checks']['bill_age'] = [
				'passed' => $check3,
				'value' => $daysSinceBill,
				'message' => $check3
					? "✓ Älteste Rechnung: {$daysSinceBill} Tage alt (≥ 30)"
					: "❌ Älteste Rechnung: {$daysSinceBill} Tage alt (< 30 - zu neu)"
			];
			if (!$check3) $result['reason'][] = "Älteste Rechnung noch keine 30 Tage alt ({$daysSinceBill} Tage)";
		} else {
			$result['checks']['bill_age'] = [
				'passed' => false,
				'value' => null,
				'message' => "❌ Keine offenen Rechnungen gefunden"
			];
			$result['reason'][] = "Keine offenen Rechnungen";
		}

		// Check 4: Last reminder > 14 days ago
		$lastReminder = $this->getLastReminderDate($memberId);
		$check4 = true;
		if ($lastReminder) {
			$daysSinceReminder = $now->diff($lastReminder)->days;
			$check4 = $daysSinceReminder >= 14;
			$result['checks']['reminder_spacing'] = [
				'passed' => $check4,
				'value' => $daysSinceReminder,
				'message' => $check4
					? "✓ Letzte Mahnung: {$daysSinceReminder} Tage her (≥ 14)"
					: "❌ Letzte Mahnung: {$daysSinceReminder} Tage her (< 14 - zu nah beieinander)"
			];
			if (!$check4) $result['reason'][] = "Letzte Mahnung noch keine 14 Tage her ({$daysSinceReminder} Tage)";
		} else {
			$result['checks']['reminder_spacing'] = [
				'passed' => true,
				'value' => null,
				'message' => "✓ Keine bisherige Mahnung vorhanden"
			];
		}

		// Check 5: Recent payment import
		$lastImportDate = $this->getLastPaymentImportDate();
		$check5 = false;
		if ($lastImportDate) {
			$daysSinceImport = $now->diff($lastImportDate)->days;
			$check5 = $daysSinceImport <= 7;
			$result['checks']['recent_import'] = [
				'passed' => $check5,
				'value' => $daysSinceImport,
				'message' => $check5
					? "✓ Letzter Kontoauszug: {$daysSinceImport} Tage her (≤ 7)"
					: "❌ Letzter Kontoauszug: {$daysSinceImport} Tage her (> 7 - zu alt)"
			];
			if (!$check5) $result['reason'][] = "Kein aktueller Kontoauszug ({$daysSinceImport} Tage alt)";
		} else {
			$result['checks']['recent_import'] = [
				'passed' => false,
				'value' => null,
				'message' => "❌ Kein Kontoauszug importiert"
			];
			$result['reason'][] = "Kein Kontoauszug importiert";
		}

			// Final decision
			$result['can_create_reminder'] = $check1 && $check2 && $check3 && $check4 && $check5;

			if ($result['can_create_reminder']) {
				$result['message'] = "✅ Mahnung WÜRDE ausgegeben!";
			} else {
				$result['message'] = "❌ Mahnung wird NICHT ausgegeben. Gründe: " . implode(", ", $result['reason']);
			}

			return $result;
		} catch (\Exception $e) {
			return [
				'error' => 'Fehler beim Prüfen der Bedingungen: ' . $e->getMessage(),
				'member_id' => $memberId,
				'checks' => [],
				'can_create_reminder' => false,
			];
		}
	}

	/**
	 * Get reminder history for member
	 */
	public function getReminderHistory(int $memberId, int $limit = 10): array {
		$qb = $this->db->getQueryBuilder();
		return $qb->select('*')
			->from('weinsteig_reminders')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit)
			->executeQuery()
			->fetchAll();
	}

	/**
	 * Toggle reminder suppression
	 */
	public function setReminderStop(int $memberId, ?DateTime $until = null): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update('weinsteig_members')
			->set('reminder_stop_until', $qb->createNamedParameter($until, 'datetime'))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($memberId)))
			->executeStatement();
	}
}
