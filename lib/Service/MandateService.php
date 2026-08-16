<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

use OCP\IDBConnection;
use OCP\Files\IRootFolder;
use Mpdf\Mpdf;
use DateTime;

class MandateService {
	public function __construct(
		private IDBConnection $db,
		private IRootFolder $rootFolder,
		private ConfigService $configService,
	) {}

	public function generateMandatePdf(int $memberId): string {
		// Daten aus DB laden
		$qb = $this->db->getQueryBuilder();
		$member = $qb->select('*')
			->from('weinsteig_members')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($memberId)))
			->executeQuery()
			->fetch();

		if (!$member) {
			throw new \Exception('Member not found');
		}

		$address = $member['address'];
		$zahlungspflichtig = $member['zahlungspflichtig'] ?? 'Zahlungspflichtiger';
		$iban = $member['iban'] ?? '';
		$bic = $this->getBicFromIban($iban);
		$today = (new DateTime())->format('d.m.Y');

		// Creditor ID kommt aus der Konfiguration (Tabelle weinsteig_config)
		$creditorId = $this->configService->getCreditorId();
		if ($creditorId === '') {
			throw new \Exception('Creditor ID ist nicht konfiguriert. Bitte in der Verwaltung unter "Einstellungen" eintragen.');
		}

		// HTML für PDF
		$html = $this->getHtmlTemplate($address, $zahlungspflichtig, $iban, $bic, $today, $creditorId);

		// PDF generieren
		$mpdf = new Mpdf(['default_font_size' => 11, 'default_font' => 'Arial']);
		$mpdf->WriteHTML($html);

		// PDF als String zurückgeben
		return $mpdf->Output('', 'S');
	}

	private function getHtmlTemplate(string $address, string $zahlungspflichtig, string $iban, string $bic, string $date, string $creditorId): string {
		return <<<HTML
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.3; margin: 20px; }
h2 { font-size: 14pt; margin-bottom: 5px; }
.section { margin-bottom: 20px; border-bottom: 1px solid #999; padding-bottom: 10px; }
.field { margin-bottom: 8px; }
.label { font-weight: bold; }
.line { border-bottom: 1px solid black; width: 100%; display: inline-block; height: 1px; }
table { width: 100%; border-collapse: collapse; }
td { padding: 3px; }
</style>
</head>
<body>

<h2>SEPA-Lastschrift-Mandat (Ermächtigung)</h2>

<div class="section">
<strong>Mandatsreferenz</strong><br>
{$address}
</div>

<div class="section">
<strong>Zahlungsempfänger</strong><br>
Energiegenossenschaft Weinsteig<br>
Weinsteig 19a<br>
5082 Glanegg<br><br>
Creditor ID: {$creditorId}
</div>

<div class="section">
Ich ermächtige/ Wir ermächtigen die Energiegenossenschaft Weinsteig Zahlungen von meinem/ unserem Konto mittels SEPA-Lastschrift einzuziehen. Zugleich weise ich mein/ unser Kreditinstitut an, die von der Energiegenossenschaft Weinsteig auf mein/ unser Konto gezogenen SEPA–Lastschriften einzulösen.
<br><br>
Ich kann/ Wir können innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des belasteten Betrages verlangen. Es gelten dabei die mit meinem/ unserem Kreditinstitut vereinbarten Bedingungen.
</div>

<div class="section">
<strong>Zahlungspflichtiger</strong><br>
<table>
<tr><td><strong>Name</strong></td><td>{$zahlungspflichtig}</td></tr>
<tr><td><strong>Anschrift</strong></td><td>{$address}</td></tr>
<tr><td><strong>IBAN</strong></td><td>{$iban}</td></tr>
<tr><td><strong>BIC</strong></td><td>{$bic}</td></tr>
</table>
</div>

<div class="section">
<strong>Zahlungsart</strong><br>
x Wiederkehrender Einzug &nbsp;&nbsp;&nbsp;&nbsp; o Einmaleinzug
</div>

<div class="section">
<strong>Ort, Datum</strong><br>
Fürstenbrunn, {$date}<br><br>
<strong>Unterschrift</strong><br>
……………………………………………………………………….
</div>

</body>
</html>
HTML;
	}

	private function getBicFromIban(string $iban): string {
		// Vereinfacht: BIC aus IBAN extrahieren (üblicherweise nicht möglich)
		// Für echte Lösung: Lookup-Tabelle nutzen
		return '';
	}

	private function ensureFolder(string $path): void {
		try {
			$this->rootFolder->get($path);
		} catch (\Exception) {
			$parts = explode('/', $path);
			$current = '';
			foreach ($parts as $part) {
				$current .= ($current ? '/' : '') . $part;
				try {
					$this->rootFolder->get($current);
				} catch (\Exception) {
					$this->rootFolder->newFolder($current);
				}
			}
		}
	}
}
