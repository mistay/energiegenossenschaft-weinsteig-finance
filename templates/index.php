<?php

declare(strict_types=1);

/** @var \OCP\IL10N $l */
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Server;

$groupManager = Server::get(IGroupManager::class);
$userSession = Server::get(IUserSession::class);
$user = $userSession->getUser();
$isAdmin = $user && $groupManager->isInGroup($user->getUID(), 'obpersonen');
$isMember = $user && $groupManager->isInGroup($user->getUID(), 'mitglieder');
?>
<div id="weinsteigfinance" class="app-weinsteigfinance">
	<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
		<h1 style="margin: 0 0 10px 0; font-size: 32px;">🏦 Weinsteig Finance</h1>
		<p style="margin: 0; opacity: 0.9; font-size: 16px;">Verwaltung von Vorschreibungen, SEPA-Mandaten und Zahlungen</p>
	</div>

	<!-- Quick Links -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
		<?php if ($isMember): ?>
		<a href="/index.php/apps/weinsteigfinance/journal" style="display: block; padding: 25px; background: #f0f8ff; border: 2px solid #0082c9; border-radius: 8px; text-decoration: none; transition: all 0.3s;">
			<div style="font-size: 28px; margin-bottom: 10px;">📊</div>
			<h3 style="margin: 0 0 8px 0; color: #0082c9;">Kontojurnal</h3>
			<p style="margin: 0; color: #666; font-size: 14px;">Saldo, Vorschreibungen & Zahlungen</p>
		</a>

		<a href="/index.php/apps/weinsteigfinance/bankverbindung" style="display: block; padding: 25px; background: #f0fff4; border: 2px solid #28a745; border-radius: 8px; text-decoration: none; transition: all 0.3s;">
			<div style="font-size: 28px; margin-bottom: 10px;">💳</div>
			<h3 style="margin: 0 0 8px 0; color: #28a745;">Bankverbindung</h3>
			<p style="margin: 0; color: #666; font-size: 14px;">IBAN & SEPA-Mandat verwalten</p>
		</a>

		<a href="/index.php/apps/weinsteigfinance/zahlungen-uebersicht" style="display: block; padding: 25px; background: #fffbf0; border: 2px solid #ff9800; border-radius: 8px; text-decoration: none; transition: all 0.3s;">
			<div style="font-size: 28px; margin-bottom: 10px;">💰</div>
			<h3 style="margin: 0 0 8px 0; color: #ff9800;">Meine Zahlungen</h3>
			<p style="margin: 0; color: #666; font-size: 14px;">Zahlungsverlauf einsehen</p>
		</a>
		<?php endif; ?>

		<?php if ($isAdmin): ?>
		<a href="/index.php/apps/weinsteigfinance/admin" style="display: block; padding: 25px; background: #f5f5f5; border: 2px solid #333; border-radius: 8px; text-decoration: none; transition: all 0.3s;">
			<div style="font-size: 28px; margin-bottom: 10px;">⚙️</div>
			<h3 style="margin: 0 0 8px 0; color: #333;">Admin: Mitglieder</h3>
			<p style="margin: 0; color: #666; font-size: 14px;">Häuser & Zuordnungen verwalten</p>
		</a>

		<a href="/index.php/apps/weinsteigfinance/vorschreibungen" style="display: block; padding: 25px; background: #f5f5f5; border: 2px solid #333; border-radius: 8px; text-decoration: none; transition: all 0.3s;">
			<div style="font-size: 28px; margin-bottom: 10px;">📋</div>
			<h3 style="margin: 0 0 8px 0; color: #333;">Vorschreibungen</h3>
			<p style="margin: 0; color: #666; font-size: 14px;">Rechnungen generieren & verwalten</p>
		</a>

		<a href="/index.php/apps/weinsteigfinance/zahlungen" style="display: block; padding: 25px; background: #f5f5f5; border: 2px solid #333; border-radius: 8px; text-decoration: none; transition: all 0.3s;">
			<div style="font-size: 28px; margin-bottom: 10px;">📥</div>
			<h3 style="margin: 0 0 8px 0; color: #333;">Zahlungs-Management</h3>
			<p style="margin: 0; color: #666; font-size: 14px;">CSV-Import & Abgleich</p>
		</a>
		<?php endif; ?>
	</div>

	<!-- Info Box -->
	<div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #0082c9;">
		<h3 style="margin-top: 0;">ℹ️ Über diese App</h3>
		<p style="color: #666; margin: 10px 0;">
			WeinsteigFinance verwaltet die Finanzen der Energiegenossenschaft Weinsteig:
		</p>
		<ul style="color: #666; margin: 10px 0; padding-left: 20px;">
			<li><strong>Vorschreibungen:</strong> Monatliche Akontozahlungen für gemeinsame Kosten</li>
			<li><strong>SEPA-Mandate:</strong> Digitale Mandatsverwaltung für Lastschriften</li>
			<li><strong>Zahlungen:</strong> Abgleich und Zuordnung von Bankzahlungen</li>
			<li><strong>Journal:</strong> Vollständiger Kontosaldo und Buchhaltung pro Haus</li>
		</ul>
	</div>

	<style>
		a[href*="bankverbindung"]:hover,
		a[href*="journal"]:hover,
		a[href*="zahlungen-uebersicht"]:hover,
		a[href*="admin"]:hover,
		a[href*="vorschreibungen"]:hover,
		a[href*="zahlungen"]:not([href*="uebersicht"]):hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		}
	</style>
</div>
