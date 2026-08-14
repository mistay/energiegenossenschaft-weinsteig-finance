<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Controller;

use OCA\WeinsteigFinance\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class ApiController extends Controller {
	public function __construct(
		IRequest $request,
		private IDBConnection $db,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
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

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function members(): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$rows = $qb->select('id', 'address')
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
	public function updateMember(int $id, ?string $zahlungspflichtig = null, ?string $iban = null): DataResponse {
		if (!$this->canEdit()) {
			return new DataResponse(['error' => 'Unauthorized'], 403);
		}

		$qb = $this->db->getQueryBuilder();
		$qb->update('weinsteig_members')
			->set('zahlungspflichtig', $qb->createNamedParameter($zahlungspflichtig))
			->set('iban', $qb->createNamedParameter($iban))
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
}
