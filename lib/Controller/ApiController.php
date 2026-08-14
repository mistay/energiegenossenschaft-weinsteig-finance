<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Controller;

use OCA\WeinsteigFinance\AppInfo\Application;
use OCA\WeinsteigFinance\Util\IbanValidator;
use OCA\WeinsteigFinance\Service\MandateService;
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
use DateTime;

class ApiController extends Controller {
	public function __construct(
		IRequest $request,
		private IDBConnection $db,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private MandateService $mandateService,
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
			->set('iban', $qb->createNamedParameter($iban))
			->set('mandate_withdrawn_date', $qb->createNamedParameter(null))
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

			// Im Nextcloud-Dateisystem speichern
			$folder = "energiegenossenschaft-weinsteig/generated/{$address}/sepa";
			$filePath = "$folder/mandat_unterschrieben.pdf";

			// Ordner erstellen
			$this->ensureNextcloudFolder($folder);

			// Datei speichern
			$rootFolder = \OCP\Server::get(\OCP\Files\IRootFolder::class);
			try {
				$ncFile = $rootFolder->get($filePath);
				$ncFile->putContent($pdfContent);
			} catch (\Exception) {
				$rootFolder->newFile($filePath)->putContent($pdfContent);
			}

			return new DataResponse(['success' => true]);
		} catch (\Throwable $e) {
			\OCP\Server::get(\OCP\Log\ILogFactory::class)->getLogFile()?->log(0, 'Upload error: ' . $e->getMessage() . ' ' . $e->getTraceAsString());
			return new DataResponse(['error' => $e->getMessage() ?: 'Upload failed'], 400);
		}
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getSignedMandate(int $id): DataResponse {
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
			$filePath = "energiegenossenschaft-weinsteig/generated/{$address}/sepa/mandat_unterschrieben.pdf";

			try {
				\OCP\Server::get(\OCP\Files\IRootFolder::class)->get($filePath);
				return new DataResponse(['exists' => true]);
			} catch (\Exception) {
				return new DataResponse(['exists' => false]);
			}
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], 400);
		}
	}

	private function ensureNextcloudFolder(string $path): void {
		try {
			\OCP\Server::get(\OCP\Files\IRootFolder::class)->get($path);
		} catch (\Exception) {
			$parts = explode('/', $path);
			$current = '';
			foreach ($parts as $part) {
				if (!$part) continue;
				$current .= ($current ? '/' : '') . $part;
				try {
					\OCP\Server::get(\OCP\Files\IRootFolder::class)->get($current);
				} catch (\Exception) {
					try {
						\OCP\Server::get(\OCP\Files\IRootFolder::class)->newFolder($current);
					} catch (\Exception) {
						// Folder exists
					}
				}
			}
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
}
