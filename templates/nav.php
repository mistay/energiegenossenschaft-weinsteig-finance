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

// Get app version
$appVersion = '1.3.4'; // fallback
try {
	$appManager = Server::get(\OCP\App\IAppManager::class);
	$appVersion = $appManager->getAppVersion('weinsteigfinance');
} catch (\Throwable $e) {
	// Use fallback
}
?>

<nav style="background: white; border-bottom: 1px solid #ecf0f1; margin: -16px -16px 24px -16px; padding: 0; sticky top: 0; z-index: 100;">
	<div style="display: flex; gap: 0; max-width: 1400px; margin: 0 auto; flex-wrap: wrap; align-items: stretch;">
		<a href="/index.php/apps/weinsteigfinance/"
			style="padding: 14px 16px; color: #2c3e50; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 600; border-bottom: 3px solid transparent; transition: all 0.2s;">
			<span style="font-size: 16px;">■</span>
			<span style="display: none; @media (min-width: 768px) { display: inline; }">Finance</span>
		</a>

		<div style="margin-left: auto; display: flex; align-items: center; gap: 8px; padding: 14px 16px;">
			<div id="user-groups-info" style="font-size: 12px; padding: 6px 10px; background: #e3f2fd; border-radius: 4px; color: #0082c9;">
				Lädt...
			</div>
		</div>

		<a href="/index.php/apps/weinsteigfinance/profil"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'profil' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			👤 Profil
		</a>

		<a href="/index.php/apps/weinsteigfinance/bankverbindung"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'bankverbindung' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			💳 SEPA Lastschrift
		</a>

		<a href="/index.php/apps/weinsteigfinance/vorschreibungen"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'vorschreibungen' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			📋 Vorschreibungen
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
			📥 Import Kontoauszüge
		</a>

		<a href="/index.php/apps/weinsteigfinance/admin"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'admin' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			⚙️ Konfiguration
		</a>

		<a href="/index.php/apps/weinsteigfinance/admin-haeuser-personen"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'admin-members' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			👥 Admin: Häuser & Personen
		</a>

		<a href="/index.php/apps/weinsteigfinance/sepa-datentraeger"
			style="padding: 14px 16px; color: #555; text-decoration: none; border-bottom: 3px solid <?= $currentPage === 'sepa-datentraeger' ? '#0082c9' : 'transparent' ?>; transition: all 0.2s;">
			🏦 Admin: SEPA Core
		</a>
		<?php endif; ?>
	</div>
</nav>

<!-- Version Display - Bottom Right -->
<div id="app-version" style="position: fixed; bottom: 12px; right: 12px; font-size: 12px; color: #999; font-family: monospace; background: rgba(255,255,255,0); padding: 4px 8px; border-radius: 3px; z-index: 100; pointer-events: none;">
	v<?php echo htmlspecialchars($appVersion); ?>
</div>
