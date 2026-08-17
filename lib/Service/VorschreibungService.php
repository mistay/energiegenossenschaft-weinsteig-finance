<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

use OCP\IDBConnection;
use OCP\IConfig;
use Mpdf\Mpdf;
use DateTime;

class VorschreibungService {
	public function __construct(
		private IDBConnection $db,
		private IConfig $config,
		private ConfigService $configService,
	) {}

	/**
	 * Generiert alle Vorschreibungs-PDFs für einen bestimmten Monat und speichert sie in der DB
	 */
	public function generateAllForMonth(int $year, int $month): array {
		$qb = $this->db->getQueryBuilder();
		$members = $qb->select('*')
			->from('weinsteig_members')
			->orderBy('address')
			->executeQuery()
			->fetchAll();

		$generated = [];
		$dataDir = $this->config->getSystemValue('datadirectory');
		$now = new DateTime();

		foreach ($members as $member) {
			try {
				$memberId = $member['id'];
				$amount = 60.00; // Akontozahlung

				// Speichere in DB (nur wenn noch nicht vorhanden)
				$checkQb = $this->db->getQueryBuilder();
				$existing = $checkQb->select('id')
					->from('weinsteig_vorschreibungen')
					->where($checkQb->expr()->eq('member_id', $checkQb->createNamedParameter($memberId)))
					->andWhere($checkQb->expr()->eq('year', $checkQb->createNamedParameter($year)))
					->andWhere($checkQb->expr()->eq('month', $checkQb->createNamedParameter($month)))
					->executeQuery()
					->fetch();

				if (!$existing) {
					$insertQb = $this->db->getQueryBuilder();
					$insertQb->insert('weinsteig_vorschreibungen')
						->values([
							'member_id' => $insertQb->createNamedParameter($memberId),
							'year' => $insertQb->createNamedParameter($year),
							'month' => $insertQb->createNamedParameter($month),
							'amount' => $insertQb->createNamedParameter($amount),
							'status' => $insertQb->createNamedParameter('open'),
							'created_at' => $insertQb->createNamedParameter($now->format('Y-m-d H:i:s')),
						])
						->executeStatement();
				}

				// PDF generieren und speichern
				$pdf = $this->generateVorschreibungPdf($member, $year, $month);
				$address = $member['address'];
				$folderPath = "$dataDir/generated/{$address}/vorschreibungen";
				$filename = sprintf('%04d-%02d-vorschreibung.pdf', $year, $month);
				$filePath = "$folderPath/$filename";

				// Ordner erstellen
				@mkdir($folderPath, 0750, true);

				// PDF speichern
				file_put_contents($filePath, $pdf);
				$generated[] = [
					'member_id' => $memberId,
					'address' => $address,
					'filename' => $filename,
					'path' => $filePath,
					'amount' => $amount,
				];
			} catch (\Exception $e) {
				// Fehler loggen, aber weitermachen
				\OCP\Server::get(\OCP\Log\ILogFactory::class)->getLogFile()?->log(0, 'Vorschreibung generation error for ' . ($member['address'] ?? 'unknown') . ': ' . $e->getMessage());
			}
		}

		return $generated;
	}

	/**
	 * Generiert eine einzelne Vorschreibungs-PDF
	 */
	private function generateVorschreibungPdf(array $member, int $year, int $month): string {
		$address = $member['address'];
		$iban = $member['iban'] ?? '';
		$mandateGrantedDate = $member['mandate_granted_date'] ?? null;

		// Deutsche Monatsnamen
		$months = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
		$monthName = $months[$month] . ' ' . $year;

		// Format mandate granted date
		$mandateDateText = '';
		if ($mandateGrantedDate) {
			try {
				$mandateDate = new DateTime($mandateGrantedDate);
				$mandateDateText = 'Mandatserteilung: ' . $mandateDate->format('d.m.Y');
			} catch (\Exception) {
				$mandateDateText = 'Mandatserteilung: ' . $mandateGrantedDate;
			}
		}

		// Belastungskonto: Immer Genossenschaft anzeigen, plus optional Mitglied-IBAN
		$bankAccount = '<strong>Energiegenossenschaft Weinsteig</strong><br>';
		$bankAccount .= $this->configService->getBankAccountHtml();

		if ($iban) {
			$bankAccount .= '<br><strong>Ihr hinterlegtes Konto:</strong><br>';
			$bankAccount .= $address . '<br>';
			$bankAccount .= 'IBAN: ' . $iban;
		}

		// Ausgestellt: 1. des Monats der Vorschreibung
		$issuedDate = (new DateTime("$year-$month-01"))->format('d.m.Y');

		$html = <<<HTML
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; margin: 20px; }
h2 { font-size: 14pt; margin-bottom: 10px; }
.section { margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
td { padding: 5px; border: 1px solid #ddd; }
.label { font-weight: bold; }
.amount { font-size: 14pt; font-weight: bold; color: #d9534f; }
.note { font-size: 9pt; color: #666; margin-top: 20px; line-height: 1.3; }
</style>
</head>
<body>

<h2>Vorschreibung</h2>

<div class="section">
<strong>Energiegenossenschaft Weinsteig</strong><br>
Weinsteig 19a<br>
5082 Glanegg<br>
Österreich
</div>

<div class="section">
<strong>Rechnungsempfänger</strong><br>
Energiegenossenschaft Weinsteig
</div>

<div class="section">
<strong>Liegenschaft</strong><br>
{$address}
</div>

<div class="section">
<strong>Rechnungszeitraum</strong><br>
{$monthName}
</div>

<table>
<tr><td class="label">Akontozahlung</td><td style="text-align: right; width: 150px;">€ 60,00</td></tr>
<tr style="background: #f5f5f5;"><td class="label" style="border-top: 2px solid black; padding-top: 10px;"><strong>Gesamtbetrag fällig</strong></td><td style="border-top: 2px solid black; padding-top: 10px; text-align: right;"><span class="amount">€ 60,00</span></td></tr>
</table>

<div class="section" style="margin-top: 20px;">
<strong>Ihr hinterlegtes Konto</strong><br><br>
{$bankAccount}<br><br>
<strong>Der fällige Betrag wird von Ihrem hinterlegten Konto per SEPA-Lastschrift eingezogen.</strong>
</div>

<div class="note">
<strong>Mandatsinformation:</strong><br>
{$mandateDateText}<br><br>
<strong>Widerrufsrecht:</strong> Das SEPA-Lastschrift-Mandat kann jederzeit online über das Kundencenter der Energiegenossenschaft Weinsteig widerrufen werden.
</div>

<div style="margin-top: 30px; font-size: 9pt; color: #666;">
Ausgestellt: {$issuedDate}
</div>

</body>
</html>
HTML;

		$mpdf = new Mpdf(['default_font_size' => 11, 'default_font' => 'Arial']);
		$mpdf->WriteHTML($html);
		return $mpdf->Output('', 'S');
	}
}
