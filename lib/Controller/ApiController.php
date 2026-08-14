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

class ApiController extends Controller {
	public function __construct(
		IRequest $request,
		private IDBConnection $db,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private string $userId,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function members(): DataResponse {
		if (!$this->groupManager->isInGroup($this->userId, 'obpersonen')) {
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
		if (!$this->groupManager->isInGroup($this->userId, 'obpersonen')) {
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
	public function assignUser(int $memberId, string $userId): DataResponse {
		if (!$this->groupManager->isInGroup($this->userId, 'obpersonen')) {
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
	public function unassignUser(int $memberId, string $userId): DataResponse {
		if (!$this->groupManager->isInGroup($this->userId, 'obpersonen')) {
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
