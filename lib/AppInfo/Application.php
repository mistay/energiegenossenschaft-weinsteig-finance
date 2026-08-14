<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'weinsteigfinance';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService('UserId', fn() => \OCP\Server::get(\OCP\IUserSession::class)->getUser()?->getUID() ?? '');
	}

	public function boot(IBootContext $context): void {
	}
}
