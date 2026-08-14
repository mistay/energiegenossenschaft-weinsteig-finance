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

<nav style="background: #0082c9; padding: 0; margin: -20px -20px 20px -20px; border-bottom: 3px solid #003d7a;">
	<div style="display: flex; gap: 0; max-width: 1200px; margin: 0 auto;">
		<a href="/index.php/apps/weinsteigfinance/"
			style="padding: 12px 16px; color: white; text-decoration: none; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s;">
			<span style="font-size: 18px;">🏠</span>
			<strong>Weinsteig Finance</strong>
		</a>

		<a href="/index.php/apps/weinsteigfinance/bankverbindung"
			style="padding: 12px 16px; color: white; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'bankverbindung' ? '#ffb81c' : 'transparent' ?>; transition: all 0.2s; hover: background #006ba3;">
			💳 Bankverbindung
		</a>

		<a href="/index.php/apps/weinsteigfinance/vorschreibungen"
			style="padding: 12px 16px; color: white; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'vorschreibungen' ? '#ffb81c' : 'transparent' ?>; transition: all 0.2s;">
			📋 Vorschreibungen
		</a>

		<a href="/index.php/apps/weinsteigfinance/zahlungen-uebersicht"
			style="padding: 12px 16px; color: white; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'zahlungen-uebersicht' ? '#ffb81c' : 'transparent' ?>; transition: all 0.2s;">
			💰 Meine Zahlungen
		</a>

		<?php if ($isAdmin): ?>
		<a href="/index.php/apps/weinsteigfinance/zahlungen"
			style="padding: 12px 16px; color: white; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'zahlungen' ? '#ffb81c' : 'transparent' ?>; transition: all 0.2s;">
			📥 Admin: Zahlungen
		</a>

		<a href="/index.php/apps/weinsteigfinance/admin"
			style="padding: 12px 16px; color: white; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'admin' ? '#ffb81c' : 'transparent' ?>; transition: all 0.2s;">
			⚙️ Admin: Mitglieder
		</a>
		<?php endif; ?>
	</div>
</nav>
