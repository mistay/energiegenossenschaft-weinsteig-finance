// IBAN Validator (ISO 7064 mod-97)
function validateIBAN(iban) {
	if (!iban) return true; // optional field

	iban = iban.toUpperCase().replace(/\s+/g, '');

	// Format check: 2 letters + 2 digits + 1-30 alphanumeric
	if (!/^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/.test(iban)) {
		return false;
	}

	// Move first 4 chars to end
	iban = iban.slice(4) + iban.slice(0, 4);

	// Replace letters with numbers (A=10, B=11, ..., Z=35)
	let numeric = '';
	for (let i = 0; i < iban.length; i++) {
		const char = iban[i];
		numeric += char.charCodeAt(0) > 57 ? (char.charCodeAt(0) - 55) : char;
	}

	// mod 97 check
	return parseInt(numeric) % 97 === 1;
}
