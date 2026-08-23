# 🤖 Claude Development Guidelines

Anleitung für Entwicklung mit Claude KI-Assistent.

---

## 📋 Wichtige Konventionen

### 1. **Version Bumping**
- **Vor jedem Commit**: Version in `appinfo/info.xml` erhöhen (mindestens +0.0.1)
- **Beispiel**: `1.4.8` → `1.4.9`
- Version wird im Browser angezeigt und sollte immer aktuell sein

### 2. **Git Commits**
- Aussagekräftige Commit-Messages auf Deutsch oder Englisch
- Präfix nach Art: "Add", "Fix", "Update", "Remove", "Refactor"
- Beispiel:
  ```
  Fix downloadSignedMandate to support multiple file extensions
  - Remove hardcoded .pdf extension lookup
  - Find actual file based on version number
  - Set correct Content-Type header
  ```
- **Immer pushen nach dem Commit** (nicht lokal sammeln)

### 3. **Dateiendungen & Dateitypen**
- **Mandate**: PDF, JPEG, PNG (nicht nur PDF!)
  - Originalendung beibehalten (`mandat_v1.jpg` nicht `mandat_v1.pdf`)
  - MIME-Type validieren (Sicherheit)
  - Regex muss flexibel sein: `/^name_v(\d+)\.(pdf|jpg|jpeg|png)$/`

### ⚠️ **Terminologie**
- **Niemals** "whitelist" oder "blacklist" verwenden!
- Statt dessen: "allowlist" (Erlaubnis-Liste) oder "blocklist" (Block-Liste)
- Diese Begriffe sind rassistisch geprägt und sollten nicht verwendet werden
- **CSV-Dateien**: Semikolon-Trennzeichen, DD.MM.YYYY Daten
- **ZIP-Backups**: Struktur = `/data/backup/weinsteig-finance-backup_YYYY-MM-DD_HH-MM-SS.zip`

### 4. **Sicherheit & API**
- **Alle APIs**: 3-Layer Security Check
  1. `#[NoAdminRequired]` + `#[NoCSRFRequired]` Decorators
  2. `if (!$this->isObperson()) return 403;`
  3. Row-level check: `if (!$this->canEditMember($id)) return 403;`
- **Dateivalidierung**: Immer MIME-Type prüfen (nicht nur Extension)
- **Fehler-Handling**: Aussagekräftige Fehlermeldungen für Benutzer

### 5. **JavaScript & CSP**
- **Inline-Scripts NICHT erlaubt** (CSP blockiert sie)
- **Externe Scripts laden**: `script('weinsteigfinance', 'myfile');`
  - Files gehen in `/js/myfile.js`
- **Fetch mit Authentifizierung**: `credentials: 'include'`
  - Sonst wird API "user not logged in" zurückgeben
- **Error-Handling**: Try-catch + aussagekräftige Fehler an User

### 6. **Database & Queries**
- **QueryBuilder immer verwenden**: `$qb = $this->db->getQueryBuilder();`
- **Named Parameters**: `$qb->createNamedParameter($value)`
- **Regex in DB**: Flexibel halten (z.B. `(pdf|jpg|png)`)
- **Regex im Code**: Immer mit escaped Slashes: `/^pattern$/`
- **Aggregationen**: Verwende ORDER BY ... LIMIT 1 statt MIN()/MAX()
  - ❌ `SELECT MIN(year * 100 + month)` → MySQL Fehler
  - ✅ `SELECT year, month ORDER BY ... ASC LIMIT 1` → Funktioniert überall

### **Saldo-Semantik (WICHTIG!)**
- **Definition**: `Saldo = Zahlungen - Offene Vorschreibungen`
- **Negatives Saldo** = Member schuldet Geld (Debt)
  - `-240€` bedeutet: Member schuldet **+240€**
  - Anzustrebender Zustand: Einzuziehen per Lastschrift
- **Positives Saldo** = Member hat Guthaben (Credit)
  - `+50€` bedeutet: Wir schulden dem Member
  - Nicht zum Einziehen!
- **Mahngrenze**: `Saldo <= -10.0` (negativ UND >= 10€ Schuld)
- **UI-Anzeige**:
  - `-240€` Saldo → zeige "+240€ Schuld"
  - `+50€` Saldo → zeige "+50€ Guthaben"
  - Label per Wert: negative = "Schuld", positive = "Guthaben"

### 7. **Templates & UI**
- **Einheitliches Design**:
  - Info-Boxen: `background: #e3f2fd; border-left: 4px solid #0082c9;` (blau)
  - Success: `background: #e8f5e9; border-left: 4px solid #28a745;` (grün)
  - Orange/Rot vermeiden (wirkt wie Fehler)
- **Responsive**: Flexbox, max-width 900px, mobile-friendly
- **Navigation**: über `nav.php` (wird included)
- **Menü-Punkte**: Nur in `templates/nav.php` hinzufügen (mit emoji prefix)

### 8. **Mahnfunktion (Reminders)**
- **2 Mahnstufen**: Zahlungserinnerung (S1) → Mahnung (S2, Letzte)
- **Bedingungen für automatische Mahnung** (ALLE müssen erfüllt sein):
  1. Kontoauszug aktuell (< 7 Tage alt)
  2. Schuld >= 10€ (d.h. `openAmount <= -10.0`)
  3. Älteste Rechnung > 30 Tage alt
  4. Letzte Mahnung > 14 Tage her
  5. Kein Mahnstop aktiv
- **Debug-Feature**: `/mahnungen/` → "ℹ️ Warum?" zeigt alle Bedingungen pro Haus
- **Mahnstop**: Kann pro Haus mit optionalem Enddatum gesetzt werden
- **Manuelle Mahnung**: Jederzeit per Knopfdruck möglich (ignoriert Bedingungen außer Mahnstop)
- **Editierbare Mahnstufen-Texte**:
  - **Zugriffsrollen**: Obpersonen + Kassier:innen
  - **Speicherorte**:
    - Admin (obpersonen only): Admin → Einstellungen → Mahnstufen-Texte Sektion
    - Mahnungen-Seite: /mahnungen/ → ⚙️ Mahnstufen-Texte Button → Modal
  - **Tabelle**: `oc_weinsteig_reminder_texts` (stage INT 1-2, subject, body, created_at, updated_at)
  - **API Endpoints**:
    - `GET /api/reminder-texts` – Alle Texte laden (Obpersonen + Kassier:innen)
    - `POST /api/reminder-texts/{stage}` – Text speichern (stage 1-2 only, Obpersonen + Kassier:innen)
  - **Platzhalter** (werden bei Versand ersetzt):
    - `{name}` → Zahlungspflichtiger Name
    - `{address}` → Hausadresse
    - `{amount}` → Offener Betrag €
    - `{duedate}` → Ursprüngliches Fälligkeitsdatum
  - **Datenbank-Migration**: `Version0014Date20260824001300.php` erstellt Tabelle mit Defaults
  - **Admin-UI**: 2 Textareas (Betreff + Nachrichtentext pro Stufe) mit Live-Speicherung
  - **Mahnungen-Modal**: ⚙️ Button oben auf /mahnungen/ für schnellen Zugriff
  - **JavaScript**: `admin-config-reminders.js` für Admin-Seite + Funktionen in `reminders.js` für Modal
- **Saldo-Semantik**: Negatives Saldo = Schuld (siehe Punkt 6 oben!)

### 9. **Dokumentation**
- **Anleitungen für Benutzer**:
  - `ANLEITUNG_MITGLIEDER.md` – für normale Mitglieder
  - `ANLEITUNG_KASSIERER.md` – für Kassier:innen/Admins (inkl. Kapitel 3.1 George Business CSV)
  - Deutsch, einfache Sprache, Schritt-für-Schritt
- **README.md**: Technische Übersicht + Feature-Liste (mit Mahnung + George CSV Doku)
- **claude.md**: Diese Datei – Entwickler-Richtlinien
- **Code-Comments**: Auf Deutsch, prägnant

---

## 🔍 Häufige Fehler vermeiden

### ❌ API gibt 404
- **Problem**: Route nicht in `appinfo/routes.php`
- **Lösung**: Route hinzufügen, Cache clearen

### ❌ JavaScript lädt nicht
- **Problem**: Inline-Script blockiert durch CSP
- **Lösung**: In externe Datei verschieben, `script()` verwenden

### ❌ API sagt "user not logged in"
- **Problem**: `fetch()` sendet Cookies nicht
- **Lösung**: `credentials: 'include'` hinzufügen

### ❌ Datei speichern funktioniert nicht
- **Problem**: Verzeichnis existiert nicht
- **Lösung**: `@mkdir($path, 0750, true);` vor `file_put_contents()`

### ❌ Version wird nicht aktualisiert
- **Problem**: `appinfo/info.xml` nicht geändert
- **Lösung**: Vor JEDEM Commit version bumpen

### ❌ Dateidownload zeigt "malformed"
- **Problem**: Falscher Content-Type Header
- **Lösung**: Dateiendung überprüfen, korrekten MIME-Type setzen

---

## 📁 Datei-Struktur

```
lib/
  ├── Controller/
  │   ├── ApiController.php      (REST API, 25+ endpoints)
  │   └── PageController.php     (Template-Routen)
  ├── Service/
  │   ├── BackupService.php      (SQL Dump + ZIP-Archivierung)
  │   ├── ReminderService.php    (3-Stufen Mahnsystem + Bedingungsprüfung + Texte aus DB)
  │   ├── VorschreibungService.php
  │   └── ...
  ├── Migration/
  │   ├── Version0014Date20260824001300.php (Reminder-Texte Tabelle + Defaults)
  │   └── ...
  └── BackgroundJob/
      ├── GenerateBackupJob.php  (Cron: täglich 02:00 AM)
      └── GenerateRemindersJob.php (Cron: täglich 02:00 AM)

templates/
  ├── admin.php                  (Einstellungen + Mahnstufen-Texte)
  ├── backup-status.php          (Backup-UI)
  ├── nav.php                    (Navigation - alle Seiten nutzen)
  └── ...

js/
  ├── admin-config-reminders.js  (Load/Save Reminder-Texte in Admin)
  ├── backup-status.js           (Backup-Status Script)
  └── ...

appinfo/
  ├── routes.php                 (API + Page Routes)
  └── info.xml                   (Version, Background Jobs)
```

---

## 🚀 Deployment Checklist

Vor größeren Änderungen:

- [ ] Version in `appinfo/info.xml` erhöht?
- [ ] Alle Routes in `appinfo/routes.php` eingetragen?
- [ ] Alle Security Checks eingebaut (isObperson, canEdit)?
- [ ] Fehlerbehandlung für Edge Cases?
- [ ] Cache-Clear durchgeführt?
- [ ] Im Browser getestet (Chrome, Firefox, Mobile)?
- [ ] README.md aktualisiert?
- [ ] Commit mit aussagekräftiger Message?
- [ ] Gepusht auf main?

---

## 📚 Useful Resources

- **Nextcloud AppFramework**: `/index.php` → `/apps/[app]/`
- **IDB Connection**: `QueryBuilder` für SQL
- **File Upload**: `$_FILES['file']` + MIME-Type validation
- **Background Jobs**: TimedJob für regelmäßige Tasks
- **PDF Generation**: mPDF Library (schon installiert)

---

## 💬 Kommunikation mit Claude

Wenn du wieder mit Claude arbeiten möchtest:

1. **Branch/Kontext**: Sag welche Feature/Bug du angehen willst
2. **Testinstruktionen**: Wie man das Feature testet
3. **Fehler-Details**: Screenshots, Error Messages, URLs
4. **Constraints**: PHP-Versionen, Browser-Support, etc.

Beispiel:
```
Feature: Backup-Dateien als JPEG speichern
Fehler: Download zeigt "malformed PDF"
Getestet: https://[URL]/api/download/2?v=1
Erwartet: JPEG wird als .jpg heruntergeladen, nicht als .pdf
```

---

**Version**: 1.2  
**Letzte Aktualisierung**: 2026-08-24 (Editable Reminder Texts Feature)
**Für**: Entwicklung mit Claude KI-Assistent

*Richtlinien für nachhaltige, sichere und wartbare Code-Entwicklung* ⚡
