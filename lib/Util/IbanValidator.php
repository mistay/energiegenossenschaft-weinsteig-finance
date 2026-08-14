<?php

declare(strict_types=1);

namespace OCA\WeinsteigFinance\Util;

class IbanValidator {
	public static function validate(?string $iban): bool {
		if ($iban === null || $iban === '') {
			return true; // optional field
		}

		$iban = strtoupper(preg_replace('/\s+/', '', $iban));

		// Format check: 2 letters + 2 digits + 1-30 alphanumeric
		if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/', $iban)) {
			return false;
		}

		// Move first 4 chars to end
		$iban = substr($iban, 4) . substr($iban, 0, 4);

		// Replace letters with numbers (A=10, B=11, ..., Z=35)
		$numeric = '';
		for ($i = 0; $i < strlen($iban); $i++) {
			$char = $iban[$i];
			$numeric .= is_numeric($char) ? $char : (ord($char) - 55);
		}

		// mod 97 check
		return bcmod($numeric, '97') === '1';
	}
}
