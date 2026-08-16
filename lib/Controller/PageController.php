<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Controller;

use OCA\WeinsteigFinance\AppInfo\Application;
use OCA\WeinsteigFinance\Service\ConfigService;
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
		private ConfigService $configService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();

		// Mitglieder direkt zur Bankverbindung
		if ($user && $this->groupManager->isInGroup($user->getUID(), 'mitglieder') && !$this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse('/index.php/apps/weinsteigfinance/bankverbindung');
		}

		// Admins direkt zur Admin-Seite
		if ($user && $this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse('/index.php/apps/weinsteigfinance/admin');
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
		Util::addScript(Application::APP_ID, 'admin-config');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'admin');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function adminMembers(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || !$this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'admin-members');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'admin-members');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function bankverbindung(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'bankverbindung');
		Util::addScript(Application::APP_ID, 'user-groups');

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
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'vorschreibungen');
		Util::addScript(Application::APP_ID, 'user-groups');

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
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'zahlungen');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'zahlungen');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function zahlungenUebersicht(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'zahlungen-uebersicht');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'zahlungen-uebersicht');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function journal(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'journal');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'journal', [
			'creditorIban' => $this->configService->getCreditorIban(),
			'creditorBic' => $this->configService->getCreditorBic(),
		]);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function profil(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || (!$this->groupManager->isInGroup($user->getUID(), 'obpersonen') && !$this->groupManager->isInGroup($user->getUID(), 'mitglieder'))) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'profil');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'profil');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function sepaDataCarrier(): TemplateResponse|RedirectResponse {
		$user = $this->userSession->getUser();
		if (!$user || !$this->groupManager->isInGroup($user->getUID(), 'obpersonen')) {
			return new RedirectResponse('/index.php');
		}

		Util::addStyle(Application::APP_ID, 'main');
		Util::addScript(Application::APP_ID, 'table-wrapper');
		Util::addScript(Application::APP_ID, 'sepa-datentraeger');
		Util::addScript(Application::APP_ID, 'user-groups');

		return new TemplateResponse(Application::APP_ID, 'sepa-datentraeger');
	}
}
