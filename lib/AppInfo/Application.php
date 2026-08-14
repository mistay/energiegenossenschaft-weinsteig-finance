<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\AppInfo;

// Load vendor autoloader for mPDF
@require_once __DIR__ . '/../../vendor/autoload.php';

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCA\WeinsteigFinance\BackgroundJob\GenerateVorschreibungenJob;

class Application extends App implements IBootstrap {
	public const APP_ID = 'weinsteigfinance';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService('UserId', fn() => \OCP\Server::get(\OCP\IUserSession::class)->getUser()?->getUID() ?? '');
		// BackgroundJob wird via info.xml registriert
	}

	public function boot(IBootContext $context): void {
	}
}
