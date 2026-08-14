<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Controller;

use OCA\WeinsteigFinance\AppInfo\Application;
use OCA\WeinsteigFinance\Util\IbanValidator;
use OCA\WeinsteigFinance\Service\MandateService;
use OCA\WeinsteigFinance\Service\VorschreibungService;
use OCA\WeinsteigFinance\Service\ZahlungService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IURLGenerator;
use DateTime;

class ApiController extends Controller {
	public function __construct(
		IRequest $request,
		private IDBConnection $db,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private MandateService $mandateService,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private VorschreibungService $vorschreibungService,
		private ZahlungService $zahlungService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	private function getUserId(): string {
		return $this->userSession->getUser()?->getUID() ?? '';
	}

	private function canEdit(): bool {
		$userId = $this->getUserId();
		return $this->groupManager->isInGroup($userId, 'obpersonen') || $this->groupManager->isInGroup($userId, 'mitglieder');
	}

	private function isObperson(): bool {
		return $this->groupManager->isInGroup($this->getUserId(), 'obpersonen');
	}

	private function canEditMember(int $memberId): bool {
		// Obpersonen dürfen alles bearbeiten
		if ($this->isObperson()) {
			return true;
		}

		// Mitglieder nur ihr eigenes Haus
		$userId = $this->getUserId();
		$qb = $this->db->getQueryBuilder();
		$exists = $qb->select('id')
			->from('weinsteig_user_members')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->executeQuery()
			->fetchOne();

		return $exists !== false;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function members(): DataResponse {
		// Nur obpersonen dürfen alle Häuser sehen
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$rows = $qb->select('*')
			->from('weinsteig_members')
			->orderBy('address')
			->executeQuery()
			->fetchAll();

		if ($this->request->getParam('loadAssignments') === '1') {
			$qb = $this->db->getQueryBuilder();
			$assignments = $qb->select('member_id', 'user_id')
				->from('weinsteig_user_members')
				->executeQuery()
				->fetchAll();
			return new DataResponse(['members' => $rows, 'assignments' => $assignments]);
		}

		return new DataResponse($rows);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function users(): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$users = [];
		foreach ($this->userManager->search('') as $user) {
			$users[] = ['uid' => $user->getUID(), 'displayName' => $user->getDisplayName()];
		}

		usort($users, fn($a, $b) => strcmp($a['displayName'] ?: $a['uid'], $b['displayName'] ?: $b['uid']));
		return new DataResponse($users);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getMember(int $id): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$row = $qb->select('*')
			->from('weinsteig_members')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery()
			->fetch();

		return new DataResponse($row ?: ['error' => 'Not found']);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function updateMember(int $id, ?string $zahlungspflichtig = null, ?string $iban = null, ?int $force = null): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot edit other members'], 403);
		}

		// Trim whitespace
		$zahlungspflichtig = $zahlungspflichtig ? trim($zahlungspflichtig) : null;
		$iban = $iban ? preg_replace('/\s+/', '', trim($iban)) : null;

		if (!$force && $iban && !IbanValidator::validate($iban)) {
			return new DataResponse(['error' => 'IBAN invalid'], 400);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->update('weinsteig_members')
			->set('zahlungspflichtig', $qb->createNamedParameter($zahlungspflichtig))
			->set('iban', $qb->createNamedParameter($iban));

		// Setze mandate_granted_date wenn IBAN gespeichert wird
		if ($iban) {
			$now = (new DateTime())->format('Y-m-d H:i:s');
			$qb->set('mandate_granted_date', $qb->createNamedParameter($now));
		}

		$qb->set('mandate_withdrawn_date', $qb->createNamedParameter(null))
			->set('mandate_withdrawn_reason', $qb->createNamedParameter(null))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();

		return new DataResponse(['success' => true]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function assignUser(int $memberId, string $userId): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		try {
			$qb->insert('weinsteig_user_members')
				->values(['member_id' => $qb->createNamedParameter($memberId), 'user_id' => $qb->createNamedParameter($userId)])
				->executeStatement();
			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function unassignUser(int $memberId, string $userId): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->delete('weinsteig_user_members')
			->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->executeStatement();

		return new DataResponse(['success' => true]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function withdrawMandate(int $id, ?string $reason = null): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot edit other members'], 403);
		}

		$now = (new DateTime())->format('Y-m-d H:i:s');
		$qb = $this->db->getQueryBuilder();
		$qb->update('weinsteig_members')
			->set('mandate_withdrawn_date', $qb->createNamedParameter($now))
			->set('mandate_withdrawn_reason', $qb->createNamedParameter($reason))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeStatement();

		return new DataResponse(['success' => true]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function mandatePdf(int $id) {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot access other members'], 403);
		}

		try {
			$pdf = $this->mandateService->generateMandatePdf($id);
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="mandat.pdf"');
			echo $pdf;
			exit;
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function uploadSignedMandate(int $id) {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot edit other members'], 403);
		}

		if (empty($_FILES['file'])) {
			return new DataResponse(['error' => 'No file provided'], 400);
		}

		try {
			$file = $_FILES['file'];
			if ($file['error'] !== UPLOAD_ERR_OK) {
				return new DataResponse(['error' => 'Upload error: ' . $file['error']], 400);
			}

			// Daten laden
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('*')
				->from('weinsteig_members')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeQuery()
				->fetch();

			if (!$member) {
				return new DataResponse(['error' => 'Member not found'], 404);
			}

			$address = $member['address'];
			$pdfContent = file_get_contents($file['tmp_name']);

			// Im Nextcloud data/-Verzeichnis speichern
			$dataDir = $this->config->getSystemValue('datadirectory');
			$folderPath = "$dataDir/generated/{$address}/sepa";

			// Ordner erstellen
			@mkdir($folderPath, 0750, true);

			// Versionsnummer ermitteln
			$v = 1;
			while (file_exists("$folderPath/mandat_unterschrieben_v{$v}.pdf")) {
				$v++;
			}

			$filePath = "$folderPath/mandat_unterschrieben_v{$v}.pdf";

			// Datei speichern
			file_put_contents($filePath, $pdfContent);

			return new DataResponse(['success' => true]);
		} catch (\Throwable $e) {
			\OCP\Server::get(\OCP\Log\ILogFactory::class)->getLogFile()?->log(0, 'Upload error: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
			return new DataResponse(['error' => $e->getMessage() ?: 'Upload failed'], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getSignedMandate(int $id) {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot access other members'], 403);
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('*')
				->from('weinsteig_members')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeQuery()
				->fetch();

			if (!$member) {
				return new DataResponse(['error' => 'Member not found'], 404);
			}

			$address = $member['address'];
			$dataDir = $this->config->getSystemValue('datadirectory');
			$folderPath = "$dataDir/generated/{$address}/sepa";

			// Alle Versionen sammeln
			$files = [];
			if (is_dir($folderPath)) {
				$dir = scandir($folderPath, SCANDIR_SORT_DESCENDING);
				foreach ($dir as $file) {
					if (preg_match('/^mandat_unterschrieben_v(\d+)\.pdf$/', $file, $m)) {
						$files[] = [
							'version' => (int)$m[1],
							'filename' => $file,
							'downloadUrl' => $this->urlGenerator->linkToRoute('weinsteigfinance.api.downloadSignedMandate', ['id' => $id, 'v' => (int)$m[1]]),
							'mtime' => filemtime("$folderPath/$file")
						];
					}
				}
			}

			if (count($files) > 0) {
				return new DataResponse(['exists' => true, 'files' => $files]);
			} else {
				return new DataResponse(['exists' => false]);
			}
		} catch (\Throwable $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function downloadSignedMandate(int $id, ?int $v = null) {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot access other members'], 403);
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('*')
				->from('weinsteig_members')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeQuery()
				->fetch();

			if (!$member) {
				return new DataResponse(['error' => 'Member not found'], 404);
			}

			$address = $member['address'];
			$dataDir = $this->config->getSystemValue('datadirectory');
			$folderPath = "$dataDir/generated/{$address}/sepa";

			// Versionsnummer bestimmen (default: neueste)
			if ($v === null) {
				$highestV = 0;
				if (is_dir($folderPath)) {
					foreach (scandir($folderPath) as $file) {
						if (preg_match('/^mandat_unterschrieben_v(\d+)\.pdf$/', $file, $m)) {
							$highestV = max($highestV, (int)$m[1]);
						}
					}
				}
				if ($highestV === 0) {
					return new DataResponse(['error' => 'File not found'], 404);
				}
				$v = $highestV;
			}

			$filePath = "$folderPath/mandat_unterschrieben_v{$v}.pdf";

			if (!file_exists($filePath)) {
				return new DataResponse(['error' => 'File not found'], 404);
			}

			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="' . urlencode($address) . '_mandat_v' . $v . '.pdf"');
			readfile($filePath);
			exit;
		} catch (\Throwable $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenImport(): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$csvContent = $this->request->getParam('csv');
			if (!$csvContent) {
				return new DataResponse(['error' => 'No CSV provided'], 400);
			}

			// Speichere CSV mit Zeitstempel
			$dataDir = $this->config->getSystemValue('datadirectory');
			$importDir = "$dataDir/imports";
			@mkdir($importDir, 0750, true);
			$timestamp = (new DateTime())->format('Y-m-d_H-i-s');
			$csvFile = "$importDir/zahlungen_$timestamp.csv";
			file_put_contents($csvFile, $csvContent);

			// Speichere Zeitstempel
			$this->config->setAppValue('weinsteigfinance', 'last_zahlungen_import', (new DateTime())->format('Y-m-d H:i:s'));

			$result = $this->zahlungService->importFromCsv($csvContent);
			return new DataResponse($result);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenGetUnmatched(): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$allZahlungen = $this->zahlungService->getAllPendingAndMatched();
			$qb = $this->db->getQueryBuilder();
			$members = $qb->select('*')
				->from('weinsteig_members')
				->orderBy('address')
				->executeQuery()
				->fetchAll();

			// Gruppiere nach Status (und neu indizieren)
			$pending = array_values(array_filter($allZahlungen, fn($z) => $z['status'] === 'pending'));
			$matched = array_values(array_filter($allZahlungen, fn($z) => $z['status'] === 'matched'));

			$lastImport = $this->config->getAppValue('weinsteigfinance', 'last_zahlungen_import');

			return new DataResponse([
				'unmatched' => $pending,
				'matched' => $matched,
				'members' => $members,
				'lastImport' => $lastImport ?: null,
				'stats' => [
					'pending' => count($pending),
					'matched' => count($matched),
					'total' => count($allZahlungen)
				]
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenAssign(int $zahlungId, int $memberId): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$this->zahlungService->assignZahlung($zahlungId, $memberId);
			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenUnassign(int $zahlungId): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$this->zahlungService->unassignZahlung($zahlungId);
			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenAutoMatch(): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$result = $this->zahlungService->autoMatchAll();
			return new DataResponse([
				'success' => true,
				'matched' => $result['matched'],
				'total' => $result['total'],
				'message' => $result['matched'] . ' von ' . $result['total'] . ' Zahlungen neu zugeordnet'
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenGet(?int $memberId = null): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			// Mitglieder sehen nur ihre eigenen Zahlungen
			if (!$this->isObperson()) {
				$userId = $this->getUserId();
				$qb = $this->db->getQueryBuilder();
				$member = $qb->select('m.id')
					->from('weinsteig_members', 'm')
					->innerJoin('m', 'weinsteig_user_members', 'um', $qb->expr()->eq('m.id', 'um.member_id'))
					->where($qb->expr()->eq('um.user_id', $qb->createNamedParameter($userId)))
					->setMaxResults(1)
					->executeQuery()
					->fetch();
				if (!$member) {
					return new DataResponse(['error' => 'Not found'], 404);
				}
				$memberId = $member['id'];
			}

			$zahlungen = $this->zahlungService->getAll($memberId);
			return new DataResponse(['zahlungen' => $zahlungen]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenUebersicht(?int $month = null): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			// Mitglieder sehen nur ihre eigenen Zahlungen
			$userId = $this->getUserId();
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('m.*')
				->from('weinsteig_members', 'm')
				->innerJoin('m', 'weinsteig_user_members', 'um', $qb->expr()->eq('m.id', 'um.member_id'))
				->where($qb->expr()->eq('um.user_id', $qb->createNamedParameter($userId)))
				->setMaxResults(1)
				->executeQuery()
				->fetch();

			if (!$member) {
				return new DataResponse([
					'error' => 'Kein Haus zugewiesen',
					'message' => 'Sie sind noch nicht mit einem Haus verknüpft. Bitte kontaktieren Sie einen Administrator.'
				], 404);
			}

			$memberId = $member['id'];

			// Lade alle Zahlungen für dieses Mitglied
			$qb = $this->db->getQueryBuilder();
			$zahlungen = $qb->select('*')
				->from('weinsteig_zahlungen')
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
				->orderBy('valutadatum', 'DESC')
				->executeQuery()
				->fetchAll();

			// Berechne Statistik
			$gesamt = 0;
			$zugeordnet = 0;
			$unzugeordnet = 0;

			foreach ($zahlungen as $z) {
				$gesamt += (float)$z['betrag'];
				if ($z['status'] === 'matched') {
					$zugeordnet += (float)$z['betrag'];
				} else {
					$unzugeordnet += (float)$z['betrag'];
				}
			}

			return new DataResponse([
				'member' => $member,
				'zahlungen' => $zahlungen,
				'stats' => [
					'gesamt' => round($gesamt, 2),
					'zugeordnet' => round($zugeordnet, 2),
					'unzugeordnet' => round($unzugeordnet, 2),
					'count' => count($zahlungen)
				]
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Fehler: ' . $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function myMember(): DataResponse {
		$userId = $this->getUserId();
		if (!$userId || !$this->groupManager->isInGroup($userId, 'mitglieder')) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$row = $qb->select('m.*')
			->from('weinsteig_members', 'm')
			->innerJoin('m', 'weinsteig_user_members', 'um', $qb->expr()->eq('m.id', 'um.member_id'))
			->where($qb->expr()->eq('um.user_id', $qb->createNamedParameter($userId)))
			->setMaxResults(1)
			->executeQuery()
			->fetch();

		return new DataResponse($row ?: ['error' => 'Not found']);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function generateVorschreibungen(int $year, int $month): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$generated = $this->vorschreibungService->generateAllForMonth($year, $month);
			return new DataResponse([
				'success' => true,
				'count' => count($generated),
				'message' => 'Generiert: ' . count($generated) . ' Vorschreibungen für ' . sprintf('%02d', $month) . '/' . $year
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getVorschreibungen(): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		// Berechne alle Monate von Mai 2026 bis heute
		$months = [];
		$startDate = new DateTime('2026-05-01');
		$endDate = new DateTime();
		$endDate->modify('last day of this month');

		$current = clone $startDate;
		while ($current <= $endDate) {
			$months[] = [
				'year' => (int)$current->format('Y'),
				'month' => (int)$current->format('m'),
				'label' => $current->format('F Y') // Mai 2026, Juni 2026, etc.
			];
			$current->modify('first day of next month');
		}

		// Hol Cron-Status für obpersonen
		$cronStatus = null;
		if ($this->isObperson()) {
			$lastCronRun = $this->config->getAppValue('weinsteigfinance', 'last_cron_run');
			$lastGenerated = $this->config->getAppValue('weinsteigfinance', 'last_vorschreibungen_generated');
			$cronStatus = [
				'lastRun' => $lastCronRun ?: null,
				'lastGenerated' => $lastGenerated ?: null,
			];
		}

		// Obpersonen sehen alle Häuser
		if ($this->isObperson()) {
			$qb = $this->db->getQueryBuilder();
			$members = $qb->select('*')
				->from('weinsteig_members')
				->orderBy('address')
				->executeQuery()
				->fetchAll();
			$members = $this->enrichMembersWithVorschreibungDates($members, $months);
			return new DataResponse(['months' => $months, 'members' => $members, 'isObperson' => true, 'cronStatus' => $cronStatus]);
		}

		// Mitglieder sehen nur ihr Haus
		$userId = $this->getUserId();
		$qb = $this->db->getQueryBuilder();
		$member = $qb->select('m.*')
			->from('weinsteig_members', 'm')
			->innerJoin('m', 'weinsteig_user_members', 'um', $qb->expr()->eq('m.id', 'um.member_id'))
			->where($qb->expr()->eq('um.user_id', $qb->createNamedParameter($userId)))
			->setMaxResults(1)
			->executeQuery()
			->fetch();

		if (!$member) {
			return new DataResponse(['error' => 'Member not found'], 404);
		}

		$members = [$member];
		$members = $this->enrichMembersWithVorschreibungDates($members, $months);
		return new DataResponse(['months' => $months, 'members' => $members, 'isObperson' => false, 'cronStatus' => $cronStatus]);
	}

	private function enrichMembersWithVorschreibungDates(array $members, array $months): array {
		$dataDir = $this->config->getSystemValue('datadirectory');

		foreach ($members as &$member) {
			$member['vorschreibungen'] = [];
			$address = $member['address'];

			foreach ($months as $month) {
				$filename = sprintf('%04d-%02d-vorschreibung.pdf', $month['year'], $month['month']);
				$filePath = "$dataDir/generated/{$address}/vorschreibungen/$filename";

				if (file_exists($filePath)) {
					$mtime = filemtime($filePath);
					$member['vorschreibungen'][$month['year'] . '-' . sprintf('%02d', $month['month'])] = [
						'exists' => true,
						'mtime' => $mtime,
						'date' => (new DateTime())->setTimestamp($mtime)->format('d.m.Y H:i')
					];
				} else {
					$member['vorschreibungen'][$month['year'] . '-' . sprintf('%02d', $month['month'])] = [
						'exists' => false
					];
				}
			}
		}

		return $members;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function vorschreibungPdf(int $id, string $month) {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot access other members'], 403);
		}

		try {
			// Parse month (format: 2026-05 oder 202605)
			$parts = preg_split('/[-\/]/', $month);
			if (count($parts) !== 2) {
				return new DataResponse(['error' => 'Invalid month format'], 400);
			}
			$year = (int)$parts[0];
			$m = (int)$parts[1];

			// Mitglied laden
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('*')
				->from('weinsteig_members')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeQuery()
				->fetch();

			if (!$member) {
				return new DataResponse(['error' => 'Member not found'], 404);
			}

			$address = $member['address'];
			$dataDir = $this->config->getSystemValue('datadirectory');
			$filename = sprintf('%04d-%02d-vorschreibung.pdf', $year, $m);
			$filePath = "$dataDir/generated/{$address}/vorschreibungen/$filename";

			// Prüfe ob gespeicherte PDF existiert
			if (file_exists($filePath)) {
				header('Content-Type: application/pdf');
				header('Content-Disposition: attachment; filename="' . $filename . '"');
				readfile($filePath);
				exit;
			}

			// Fallback: Generate on-demand (falls noch nicht generiert)
			$pdf = $this->generateVorschreibungPdf($member, $year, $m);
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="vorschreibung_' . $year . sprintf('%02d', $m) . '.pdf"');
			echo $pdf;
			exit;
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	private function generateVorschreibungPdf(array $member, int $year, int $month): string {
		$address = $member['address'];
		$iban = $member['iban'] ?? '';
		$mandateGrantedDate = $member['mandate_granted_date'] ?? null;

		// Deutsche Monatsnamen
		$months = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
		$monthName = $months[$month] . ' ' . $year;
		$today = (new DateTime())->format('d.m.Y');

		// Format mandate granted date
		$mandateDateText = '';
		if ($mandateGrantedDate) {
			try {
				$mandateDate = new DateTime($mandateGrantedDate);
				$mandateDateText = 'Mandatserteilung: ' . $mandateDate->format('d.m.Y');
			} catch (\Exception) {
				$mandateDateText = 'Mandatserteilung: ' . $mandateGrantedDate;
			}
		}

		// Belastungskonto: Immer Genossenschaft anzeigen, plus optional Mitglied-IBAN
		$bankAccount = '<strong>Energiegenossenschaft Weinsteig</strong><br>';
		$bankAccount .= 'IBAN: AT822011185788107800<br>';
		$bankAccount .= 'BIC: GIBATWWXXX<br>';

		if ($iban) {
			$bankAccount .= '<br><strong>Ihr hinterlegtes Konto:</strong><br>';
			$bankAccount .= $address . '<br>';
			$bankAccount .= 'IBAN: ' . $iban;
		}

		// Ausgestellt: 1. des Monats der Vorschreibung (rechtlich korrekt)
		$issuedDate = (new DateTime("$year-$month-01"))->format('d.m.Y');

		$html = <<<HTML
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; margin: 20px; }
h2 { font-size: 14pt; margin-bottom: 10px; }
.section { margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
td { padding: 5px; border: 1px solid #ddd; }
.label { font-weight: bold; }
.amount { font-size: 14pt; font-weight: bold; color: #d9534f; }
.note { font-size: 9pt; color: #666; margin-top: 20px; line-height: 1.3; }
</style>
</head>
<body>

<h2>Vorschreibung</h2>

<div class="section">
<strong>Energiegenossenschaft Weinsteig</strong><br>
Weinsteig 19a<br>
5082 Glanegg<br>
Österreich
</div>

<div class="section">
<strong>Rechnungsempfänger</strong><br>
Energiegenossenschaft Weinsteig
</div>

<div class="section">
<strong>Liegenschaft</strong><br>
{$address}
</div>

<div class="section">
<strong>Rechnungszeitraum</strong><br>
{$monthName}
</div>

<table>
<tr><td class="label">Akontozahlung</td><td style="text-align: right; width: 150px;">€ 60,00</td></tr>
<tr style="background: #f5f5f5;"><td class="label" style="border-top: 2px solid black; padding-top: 10px;"><strong>Gesamtbetrag fällig</strong></td><td style="border-top: 2px solid black; padding-top: 10px; text-align: right;"><span class="amount">€ 60,00</span></td></tr>
</table>

<div class="section" style="margin-top: 20px;">
<strong>Der fällige Betrag wird von folgendem Konto eingezogen:</strong><br><br>
{$bankAccount}
</div>

<div class="note">
<strong>Mandatsinformation:</strong><br>
{$mandateDateText}<br><br>
<strong>Widerrufsrecht:</strong> Das SEPA-Lastschrift-Mandat kann jederzeit online über das Kundencenter der Energiegenossenschaft Weinsteig widerrufen werden.
</div>

<div style="margin-top: 30px; font-size: 9pt; color: #666;">
Ausgestellt: {$issuedDate}
</div>

</body>
</html>
HTML;

		$mpdf = new \Mpdf\Mpdf(['default_font_size' => 11, 'default_font' => 'Arial']);
		$mpdf->WriteHTML($html);
		return $mpdf->Output('', 'S');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function memberJournal(?int $memberId = null): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			// Mitglieder sehen nur ihre eigenen Daten
			if (!$this->isObperson()) {
				$userId = $this->getUserId();
				$qb = $this->db->getQueryBuilder();
				$member = $qb->select('m.id')
					->from('weinsteig_members', 'm')
					->innerJoin('m', 'weinsteig_user_members', 'um', $qb->expr()->eq('m.id', 'um.member_id'))
					->where($qb->expr()->eq('um.user_id', $qb->createNamedParameter($userId)))
					->setMaxResults(1)
					->executeQuery()
					->fetch();
				if (!$member) {
					return new DataResponse(['error' => 'Not found'], 404);
				}
				$memberId = $member['id'];
			}

			if (!$memberId) {
				return new DataResponse(['error' => 'memberId required'], 400);
			}

			// Lade alle Vorschreibungen
			$vorschreibungen = $this->db->getQueryBuilder()
				->select('*')
				->from('weinsteig_vorschreibungen')
				->where($this->db->getQueryBuilder()->expr()->eq('member_id', $this->db->getQueryBuilder()->createNamedParameter($memberId)))
				->orderBy('year', 'DESC')
				->addOrderBy('month', 'DESC')
				->executeQuery()
				->fetchAll();

			// Lade alle Zahlungen
			$zahlungen = $this->db->getQueryBuilder()
				->select('*')
				->from('weinsteig_zahlungen')
				->where($this->db->getQueryBuilder()->expr()->eq('member_id', $this->db->getQueryBuilder()->createNamedParameter($memberId)))
				->orderBy('valutadatum', 'DESC')
				->executeQuery()
				->fetchAll();

			// Berechne Statistiken
			$totalVorschreibungen = 0;
			$paidVorschreibungen = 0;
			$openVorschreibungen = 0;

			foreach ($vorschreibungen as $v) {
				$totalVorschreibungen += (float)$v['amount'];
				if ($v['status'] === 'paid') {
					$paidVorschreibungen += (float)$v['amount'];
				} else {
					$openVorschreibungen += (float)$v['amount'];
				}
			}

			$totalZahlungen = 0;
			foreach ($zahlungen as $z) {
				$totalZahlungen += (float)$z['betrag'];
			}

			// Saldo = eingegangene Zahlungen - ausstehende Vorschreibungen
			$saldo = $totalZahlungen - $openVorschreibungen;

			return new DataResponse([
				'vorschreibungen' => $vorschreibungen,
				'zahlungen' => $zahlungen,
				'stats' => [
					'totalVorschreibungen' => round($totalVorschreibungen, 2),
					'paidVorschreibungen' => round($paidVorschreibungen, 2),
					'openVorschreibungen' => round($openVorschreibungen, 2),
					'totalZahlungen' => round($totalZahlungen, 2),
					'saldo' => round($saldo, 2),
				]
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Fehler: ' . $e->getMessage()], 400);
		}
	}
}
