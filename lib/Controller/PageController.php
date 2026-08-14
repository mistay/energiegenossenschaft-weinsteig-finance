<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Controller;

use OCA\WeinsteigFinance\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
	public function __construct(
		IRequest $request,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
		Util::addStyle(Application::APP_ID, 'main');

		return new TemplateResponse(Application::APP_ID, 'index');
	}

	#[NoAdminRequired]
	public function admin(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || !$this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse(\OCP\Util::linkTo('', 'index.php'));
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'admin');

		return new TemplateResponse(Application::APP_ID, 'admin');
	}
}
