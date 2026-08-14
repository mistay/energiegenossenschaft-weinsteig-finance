<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

use OCP\IDBConnection;
use DateTime;

class ZahlungService {
	public function __construct(
		private IDBConnection $db,
	) {}

	/**
	 * Importiert Zahlungen aus CSV und führt Matching durch
	 */
	public function importFromCsv(string $csvContent): array {
		$lines = array_filter(array_map('trim', explode("\n", $csvContent)));
		if (empty($lines)) {
			return ['error' => 'Empty CSV'];
		}

		// Header-Zeile überspringen
		array_shift($lines);

		$zahlungen = [];
		foreach ($lines as $line) {
			$parts = str_getcsv($line, ';');
			if (count($parts) < 8) continue;

			$buchungsdatum = $this->parseDate($parts[0]);
			$valutadatum = $this->parseDate($parts[1]);
			$partnername = trim($parts[2]);
			$verwendungszweck = trim($parts[3]);
			$betrag = (float)str_replace(',', '.', trim($parts[4]));
			$waehrung = trim($parts[5]);
			$iban = trim($parts[6]) ?: null;
			$bic = trim($parts[7]) ?: null;

			if (!$buchungsdatum || !$betrag) continue;

			$zahlungen[] = [
				'buchungsdatum' => $buchungsdatum,
				'valutadatum' => $valutadatum,
				'partnername' => $partnername,
				'verwendungszweck' => $verwendungszweck,
				'betrag' => $betrag,
				'waehrung' => $waehrung,
				'iban' => $iban,
				'bic' => $bic,
			];
		}

		// Alle Members laden für Matching
		$members = $this->getAllMembers();

		// Matching durchführen
		$result = [];
		foreach ($zahlungen as $zahlung) {
			$match = $this->matchZahlung($zahlung, $members);
			$zahlung['member_id'] = $match['member_id'];
			$zahlung['match_type'] = $match['type'];
			$zahlung['match_confidence'] = $match['confidence'];
			$zahlung['status'] = $match['member_id'] ? 'matched' : 'pending';
			$zahlung['created_at'] = (new DateTime())->format('Y-m-d H:i:s');

			// In DB speichern
			$this->saveZahlung($zahlung);
			$result[] = $zahlung;
		}

		return ['success' => true, 'count' => count($result), 'zahlungen' => $result];
	}

	/**
	 * Matching mit mehreren Strategien
	 */
	private function matchZahlung(array $zahlung, array $members): array {
		$text = $zahlung['verwendungszweck'] . ' ' . $zahlung['partnername'];

		// Lade User-Namen für Matching
		$userNames = $this->getUserNames();

		// 1. Exakte Adresse im Text
		foreach ($members as $member) {
			$adresse = strtolower($member['address']);
			if (strpos(strtolower($text), $adresse) !== false) {
				return [
					'member_id' => $member['id'],
					'type' => 'auto_exact_address',
					'confidence' => 95
				];
			}
		}

		// 2a. Partnername exakt gegen zahlungspflichtig
		foreach ($members as $member) {
			$zahl = strtolower($member['zahlungspflichtig'] ?? '');
			$partner = strtolower($zahlung['partnername']);
			if (!empty($zahl) && strpos($partner, $zahl) !== false) {
				return [
					'member_id' => $member['id'],
					'type' => 'auto_partnername',
					'confidence' => 90
				];
			}
		}

		// 2b. Partnername gegen User-DisplayNames
		foreach ($userNames as $userId => $displayName) {
			$partner = strtolower($zahlung['partnername']);
			$userNameLower = strtolower($displayName);

			// Exakter Match
			if ($partner === $userNameLower || strpos($partner, $userNameLower) !== false) {
				// Finde das Haus das diesem User zugeordnet ist
				$memberId = $this->getMemberForUser($userId);
				if ($memberId) {
					return [
						'member_id' => $memberId,
						'type' => 'auto_username',
						'confidence' => 89
					];
				}
			}

			// Wort-Match auf User-Name
			$partnerWords = preg_split('/[\s,;\/\-]+/', $partner, -1, PREG_SPLIT_NO_EMPTY);
			$userWords = preg_split('/[\s,;\/\-]+/', $userNameLower, -1, PREG_SPLIT_NO_EMPTY);

			$allMatch = true;
			foreach ($partnerWords as $word) {
				if (strlen($word) > 2 && !in_array($word, $userWords)) {
					$allMatch = false;
					break;
				}
			}

			if ($allMatch && count($partnerWords) >= 2) {
				$memberId = $this->getMemberForUser($userId);
				if ($memberId) {
					return [
						'member_id' => $memberId,
						'type' => 'auto_username_words',
						'confidence' => 87
					];
				}
			}
		}

		// 2c. Partnername: Alle Wörter gegen zahlungspflichtig (aber nicht in Reihenfolge)
		foreach ($members as $member) {
			$zahl = strtolower($member['zahlungspflichtig'] ?? '');
			$partner = strtolower($zahlung['partnername']);
			if (empty($zahl)) continue;

			$partnerWords = preg_split('/[\s,;\/\-]+/', $partner, -1, PREG_SPLIT_NO_EMPTY);
			$zahlWords = preg_split('/[\s,;\/\-]+/', $zahl, -1, PREG_SPLIT_NO_EMPTY);

			// Alle Wörter aus Partner müssen in Zahl vorkommen
			$allMatch = true;
			foreach ($partnerWords as $word) {
				if (strlen($word) > 2 && !in_array($word, $zahlWords)) {
					$allMatch = false;
					break;
				}
			}

			if ($allMatch && count($partnerWords) >= 2) {
				return [
					'member_id' => $member['id'],
					'type' => 'auto_partnername_words',
					'confidence' => 86
				];
			}
		}

		// 3. Fuzzy Matching auf Adresse (ohne Leerzeichen, case-insensitive)
		$textClean = preg_replace('/\s+/', '', strtolower($text));
		foreach ($members as $member) {
			$adresseClean = preg_replace('/\s+/', '', strtolower($member['address']));
			// Levenshtein distance für Typos
			if ($this->stringSimilarity($textClean, $adresseClean) > 0.85) {
				return [
					'member_id' => $member['id'],
					'type' => 'auto_fuzzy_address',
					'confidence' => 85
				];
			}
		}

		// 4. Familienname im Partnername oder Text
		foreach ($members as $member) {
			$zahl = strtolower($member['zahlungspflichtig'] ?? '');
			if (empty($zahl)) continue;
			// Nimm letztes Wort (Familienname)
			$words = preg_split('/[\s,;\/]+/', $zahl);
			$lastname = strtolower(end($words));
			if (!empty($lastname) && strlen($lastname) > 2 && strpos(strtolower($text), $lastname) !== false) {
				return [
					'member_id' => $member['id'],
					'type' => 'auto_lastname',
					'confidence' => 75
				];
			}
		}

		// No match
		return [
			'member_id' => null,
			'type' => 'pending',
			'confidence' => 0
		];
	}

	/**
	 * String-Ähnlichkeit (0-1, wobei 1 = identisch)
	 */
	private function stringSimilarity(string $a, string $b): float {
		$longer = strlen($a) > strlen($b) ? $a : $b;
		$shorter = strlen($a) > strlen($b) ? $b : $a;
		if (strlen($longer) === 0) return 1.0;
		$distance = levenshtein($longer, $shorter);
		return (strlen($longer) - $distance) / strlen($longer);
	}

	private function parseDate(string $date): ?string {
		try {
			$d = DateTime::createFromFormat('d.m.Y', trim($date));
			return $d ? $d->format('Y-m-d') : null;
		} catch (\Exception) {
			return null;
		}
	}

	private function getAllMembers(): array {
		$qb = $this->db->getQueryBuilder();
		return $qb->select('*')
			->from('weinsteig_members')
			->orderBy('address')
			->executeQuery()
			->fetchAll();
	}

	/**
	 * Lade alle Nextcloud User-DisplayNames
	 */
	private function getUserNames(): array {
		$qb = $this->db->getQueryBuilder();
		$users = $qb->select('uid', 'displayname')
			->from('oc_users')
			->executeQuery()
			->fetchAll();

		$names = [];
		foreach ($users as $user) {
			$displayName = $user['displayname'] ?: $user['uid'];
			if (!empty($displayName)) {
				$names[$user['uid']] = $displayName;
			}
		}
		return $names;
	}

	/**
	 * Finde das Haus das einem User zugeordnet ist
	 */
	private function getMemberForUser(string $userId): ?int {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('member_id')
			->from('weinsteig_user_members')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->setMaxResults(1)
			->executeQuery()
			->fetch();

		return $result ? (int)$result['member_id'] : null;
	}

	private function saveZahlung(array $zahlung): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('weinsteig_zahlungen')
			->values([
				'buchungsdatum' => $qb->createNamedParameter($zahlung['buchungsdatum']),
				'valutadatum' => $qb->createNamedParameter($zahlung['valutadatum']),
				'partnername' => $qb->createNamedParameter($zahlung['partnername']),
				'verwendungszweck' => $qb->createNamedParameter($zahlung['verwendungszweck']),
				'betrag' => $qb->createNamedParameter($zahlung['betrag']),
				'waehrung' => $qb->createNamedParameter($zahlung['waehrung']),
				'iban' => $qb->createNamedParameter($zahlung['iban']),
				'bic' => $qb->createNamedParameter($zahlung['bic']),
				'member_id' => $qb->createNamedParameter($zahlung['member_id']),
				'match_type' => $qb->createNamedParameter($zahlung['match_type']),
				'match_confidence' => $qb->createNamedParameter($zahlung['match_confidence']),
				'status' => $qb->createNamedParameter($zahlung['status']),
				'created_at' => $qb->createNamedParameter($zahlung['created_at']),
			])
			->executeStatement();
	}

	/**
	 * Zahlung manuell zuordnen
	 */
	public function assignZahlung(int $zahlungId, int $memberId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->update('weinsteig_zahlungen')
			->set('member_id', $qb->createNamedParameter($memberId))
			->set('match_type', $qb->createNamedParameter('manual'))
			->set('match_confidence', $qb->createNamedParameter(100))
			->set('status', $qb->createNamedParameter('matched'))
			->set('updated_at', $qb->createNamedParameter((new DateTime())->format('Y-m-d H:i:s')))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($zahlungId)))
			->executeStatement();
		return true;
	}

	/**
	 * Zahlung auf unzugeordnet zurückstellen
	 */
	public function unassignZahlung(int $zahlungId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->update('weinsteig_zahlungen')
			->set('member_id', $qb->createNamedParameter(null))
			->set('match_type', $qb->createNamedParameter('pending'))
			->set('match_confidence', $qb->createNamedParameter(0))
			->set('status', $qb->createNamedParameter('pending'))
			->set('updated_at', $qb->createNamedParameter((new DateTime())->format('Y-m-d H:i:s')))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($zahlungId)))
			->executeStatement();
		return true;
	}

	/**
	 * Alle Zahlungen (pending und matched) für Übersicht
	 */
	public function getAllPendingAndMatched(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('z.*', 'm.address')
			->from('weinsteig_zahlungen', 'z')
			->leftJoin('z', 'weinsteig_members', 'm', $qb->expr()->eq('z.member_id', 'm.id'))
			->where($qb->expr()->orX(
				$qb->expr()->eq('z.status', $qb->createNamedParameter('pending')),
				$qb->expr()->eq('z.status', $qb->createNamedParameter('matched'))
			))
			->orderBy('z.status', 'ASC')
			->orderBy('z.valutadatum', 'DESC');
		return $qb->executeQuery()->fetchAll();
	}

	/**
	 * Nur unmatched Zahlungen
	 */
	public function getUnmatched(string $status = 'pending'): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('z.*', 'm.address')
			->from('weinsteig_zahlungen', 'z')
			->leftJoin('z', 'weinsteig_members', 'm', $qb->expr()->eq('z.member_id', 'm.id'))
			->where($qb->expr()->eq('z.status', $qb->createNamedParameter($status)))
			->orderBy('z.valutadatum', 'DESC');
		return $qb->executeQuery()->fetchAll();
	}

	/**
	 * Auto-Match: Versuche alle pending Zahlungen erneut zu matchen
	 */
	public function autoMatchAll(): array {
		// Alle pending Zahlungen laden
		$qb = $this->db->getQueryBuilder();
		$pending = $qb->select('*')
			->from('weinsteig_zahlungen')
			->where($qb->expr()->eq('status', $qb->createNamedParameter('pending')))
			->executeQuery()
			->fetchAll();

		// Alle Members für Matching
		$members = $this->getAllMembers();

		$matched = 0;
		foreach ($pending as $zahlung) {
			$match = $this->matchZahlung($zahlung, $members);
			if ($match['member_id']) {
				// Update wenn Match gefunden
				$qb = $this->db->getQueryBuilder();
				$qb->update('weinsteig_zahlungen')
					->set('member_id', $qb->createNamedParameter($match['member_id']))
					->set('match_type', $qb->createNamedParameter($match['type']))
					->set('match_confidence', $qb->createNamedParameter($match['confidence']))
					->set('status', $qb->createNamedParameter('matched'))
					->set('updated_at', $qb->createNamedParameter((new DateTime())->format('Y-m-d H:i:s')))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($zahlung['id'])))
					->executeStatement();
				$matched++;
			}
		}

		return ['matched' => $matched, 'total' => count($pending)];
	}

	/**
	 * Alle Zahlungen laden
	 */
	public function getAll(?int $memberId = null): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('z.*', 'm.address')
			->from('weinsteig_zahlungen', 'z')
			->leftJoin('z', 'weinsteig_members', 'm', $qb->expr()->eq('z.member_id', 'm.id'));

		if ($memberId !== null) {
			$qb->where($qb->expr()->eq('z.member_id', $qb->createNamedParameter($memberId)));
		}

		return $qb->orderBy('z.valutadatum', 'DESC')->executeQuery()->fetchAll();
	}
}
