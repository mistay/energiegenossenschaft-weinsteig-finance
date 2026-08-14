<?php
declare(strict_types=1);
/** @var string $currentPage */

use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Server;

$groupManager = Server::get(IGroupManager::class);
$userSession = Server::get(IUserSession::class);
$user = $userSession->getUser();
$isAdmin = $user && $groupManager->isInGroup($user->getUID(), 'obpersonen');
?>

<nav style="background: white; border-bottom: 1px solid #ecf0f1; margin: -16px -16px 24px -16px; padding: 0; sticky top: 0; z-index: 100;">
	<div style="display: flex; gap: 0; max-width: 1400px; margin: 0 auto; flex-wrap: wrap;">
		<a href="/index.php/apps/weinsteigfinance/"
			style="padding: 14px 16px; color: #2c3e50; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 600; border-bottom: 3px solid transparent; transition: all 0.2s;">
			<span style="font-size: 16px;">■</span>
			<span style="display: none; @media (min-width: 768px) { display: inline; }">Finance</span>
		</a>

		<a href="/index.php/apps/weinsteigfinance/bankverbindung"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'bankverbindung' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			💳 SEPA Lastschrift
		</a>

		<a href="/index.php/apps/weinsteigfinance/vorschreibungen"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'vorschreibungen' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			📋 Rech.
		</a>

		<a href="/index.php/apps/weinsteigfinance/zahlungen-uebersicht"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'zahlungen-uebersicht' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			💰 Zahlungen
		</a>

		<a href="/index.php/apps/weinsteigfinance/journal"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'journal' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			📊 Journal
		</a>

		<?php if ($isAdmin): ?>
		<a href="/index.php/apps/weinsteigfinance/zahlungen"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'zahlungen' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			📥 Admin: Import
		</a>

		<a href="/index.php/apps/weinsteigfinance/admin"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'admin' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			⚙️ Admin
		</a>
		<?php endif; ?>
	</div>
</nav>
