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
	public function index(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();

		// Mitglieder direkt zur IBAN-Verwaltung
		if ($user && $this->groupManager->isInGroup($user->getUID(), 'mitglieder') && !$this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse('/index.php/apps/weinsteigfinance/bankverbindung');
		}

		Util::addStyle(Application::APP_ID, 'main');

		return new TemplateResponse(Application::APP_ID, 'index');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function admin(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || !$this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'admin');

		return new TemplateResponse(Application::APP_ID, 'admin');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function bankverbindung(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'bankverbindung');

		return new TemplateResponse(Application::APP_ID, 'bankverbindung');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function vorschreibungen(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'vorschreibungen');

		return new TemplateResponse(Application::APP_ID, 'vorschreibungen');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungen(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'zahlungen');

		return new TemplateResponse(Application::APP_ID, 'zahlungen');
	}
}
