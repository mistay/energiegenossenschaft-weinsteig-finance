<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Service;

class CamtParser {
	/**
	 * Parse CAMT.053 XML and extract transactions
	 */
	public function parse(string $xmlContent): array {
		$xml = new \SimpleXMLElement($xmlContent);
		$transactions = [];

		// Register all namespaces
		$ns = $this->getNamespaces($xml);

		// Find all statement entries (Ntry elements)
		// CAMT.053 structure typically: Document/BkToCstmrStmt/Stmt/Ntry
		$entries = $this->xpath($xml, '//ns:Ntry', $ns);
		if (empty($entries)) {
			// Try without namespace
			$entries = $xml->xpath('//Ntry');
		}

		foreach ($entries as $entry) {
			$tx = $this->parseEntry($entry, $ns);
			if ($tx) {
				$transactions[] = $tx;
			}
		}

		return $transactions;
	}

	private function parseEntry(\SimpleXMLElement $entry, array $ns): ?array {
		try {
			// Booking date (Buchungsdatum)
			$bookingDate = $this->getElementText($entry, 'BookgDt/Dt', $ns)
				?? $this->getElementText($entry, 'BookgDt', $ns);
			if (!$bookingDate) {
				return null;
			}
			$buchungsdatum = $this->parseDate($bookingDate);
			if (!$buchungsdatum) {
				return null;
			}

			// Value date (Valutadatum)
			$valueDate = $this->getElementText($entry, 'ValDt/Dt', $ns)
				?? $this->getElementText($entry, 'ValDt', $ns);
			$valutadatum = $valueDate ? $this->parseDate($valueDate) : $buchungsdatum;

			// Amount and currency
			$amountStr = $this->getElementText($entry, 'Amt', $ns);
			if (!$amountStr) {
				return null;
			}

			$betrag = (float)$amountStr;
			if ($betrag === 0) {
				return null;
			}

			$waehrung = $this->getElementAttr($entry, 'Amt', 'Ccy', $ns) ?? 'EUR';
			$betrag = abs($betrag);

			// Extract counterparty and reference info
			$partnername = '';
			$verwendungszweck = '';
			$iban = null;
			$bic = null;

			// Try TxDtl (Transaction Details) first
			$txDtls = $this->xpath($entry, './/ns:TxDtl', $ns);
			if (empty($txDtls)) {
				$txDtls = $entry->xpath('.//TxDtl');
			}

			if (!empty($txDtls)) {
				$txDtl = $txDtls[0];

				// Get counterparty name and IBAN
				if ($dbtr = $this->getChild($txDtl, 'RltdPties/Dbtr', $ns)) {
					$partnername = $this->getElementText($dbtr, 'Nm', $ns) ?? '';
					if ($dbtrAcct = $this->getChild($txDtl, 'RltdPties/DbtrAcct', $ns)) {
						$iban = $this->getElementText($dbtrAcct, 'Id/IBAN', $ns);
					}
				} elseif ($cdtr = $this->getChild($txDtl, 'RltdPties/Cdtr', $ns)) {
					$partnername = $this->getElementText($cdtr, 'Nm', $ns) ?? '';
					if ($cdtrAcct = $this->getChild($txDtl, 'RltdPties/CdtrAcct', $ns)) {
						$iban = $this->getElementText($cdtrAcct, 'Id/IBAN', $ns);
					}
				}

				// Get remittance information (purpose)
				if ($rmtInf = $this->getChild($txDtl, 'RmtInf', $ns)) {
					$verwendungszweck = $this->getElementText($rmtInf, 'Ustrd', $ns)
						?? $this->getElementText($rmtInf, 'Strd/CdtrRefInf/Ref', $ns) ?? '';
				}
			}

			// Fallback: Try RltdPties at entry level
			if (!$partnername && $rltdPties = $this->getChild($entry, 'RltdPties', $ns)) {
				if ($dbtr = $this->getChild($rltdPties, 'Dbtr', $ns)) {
					$partnername = $this->getElementText($dbtr, 'Nm', $ns) ?? '';
				} elseif ($cdtr = $this->getChild($rltdPties, 'Cdtr', $ns)) {
					$partnername = $this->getElementText($cdtr, 'Nm', $ns) ?? '';
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
				'bic' => $bic,
			];
		} catch (\Exception $e) {
			return null;
		}
	}

	private function getNamespaces(\SimpleXMLElement $xml): array {
		$ns = [];
		foreach ($xml->getDocNamespaces(true) as $prefix => $uri) {
			$ns[$prefix ?: 'ns'] = $uri;
		}
		return $ns;
	}

	private function xpath(\SimpleXMLElement $element, string $path, array $ns): array {
		if (empty($ns)) {
			return $element->xpath($path);
		}
		return $element->xpath($path, $ns);
	}

	private function getChild(\SimpleXMLElement $element, string $path, array $ns): ?\SimpleXMLElement {
		$parts = explode('/', $path);
		$current = $element;

		foreach ($parts as $part) {
			$found = false;
			foreach ($current->children() as $child) {
				if ($child->getName() === $part) {
					$current = $child;
					$found = true;
					break;
				}
			}
			if (!$found) {
				return null;
			}
		}

		return $current;
	}

	private function getElementText(\SimpleXMLElement $element, string $path, array $ns): ?string {
		$child = $this->getChild($element, $path, $ns);
		if ($child) {
			$text = (string)$child;
			return !empty($text) ? $text : null;
		}
		return null;
	}

	private function getElementAttr(\SimpleXMLElement $element, string $path, string $attr, array $ns): ?string {
		$child = $this->getChild($element, $path, $ns);
		if ($child && isset($child[$attr])) {
			return (string)$child[$attr];
		}
		return null;
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
