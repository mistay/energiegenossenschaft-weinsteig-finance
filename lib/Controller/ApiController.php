<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Controller;

use OCA\WeinsteigFinance\AppInfo\Application;
use OCA\WeinsteigFinance\Util\IbanValidator;
use OCA\WeinsteigFinance\Service\ConfigService;
use OCA\WeinsteigFinance\Service\MandateService;
use OCA\WeinsteigFinance\Service\VorschreibungService;
use OCA\WeinsteigFinance\Service\ZahlungService;
use OCA\WeinsteigFinance\Service\BackupService;
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
		private ConfigService $configService,
		private BackupService $backupService,
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
		// Obpersonen und kassier:innen dürfen alles bearbeiten
		if ($this->isObperson()) {
			return true;
		}
		$userId = $this->getUserId();
		if ($this->groupManager->isInGroup($userId, 'kassier:innen')) {
			return true;
		}

		// Mitglieder nur ihr eigenes Haus
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
		// Nur obpersonen und kassier:innen dürfen alle Häuser sehen
		$userId = $this->getUserId();
		$isKassier = $this->groupManager->isInGroup($userId, 'kassier:innen');
		if (!$this->isObperson() && !$isKassier) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$rows = $qb->select('*')
			->from('weinsteig_members')
			->orderBy('address')
			->executeQuery()
			->fetchAll();

		// Berechne offene Beträge für jedes Mitglied (exakt wie memberJournal)
		foreach ($rows as &$row) {
			$memberId = $row['id'];

			// ALLE Zahlungen für dieses Mitglied (keine Status-Filter!)
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

			// ALLE Vorschreibungen für dieses Mitglied
			$qb = $this->db->getQueryBuilder();
			$vorschreibungen = $qb->select('*')
				->from('weinsteig_vorschreibungen')
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
				->executeQuery()
				->fetchAll();

			$openVorschreibungen = 0;
			foreach ($vorschreibungen as $v) {
				// Zähle nur offene (nicht bezahlte) Vorschreibungen
				if ($v['status'] !== 'paid') {
					$openVorschreibungen += (float)$v['amount'];
				}
			}

			// Saldo = eingegangene Zahlungen - offene Vorschreibungen (wie memberJournal)
			$row['open_amount'] = round($totalZahlungen - $openVorschreibungen, 2);

			// Lade zugeordnete Benutzer
			try {
				$qb = $this->db->getQueryBuilder();
				$userRows = $qb->select('um.user_id')
					->from('weinsteig_user_members', 'um')
					->where($qb->expr()->eq('um.member_id', $qb->createNamedParameter($memberId)))
					->executeQuery()
					->fetchAll();
				$row['assigned_users'] = array_map(fn($u) => $u['user_id'], $userRows);
			} catch (\Exception $e) {
				$row['assigned_users'] = [];
			}
		}

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
	public function getConfig(): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		return new DataResponse([
			'creditorId' => $this->configService->getCreditorId(),
			'creditorIban' => $this->configService->getCreditorIban(),
			'creditorBic' => $this->configService->getCreditorBic(),
		]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function updateConfig(?string $creditorId = null, ?string $creditorIban = null, ?string $creditorBic = null): DataResponse {
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if ($creditorId !== null) {
			$creditorId = strtoupper(str_replace(' ', '', trim($creditorId)));

			// Creditor ID: Länderkürzel, 2 Prüfziffern, 3 Zeichen Geschäftsbereich, nationale Kennung
			if ($creditorId !== '' && !preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{3}[A-Z0-9]{1,28}$/', $creditorId)) {
				return new DataResponse(['error' => 'Ungültige Creditor ID'], 400);
			}

			$this->configService->set(ConfigService::KEY_CREDITOR_ID, $creditorId);
		}

		if ($creditorIban !== null) {
			$creditorIban = strtoupper(str_replace(' ', '', trim($creditorIban)));

			if ($creditorIban !== '' && !IbanValidator::validate($creditorIban)) {
				return new DataResponse(['error' => 'Ungültige IBAN'], 400);
			}

			$this->configService->set(ConfigService::KEY_CREDITOR_IBAN, $creditorIban);
		}

		if ($creditorBic !== null) {
			$creditorBic = strtoupper(str_replace(' ', '', trim($creditorBic)));

			// BIC: 4 Zeichen Bank, 2 Zeichen Land, 2 Zeichen Ort, optional 3 Zeichen Filiale
			if ($creditorBic !== '' && !preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $creditorBic)) {
				return new DataResponse(['error' => 'Ungültiger BIC'], 400);
			}

			$this->configService->set(ConfigService::KEY_CREDITOR_BIC, $creditorBic);
		}

		return new DataResponse([
			'success' => true,
			'creditorId' => $this->configService->getCreditorId(),
			'creditorIban' => $this->configService->getCreditorIban(),
			'creditorBic' => $this->configService->getCreditorBic(),
		]);
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
			return new DataResponse(['error' => 'Keine Datei hochgeladen'], 400);
		}

		try {
			$file = $_FILES['file'];

			// Sprechende Fehlermeldungen für Upload-Fehler
			if ($file['error'] !== UPLOAD_ERR_OK) {
				$errorMessages = [
					UPLOAD_ERR_INI_SIZE => 'Datei ist größer als in php.ini definiert (upload_max_filesize)',
					UPLOAD_ERR_FORM_SIZE => 'Datei ist größer als in MAX_FILE_SIZE definiert',
					UPLOAD_ERR_PARTIAL => 'Datei wurde nur teilweise hochgeladen',
					UPLOAD_ERR_NO_FILE => 'Keine Datei wurde hochgeladen',
					UPLOAD_ERR_NO_TMP_DIR => 'Temporärer Ordner fehlt',
					UPLOAD_ERR_CANT_WRITE => 'Fehler beim Schreiben der Datei auf den Server',
					UPLOAD_ERR_EXTENSION => 'Upload wurde durch eine PHP-Extension gestoppt',
				];
				$errorMsg = $errorMessages[$file['error']] ?? 'Unbekannter Upload-Fehler (' . $file['error'] . ')';

				// Besondere Behandlung für zu große Dateien
				if ($file['error'] === UPLOAD_ERR_INI_SIZE) {
					$errorMsg = 'Datei ist zu groß! Bitte komprimieren Sie das PDF und versuchen Sie es erneut. ' .
						'Die maximale Dateigröße ist auf dem Server begrenzt.';
				}

				return new DataResponse(['error' => $errorMsg], 400);
			}

			// Zusätzliche Größenprüfung auf Server-Seite (PHP Limits verwenden)
			$uploadMaxFilesize = ini_get('upload_max_filesize');
			$postMaxSize = ini_get('post_max_size');
			$maxSize = min($this->parsePhpSize($uploadMaxFilesize), $this->parsePhpSize($postMaxSize));

			if ($file['size'] > $maxSize) {
				$sizeMB = round($file['size'] / (1024 * 1024), 2);
				$maxMB = round($maxSize / (1024 * 1024), 1);
				return new DataResponse([
					'error' => sprintf('Datei ist zu groß (%.1f MB). Maximal %.1f MB erlaubt.', $sizeMB, $maxMB)
				], 400);
			}

			// Daten laden
			$qb = $this->db->getQueryBuilder();
			$member = $qb->select('*')
				->from('weinsteig_members')
				->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
				->executeQuery()
				->fetch();

			if (!$member) {
				return new DataResponse(['error' => 'Mitglied nicht gefunden'], 404);
			}

			$address = $member['address'];
			$fileContent = file_get_contents($file['tmp_name']);

			// Dateiendung validieren (Original-Extension beibehalten)
			$originalName = basename($file['name']);
			$fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
			$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

			if (!in_array($fileExt, $allowedExtensions)) {
				return new DataResponse([
					'error' => 'Nicht erlaubter Dateityp: .' . htmlspecialchars($fileExt) .
						'. Erlaubt sind: PDF, JPG, JPEG, PNG'
				], 400);
			}

			// MIME-Type zusätzlich prüfen
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $file['tmp_name']);
			finfo_close($finfo);

			$allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
			if (!in_array($mimeType, $allowedMimeTypes)) {
				return new DataResponse([
					'error' => 'Datei ist kein gültiges PDF oder Bild (JPEG/PNG). ' .
						'Bitte laden Sie ein unterschriebenes PDF oder Foto hoch.'
				], 400);
			}

			// Im Nextcloud data/-Verzeichnis speichern
			$dataDir = $this->config->getSystemValue('datadirectory');
			$folderPath = "$dataDir/generated/{$address}/sepa";

			// Ordner erstellen
			@mkdir($folderPath, 0750, true);

			// Versionsnummer ermitteln (mit flexibler Extension)
			$v = 1;
			while (file_exists("$folderPath/mandat_unterschrieben_v{$v}.{$fileExt}")) {
				$v++;
			}

			$filePath = "$folderPath/mandat_unterschrieben_v{$v}.{$fileExt}";

			// Datei speichern
			file_put_contents($filePath, $fileContent);

			return new DataResponse(['success' => true]);
		} catch (\Throwable $e) {
			\OCP\Server::get(\OCP\Log\ILogFactory::class)->getLogFile()?->log(0, 'Upload error: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
			return new DataResponse(['error' => 'Upload-Fehler: ' . ($e->getMessage() ?: 'Datei konnte nicht gespeichert werden')], 400);
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
					if (preg_match('/^mandat_unterschrieben_v(\d+)\.(pdf|jpg|jpeg|png)$/', $file, $m)) {
						$version = (int)$m[1];

						// Approval-Status laden
						$qb = $this->db->getQueryBuilder();
						$approval = $qb->select('*')
							->from('weinsteig_mandate_approvals')
							->where($qb->expr()->eq('member_id', $qb->createNamedParameter($id)))
							->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($version)))
							->executeQuery()
							->fetch();

						$files[] = [
							'version' => $version,
							'filename' => $file,
							'downloadUrl' => $this->urlGenerator->linkToRoute('weinsteigfinance.api.downloadSignedMandate', ['id' => $id, 'v' => $version]),
							'mtime' => filemtime("$folderPath/$file"),
							'approved' => $approval ? (bool)$approval['approved'] : false,
							'approved_by' => $approval ? $approval['approved_by'] : null,
							'approved_at' => $approval ? $approval['approved_at'] : null,
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
	public function approveMandatePdf(int $id, int $v): DataResponse {
		// Nur obpersonen und kassier:innen dürfen approven
		$userId = $this->getUserId();
		$isKassier = $this->groupManager->isInGroup($userId, 'kassier:innen');
		if (!$this->isObperson() && !$isKassier) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot edit other members'], 403);
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$approval = $qb->select('*')
				->from('weinsteig_mandate_approvals')
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($v)))
				->executeQuery()
				->fetch();

			$now = (new DateTime())->format('Y-m-d H:i:s');

			if ($approval) {
				// Update existing approval
				$qb = $this->db->getQueryBuilder();
				$qb->update('weinsteig_mandate_approvals')
					->set('approved', $qb->createNamedParameter(true))
					->set('approved_by', $qb->createNamedParameter($userId))
					->set('approved_at', $qb->createNamedParameter($now))
					->where($qb->expr()->eq('member_id', $qb->createNamedParameter($id)))
					->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($v)))
					->executeStatement();
			} else {
				// Create new approval record
				$qb = $this->db->getQueryBuilder();
				$qb->insert('weinsteig_mandate_approvals')
					->values([
						'member_id' => $qb->createNamedParameter($id),
						'version' => $qb->createNamedParameter($v),
						'approved' => $qb->createNamedParameter(true),
						'approved_by' => $qb->createNamedParameter($userId),
						'approved_at' => $qb->createNamedParameter($now),
						'created_at' => $qb->createNamedParameter($now),
					])
					->executeStatement();
			}

			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function deleteSignedMandatePdf(int $id, int $v): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		if (!$this->canEditMember($id)) {
			return new DataResponse(['error' => 'Cannot edit other members'], 403);
		}

		try {
			// Approval-Status laden
			$qb = $this->db->getQueryBuilder();
			$approval = $qb->select('*')
				->from('weinsteig_mandate_approvals')
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($id)))
				->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($v)))
				->executeQuery()
				->fetch();

			// Nur Kassier:innen und obpersonen dürfen approvte Dateien löschen
			$userId = $this->getUserId();
			$isKassier = $this->groupManager->isInGroup($userId, 'kassier:innen');
			if ($approval && $approval['approved'] && !$this->isObperson() && !$isKassier) {
				return new DataResponse(['error' => 'Cannot delete approved mandates'], 403);
			}

			// Datei löschen
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
			$filePath = "$folderPath/mandat_unterschrieben_v{$v}.pdf";

			if (file_exists($filePath)) {
				unlink($filePath);
			}

			// Approval-Record löschen
			if ($approval) {
				$qb = $this->db->getQueryBuilder();
				$qb->delete('weinsteig_mandate_approvals')
					->where($qb->expr()->eq('member_id', $qb->createNamedParameter($id)))
					->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($v)))
					->executeStatement();
			}

			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
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
						if (preg_match('/^mandat_unterschrieben_v(\d+)\.(pdf|jpg|jpeg|png)$/', $file, $m)) {
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
	public function appVersion(): DataResponse {
		// Read version from info.xml
		$infoPath = __DIR__ . '/../../appinfo/info.xml';
		try {
			if (file_exists($infoPath)) {
				$xml = @simplexml_load_file($infoPath);
				if ($xml !== false && isset($xml->version)) {
					$version = (string)$xml->version;
					if (!empty($version)) {
						return new DataResponse(['version' => $version]);
					}
				}
			}
		} catch (\Exception $e) {
			// Silently fall through to default
		}
		return new DataResponse(['version' => '1.3.0']);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getBackupStatus(): DataResponse {
		// Nur Obpersonen dürfen Backup-Status sehen
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$info = $this->backupService->getBackupInfo();
			return new DataResponse($info);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function downloadBackup(string $filename) {
		// Nur Obpersonen dürfen Backups downloaden
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			// Security: nur ZIP-Dateien mit richtigem Naming-Pattern
			if (!preg_match('/^weinsteig-finance-backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
				return new DataResponse(['error' => 'Invalid filename'], 400);
			}

			$dataDir = $this->config->getSystemValue('datadirectory');
			$backupFile = "$dataDir/backup/$filename";

			if (!file_exists($backupFile)) {
				return new DataResponse(['error' => 'Backup not found'], 404);
			}

			// Download as ZIP
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			header('Content-Length: ' . filesize($backupFile));

			readfile($backupFile);
			exit;
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function uploadLimits(): DataResponse {
		// Get PHP upload limits
		$uploadMaxFilesize = ini_get('upload_max_filesize');
		$postMaxSize = ini_get('post_max_size');

		// Convert to bytes
		$uploadMaxBytes = $this->parsePhpSize($uploadMaxFilesize);
		$postMaxBytes = $this->parsePhpSize($postMaxSize);

		// Use the smaller of the two limits
		$maxBytes = min($uploadMaxBytes, $postMaxBytes);

		return new DataResponse([
			'maxBytes' => $maxBytes,
			'maxMB' => round($maxBytes / (1024 * 1024), 1),
			'uploadMaxFilesize' => $uploadMaxFilesize,
			'postMaxSize' => $postMaxSize,
		]);
	}

	private function parsePhpSize(string $value): int {
		$value = trim($value);
		if (!$value || $value === '-1') {
			return PHP_INT_MAX;
		}

		$unit = strtoupper(substr($value, -1));
		$number = (int)substr($value, 0, -1);

		return match ($unit) {
			'K' => $number * 1024,
			'M' => $number * 1024 * 1024,
			'G' => $number * 1024 * 1024 * 1024,
			default => (int)$value, // If no unit, it's already in bytes
		};
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function myGroups(): DataResponse {
		$userId = $this->getUserId();
		if (!$userId) {
			return new DataResponse(['error' => 'Not logged in'], 401);
		}

		$groups = [];
		if ($this->groupManager->isInGroup($userId, 'obpersonen')) {
			$groups[] = 'obpersonen';
		}
		if ($this->groupManager->isInGroup($userId, 'mitglieder')) {
			$groups[] = 'mitglieder';
		}
		if ($this->groupManager->isInGroup($userId, 'kassier:innen')) {
			$groups[] = 'kassier:innen';
		}

		return new DataResponse([
			'userId' => $userId,
			'groups' => $groups,
		]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function exportDatabase() {
		// Nur Obpersonen dürfen exportieren
		if (!$this->isObperson()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			// Erstelle die ZIP über BackupService
			$backupFile = $this->backupService->createBackup();
			$filename = basename($backupFile);

			// Gebe Download-URL zurück
			$downloadUrl = $this->urlGenerator->linkToRoute('weinsteigfinance.api.downloadBackup', ['filename' => $filename]);

			return new DataResponse([
				'success' => true,
				'filename' => $filename,
				'downloadUrl' => $downloadUrl,
				'message' => 'Backup erstellt. Klicke auf den Link zum Herunterladen oder verwende den Download-Button.'
			]);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	private function createSqlDump(): string {
		$tables = [
			'weinsteig_members',
			'weinsteig_user_members',
			'weinsteig_vorschreibungen',
			'weinsteig_zahlungen',
			'weinsteig_zahlung_vorschreibung',
			'weinsteig_config',
			'weinsteig_mandate_approvals',
		];

		$sql = "-- Weinsteig Finance Database Backup\n";
		$sql .= "-- Created: " . (new DateTime())->format('Y-m-d H:i:s') . "\n\n";

		foreach ($tables as $table) {
			// DROP TABLE
			$sql .= "DROP TABLE IF EXISTS `oc_$table`;\n\n";

			// CREATE TABLE
			$qb = $this->db->getQueryBuilder();
			$result = $this->db->executeQuery("SHOW CREATE TABLE `oc_$table`");
			$row = $result->fetch();
			if ($row) {
				$sql .= $row['Create Table'] . ";\n\n";
			}

			// INSERT DATA
			$qb = $this->db->getQueryBuilder();
			$rows = $qb->select('*')
				->from($table)
				->executeQuery()
				->fetchAll();

			if ($rows) {
				foreach ($rows as $dataRow) {
					$values = array_map(fn($v) => $v === null ? 'NULL' : "'" . str_replace("'", "''", (string)$v) . "'", $dataRow);
					$sql .= "INSERT INTO `oc_$table` VALUES (" . implode(', ', $values) . ");\n";
				}
				$sql .= "\n";
			}
		}

		return $sql;
	}

	private function copyDirectory(string $source, string $dest): void {
		if (!is_dir($dest)) {
			mkdir($dest, 0750, true);
		}

		$files = scandir($source);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$srcPath = "$source/$file";
			$destPath = "$dest/$file";

			if (is_dir($srcPath)) {
				$this->copyDirectory($srcPath, $destPath);
			} else {
				copy($srcPath, $destPath);
			}
		}
	}

	private function addDirectoryToZip(string $dir, \ZipArchive $zip, string $zipPath = ''): void {
		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$filePath = "$dir/$file";
			$archivePath = $zipPath ? "$zipPath/$file" : $file;

			if (is_dir($filePath)) {
				$this->addDirectoryToZip($filePath, $zip, $archivePath);
			} else {
				$zip->addFile($filePath, $archivePath);
			}
		}
	}

	private function removeDirectory(string $dir): void {
		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$filePath = "$dir/$file";
			if (is_dir($filePath)) {
				$this->removeDirectory($filePath);
			} else {
				unlink($filePath);
			}
		}
		rmdir($dir);
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

		if (!$row) {
			return new DataResponse(['error' => 'Not found']);
		}

		// Prüfe, ob ein unterschriebenes Mandat-PDF existiert
		$address = $row['address'] ?? '';
		$dataDir = $this->config->getSystemValue('datadirectory');
		$folderPath = "$dataDir/generated/{$address}/sepa";
		$signedMandateExists = false;
		if (is_dir($folderPath)) {
			$files = scandir($folderPath);
			foreach ($files as $file) {
				if (preg_match('/^mandat_unterschrieben_v\d+\.(pdf|jpg|jpeg|png)$/', $file)) {
					$signedMandateExists = true;
					break;
				}
			}
		}

		$row['signed_mandate_exists'] = $signedMandateExists;
		return new DataResponse($row);
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

		// Hol Cron-Status für obpersonen und kassier:innen
		$cronStatus = null;
		$userId = $this->getUserId();
		$isKassier = $this->groupManager->isInGroup($userId, 'kassier:innen');
		if ($this->isObperson() || $isKassier) {
			$lastCronRun = $this->config->getAppValue('weinsteigfinance', 'last_cron_run');
			$lastGenerated = $this->config->getAppValue('weinsteigfinance', 'last_vorschreibungen_generated');
			$cronStatus = $this->configService->formatCronStatus($lastCronRun ?: null, $lastGenerated ?: null);
		}

		// Obpersonen und kassier:innen sehen alle Häuser
		if ($this->isObperson() || $isKassier) {
			$qb = $this->db->getQueryBuilder();
			$members = $qb->select('*')
				->from('weinsteig_members')
				->orderBy('address')
				->executeQuery()
				->fetchAll();
			$members = $this->enrichMembersWithVorschreibungDates($members, $months);
			return new DataResponse(['months' => $months, 'members' => $members, 'isObperson' => true, 'isKassier' => $isKassier, 'cronStatus' => $cronStatus]);
		}

		// Mitglieder sehen nur ihr Haus
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
			$memberId = $member['id'];
			$member['vorschreibungen'] = [];
			$address = $member['address'];

			// Lade zugeordnete Benutzer
			try {
				$qb = $this->db->getQueryBuilder();
				$userRows = $qb->select('um.user_id')
					->from('weinsteig_user_members', 'um')
					->where($qb->expr()->eq('um.member_id', $qb->createNamedParameter($memberId)))
					->executeQuery()
					->fetchAll();
				$member['assigned_users'] = array_map(fn($u) => $u['user_id'], $userRows);
			} catch (\Exception $e) {
				$member['assigned_users'] = [];
			}

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
		$bankAccount .= $this->configService->getBankAccountHtml();

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
	public function memberJournal(int $memberId = 0): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$member = null;

			// Mitglieder sehen nur ihre eigenen Daten
			if ($memberId === 0) {
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
					return new DataResponse(['error' => 'Kein Haus zugewiesen'], 404);
				}
				$memberId = (int)$member['id'];
			} elseif (!$this->isObperson()) {
				// Mitglieder können nur ihre eigenen Daten sehen
				$userId = $this->getUserId();
				$qb = $this->db->getQueryBuilder();
				$member = $qb->select('m.*')
					->from('weinsteig_members', 'm')
					->innerJoin('m', 'weinsteig_user_members', 'um', $qb->expr()->eq('m.id', 'um.member_id'))
					->where($qb->expr()->eq('um.user_id', $qb->createNamedParameter($userId)))
					->andWhere($qb->expr()->eq('m.id', $qb->createNamedParameter($memberId)))
					->setMaxResults(1)
					->executeQuery()
					->fetch();
				if (!$member) {
					return new DataResponse(['error' => 'Unauthorized'], 403);
				}
			} else {
				// Admins können jedes Haus sehen
				$qb = $this->db->getQueryBuilder();
				$member = $qb->select('m.*')
					->from('weinsteig_members', 'm')
					->where($qb->expr()->eq('m.id', $qb->createNamedParameter($memberId)))
					->setMaxResults(1)
					->executeQuery()
					->fetch();
				if (!$member) {
					return new DataResponse(['error' => 'Haus nicht gefunden'], 404);
				}
			}

			// Lade alle Vorschreibungen
			$qb = $this->db->getQueryBuilder();
			$vorschreibungen = $qb->select('*')
				->from('weinsteig_vorschreibungen')
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
				->orderBy('year', 'DESC')
				->addOrderBy('month', 'DESC')
				->executeQuery()
				->fetchAll();

			// Lade alle Zahlungen
			$qb = $this->db->getQueryBuilder();
			$zahlungen = $qb->select('*')
				->from('weinsteig_zahlungen')
				->where($qb->expr()->eq('member_id', $qb->createNamedParameter($memberId)))
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
				'member' => $member,
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

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function sepaDataCarrier(): DataResponse {
		// obpersonen und kassier:innen dürfen auf SEPA-Datenträger zugreifen
		$userId = $this->getUserId();
		$isKassier = $this->groupManager->isInGroup($userId, 'kassier:innen');
		if (!$this->isObperson() && !$isKassier) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$dataDir = $this->config->getSystemValue('datadirectory');

			// Alle Mitglieder laden
			$qb = $this->db->getQueryBuilder();
			$members = $qb->select('*')
				->from('weinsteig_members')
				->orderBy('address')
				->executeQuery()
				->fetchAll();

			$mandates = [];

			foreach ($members as $member) {
				// Prüfe ob signed mandate existiert
				$address = $member['address'];
				$folderPath = "$dataDir/generated/{$address}/sepa";
				$signedMandateExists = false;
				if (is_dir($folderPath)) {
					$files = scandir($folderPath);
					foreach ($files as $file) {
						if (preg_match('/^mandat_unterschrieben_v\d+\.(pdf|jpg|jpeg|png)$/', $file)) {
							$signedMandateExists = true;
							break;
						}
					}
				}

				// Alle Haeuser mit IBAN und nicht zurueckgezogenes Mandat
				if ($member['iban'] && !$member['mandate_withdrawn_date']) {
					// Berechne offene Betraege (exakt wie memberJournal)
					$memberId = $member['id'];

					// ALLE Zahlungen für dieses Mitglied
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

					// ALLE Vorschreibungen für dieses Mitglied
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

					$openAmount = $totalZahlungen - $openVorschreibungen;

					$mandates[] = [
						'id' => $member['id'],
						'address' => $member['address'],
						'zahlungspflichtig' => $member['zahlungspflichtig'] ?? '-',
						'iban' => $member['iban'] ?? '-',
						'mandate_granted_date' => $member['mandate_granted_date'],
						'open_amount' => round($openAmount, 2),
					];
				}
			}

			return new DataResponse([
				'success' => true,
				'count' => count($mandates),
				'mandates' => $mandates,
			]);

		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Fehler: ' . $e->getMessage()], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function sepaDataCarrierCsv(): Response {
		// obpersonen und kassier:innen dürfen CSV exportieren
		$userId = $this->getUserId();
		$isKassier = $this->groupManager->isInGroup($userId, 'kassier:innen');
		if (!$this->isObperson() && !$isKassier) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		try {
			$dataDir = $this->config->getSystemValue('datadirectory');

			// Alle Mitglieder laden
			$qb = $this->db->getQueryBuilder();
			$members = $qb->select('*')
				->from('weinsteig_members')
				->orderBy('address')
				->executeQuery()
				->fetchAll();

			$csvLines = [];
			$csvLines[] = 'Haus;Kontoinhaber;IBAN;Mandat gültig seit;Offene Beträge (€)';

			foreach ($members as $member) {
				// Prüfe ob signed mandate existiert
				$address = $member['address'];
				$folderPath = "$dataDir/generated/{$address}/sepa";
				$signedMandateExists = false;
				if (is_dir($folderPath)) {
					$files = scandir($folderPath);
					foreach ($files as $file) {
						if (preg_match('/^mandat_unterschrieben_v\d+\.(pdf|jpg|jpeg|png)$/', $file)) {
							$signedMandateExists = true;
							break;
						}
					}
				}

				// Alle Haeuser mit IBAN und nicht zurueckgezogenes Mandat
				if ($member['iban'] && !$member['mandate_withdrawn_date']) {
					// Berechne offene Betraege (exakt wie memberJournal)
					$memberId = $member['id'];

					// ALLE Zahlungen fuer dieses Mitglied
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

					// ALLE Vorschreibungen für dieses Mitglied
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

					$openAmount = $totalZahlungen - $openVorschreibungen;

					$zahlungspflichtig = $member['zahlungspflichtig'] ?? '-';
					$iban = $member['iban'] ?? '-';
					$mandateDate = $member['mandate_granted_date'] ?? '-';
					$openStr = number_format($openAmount, 2, ',', '');

					$csvLines[] = "\"$address\";\"$zahlungspflichtig\";\"$iban\";\"$mandateDate\";\"$openStr\"";
				}
			}

			$csv = implode("\r\n", $csvLines);

			// Header setzen für CSV-Download
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="sepa-datentraeger-' . date('Y-m-d') . '.csv"');
			echo $csv;
			exit;

		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Fehler: ' . $e->getMessage()], 400);
		}
	}
}
