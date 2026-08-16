# Test-Daten für WeinsteigFinance

## Was ist drin?

Die `TESTDATA.sql` Datei enthält Beispieldaten für die Entwicklung und das Testen:

### Vorschreibungen (weinsteig_vorschreibungen)
- **Member 1**: 7 Vorschreibungen (Feb-Aug 2026)
  - Feb-Mai: bezahlt ✓
  - Jun-Aug: offen ⏳

- **Member 2**: 7 Vorschreibungen (Feb-Aug 2026)
  - Feb-Jun: bezahlt ✓
  - Jul-Aug: offen ⏳

- **Member 3**: 7 Vorschreibungen (Feb-Aug 2026)
  - Feb-Apr: bezahlt ✓
  - Mai-Aug: offen ⏳

### Zahlungen (weinsteig_zahlungen)
- **Member 1**: 5 Zahlungen
  - 4x matched (60€ jeweils)
  - 1x pending (30€)

- **Member 2**: 5 Zahlungen
  - 5x matched (60€ jeweils)

- **Member 3**: 3 Zahlungen
  - 3x matched (60€ jeweils)

## Wie man die Testdaten einfügt

### Option 1: Via Nextcloud CLI (empfohlen)
```bash
docker exec -i nextcloud_db mysql -u nextcloud -p[PASSWORD] nextcloud < TESTDATA.sql
```

### Option 2: Via phpMyAdmin
1. Öffne phpMyAdmin
2. Wähle die `nextcloud` Datenbank
3. Gehe zum Tab "SQL"
4. Kopiere den Inhalt von `TESTDATA.sql`
5. Klick "Ausführen"

### Option 3: Via Shell in Nextcloud Container
```bash
docker exec -i nextcloud_db mysql nextcloud < TESTDATA.sql
```

## Was kann man dann testen?

### Journal-UI (als Member)
1. Login als User (z.B. `testuser1`) in Gruppe `mitglieder`
2. Gehe zu `/apps/weinsteigfinance/journal`
3. Du siehst:
   - **Saldo**: Unterschiedlich je nach Member
   - **Offene Vorschreibungen**: 180€ (3 Monate × 60€)
   - **Eingegangene Zahlungen**: 240€ oder 180€ je nach Member
   - Alle Vorschreibungen mit Status
   - Alle Zahlungen mit Status

### Buchhaltungs-Abgleich
- Member 1: Saldo = 0€ - 120€ = -120€ (schuldig)
- Member 2: Saldo = 300€ - 120€ = +180€ (Guthaben)
- Member 3: Saldo = 180€ - 240€ = -60€ (schuldig)

## Daten zurücksetzen

Wenn du die Testdaten löschen möchtest:

```sql
DELETE FROM oc_weinsteig_zahlungen WHERE member_id IN (1, 2, 3);
DELETE FROM oc_weinsteig_vorschreibungen WHERE member_id IN (1, 2, 3);
```

Oder alle Daten:

```sql
DELETE FROM oc_weinsteig_zahlung_vorschreibung;
DELETE FROM oc_weinsteig_zahlungen;
DELETE FROM oc_weinsteig_vorschreibungen;
```

## Notes

- Alle Daten sind auf die ersten 3 Häuser (member_id 1-3) bezogen
- Creditor ID, IBAN und BIC in `oc_weinsteig_config` sind Dummy-Werte; die echten werden unter `/admin` eingetragen
- Die User-Member-Zuweisungen müssen separat über `/admin` gemacht werden
- Die IBAN sind Dummy-Daten für Tests
