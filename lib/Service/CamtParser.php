<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

class CamtParser {
	public function parse(string $xmlContent): array {
		try {
			$xml = new \SimpleXMLElement($xmlContent);
		} catch (\Exception $e) {
			throw new \Exception('Ungültige XML: ' . $e->getMessage());
		}

		$transactions = [];

		// Register namespace for XPath
		$ns = $xml->getDocNamespaces();
		$defaultNs = reset($ns);
		if ($defaultNs) {
			$xml->registerXPathNamespace('camt', $defaultNs);
		}

		// Find all statement entries
		$entries = $xml->xpath('//camt:Ntry') ?: [];
		if (empty($entries)) {
			$entries = $xml->xpath('//Ntry') ?: [];
		}

		// If still no entries, provide helpful debug info
		if (empty($entries)) {
			// Check if this is a valid CAMT.053 document at all
			$stmts = $xml->xpath('//camt:Stmt') ?: [];
			if (empty($stmts)) {
				$stmts = $xml->xpath('//Stmt') ?: [];
			}

			if (!empty($stmts)) {
				// Valid CAMT.053 but no transactions (empty statement)
				return [];
			}

			// Not a valid CAMT.053 document
			throw new \Exception('Keine Transaktionen gefunden. Ist das eine gültige CAMT.053 XML-Datei? (Format: ISO 20022 Bankkontoauszug)');
		}

		foreach ($entries as $entry) {
			try {
				if ($tx = $this->parseEntry($entry)) {
					$transactions[] = $tx;
				}
			} catch (\Exception) {
				continue;
			}
		}

		return $transactions;
	}

	private function parseEntry(\SimpleXMLElement $entry): ?array {
		$buchungsdatum = $this->text($entry->BookgDt->Dt ?? $entry->BookgDt);
		if (!$buchungsdatum) {
			return null;
		}
		$buchungsdatum = $this->parseDate($buchungsdatum);
		if (!$buchungsdatum) {
			return null;
		}

		$valutadatum = $this->parseDate($this->text($entry->ValDt->Dt ?? $entry->ValDt) ?? $buchungsdatum) ?? $buchungsdatum;

		$betrag = (float)$this->text($entry->Amt);
		if (!$betrag) {
			return null;
		}
		$betrag = abs($betrag);

		$waehrung = (string)($entry->Amt['Ccy'] ?? 'EUR');

		// Extract counterparty and purpose from NtryDtls
		$partnername = '';
		$verwendungszweck = '';
		$iban = null;

		if (isset($entry->NtryDtls->TxDtls)) {
			$txDtls = $entry->NtryDtls->TxDtls;

			// Get counterparty
			if (isset($txDtls->RltdPties)) {
				// Debtor (sender)
				if (isset($txDtls->RltdPties->Dbtr)) {
					$partnername = $this->text($txDtls->RltdPties->Dbtr->Nm) ?? '';
				}
				// Creditor (receiver) - use if no debtor
				if (!$partnername && isset($txDtls->RltdPties->Cdtr)) {
					$partnername = $this->text($txDtls->RltdPties->Cdtr->Nm) ?? '';
				}
				// Get IBAN from debtor account
				if (isset($txDtls->RltdPties->DbtrAcct->Id->IBAN)) {
					$iban = $this->text($txDtls->RltdPties->DbtrAcct->Id->IBAN);
				}
				// Fallback to creditor account
				if (!$iban && isset($txDtls->RltdPties->CdtrAcct->Id->IBAN)) {
					$iban = $this->text($txDtls->RltdPties->CdtrAcct->Id->IBAN);
				}
			}

			// Get purpose
			if (isset($txDtls->RmtInf->Ustrd)) {
				$verwendungszweck = $this->text($txDtls->RmtInf->Ustrd) ?? '';
			} elseif (isset($txDtls->RmtInf->Strd->CdtrRefInf->Ref)) {
				$verwendungszweck = $this->text($txDtls->RmtInf->Strd->CdtrRefInf->Ref) ?? '';
			}
		}

		if (!$partnername) {
			$partnername = 'Unknown';
		}

		return [
			'buchungsdatum' => $buchungsdatum,
			'valutadatum' => $valutadatum,
			'partnername' => trim($partnername),
			'verwendungszweck' => trim($verwendungszweck),
			'betrag' => $betrag,
			'waehrung' => $waehrung,
			'iban' => $iban ? strtoupper(str_replace(' ', '', $iban)) : null,
			'bic' => null,
		];
	}

	private function text($element): ?string {
		if ($element === null) {
			return null;
		}
		$text = (string)$element;
		return !empty($text) ? $text : null;
	}

	private function parseDate(string $dateStr): ?string {
		$dateStr = trim($dateStr);

		if (preg_match('/^\d{4}-\d{2}-\d{2}/', $dateStr)) {
			return substr($dateStr, 0, 10);
		}

		try {
			$dt = new \DateTime($dateStr);
			return $dt->format('Y-m-d');
		} catch (\Exception) {
			return null;
		}
	}
}
