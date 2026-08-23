# 📋 Weinsteig Finance - Anleitung für Kassier:innen

Eine praktische Schritt-für-Schritt-Anleitung zur Verwaltung von SEPA-Mandaten, Vorschreibungen und Zahlungen.

---

## 📑 Inhaltsverzeichnis

1. [SEPA-Mandate verwalten](#1-sepa-mandate-verwalten)
2. [Bankdaten importieren](#2-bankdaten-importieren)
3. [SEPA Datenträger erstellen](#3-sepa-datenträger-erstellen)
   - [3.1 George Business CSV exportieren](#31-george-business-csv-exportieren)
4. [Zahlungen abgleichen](#4-zahlungen-abgleichen)
5. [Journal & Saldo prüfen](#5-journal--saldo-prüfen)
6. [Häufige Fragen](#häufige-fragen)

---

## 1. SEPA-Mandate verwalten

Das SEPA-Mandat ist die **Vollmacht der Bank für Lastschriften**. Ohne unterschriebenes Mandat können wir vom Mitglied nicht abbuchen.

### Workflow Übersicht
```
1. IBAN eingeben (Mitglied oder Kassier:in)
   ↓
2. Mandatsvorlage generieren
   ↓
3. Unterschreiben (per ID Austria oder per Hand)
   ↓
4. Hochladen
   ↓
5. Kassier:in prüft & gibt frei
   ↓
6. Fertig! Lastschrifts können eingezogen werden
```

### Schritt 1: IBAN hinterlegen

**Zwei Möglichkeiten:**

#### Option A: Mitglied trägt selbst ein (via Nextcloud)
1. Mitglied loggt sich in Nextcloud ein
2. Navigiert zu **💳 SEPA Lastschrift**
3. Trägt IBAN und Kontoinhaber-Name ein
4. Speichert

#### Option B: Kassier:in trägt per Telefon/WhatsApp ein
1. Kassier:in fragt Mitglied nach IBAN
2. Trägt Daten unter **👥 Admin: Häuser & Benutzer** → **[Haus wählen]** → **IBAN-Bereich** ein
3. Speichert

**ℹ️ Wichtig**: Die IBAN wird automatisch validiert (korrekte Länder, Prüfsumme).

---

### Schritt 2: Mandatsvorlage generieren

Sobald eine IBAN hinterlegt ist, kann die Mandatsvorlage erzeugt werden.

**Im Mitglieder-Profil (💳 SEPA Lastschrift):**
1. Unter **"SEPA-Mandat"** auf **"📄 Mandat-Vorlage generieren"** klicken
2. Vorlage wird als **PDF heruntergeladen**
3. PDF enthält:
   - Energiegenossenschaft Weinsteig (Adresse, Kontakt)
   - SEPA-Mandate-Text
   - IBAN (zum Unterschreiben)
   - Widerrufsrecht

**Zwei Wege zum Mitglied:**

#### Option A: Mitglied lädt selbst herunter
- Mitglied loggt sich ein → **💳 SEPA Lastschrift** → PDF runterladen

#### Option B: Kassier:in verschickt PDF
1. Kassier:in lädt PDF herunter
2. Sendet per **E-Mail** oder **WhatsApp** ans Mitglied

---

### Schritt 3: Mitglied unterschreibt

Das Mitglied hat zwei Optionen:

#### Option A: Digital per ID Austria ✓ (Empfohlen)
- Mitglied öffnet PDF in der Nextcloud Web-App
- Klickt auf **"🔐 Digital unterschreiben mit ID Austria"**
- ID Austria-Login
- Signatur wird direkt ins PDF eingebunden
- Automatisch wieder hochgeladen ✅

#### Option B: Per Hand mit Kugelschreiber
1. Mitglied druckt PDF
2. Unterschreibt per Hand im **Unterschriftsfeld**
3. Scannt oder fotografiert unterschriebenes Dokument
4. Lädt hochgeladen wieder hoch (siehe Schritt 4)

---

### Schritt 4: Unterschriebenes Mandat hochladen

**Via Nextcloud (Mitglied):**
1. Mitglied loggt sich ein → **💳 SEPA Lastschrift**
2. Unter **"Unterschriebenes Mandat hochladen"** auf **"📤 Datei wählen"** klicken
3. Unterschriebenes PDF auswählen
4. Klickt **"Hochladen"**
5. System speichert Datei versioniert (v1, v2, v3, ...)
6. **Status: ⏳ Ausstehend (gelb)** = Wartet auf Kassier:in-Freigabe

**Oder per Kassier:in (via Email/WhatsApp):**
1. Mitglied sendet unterschriebenes PDF per Email/WhatsApp an Kassier:in
2. Kassier:in loggt sich in Nextcloud ein
3. Geht zu **👥 Admin: Häuser & Benutzer** → **[Haus wählen]** → **Mandate-Bereich**
4. Klickt **"📤 Mandat hochladen"** und lädt Datei hoch

---

### Schritt 5: Kassier:in prüft & gibt Mandat frei

**Das ist der wichtigste Schritt!** Nur geprüfte Mandate können für Lastschriften genutzt werden.

1. Kassier:in loggt sich ein → **👥 Admin: Häuser & Benutzer** → **[Haus wählen]**
2. Unter **"Hochgeladene Mandate"** sieht Kassier:in alle Dateien:
   - **🟡 Ausstehend** = Nicht geprüft
   - **🟢 Genehmigt** = Freigegeben & aktuell gültig

3. Kassier:in **prüft manuell**:
   - ✓ Unterschrift vorhanden?
   - ✓ IBAN korrekt?
   - ✓ Kontoinhaber korrekt?
   - ✓ PDF lesbar?

4. Klickt **"✓ OK"** Button
   - Status wird zu **🟢 Genehmigt**
   - Timestamp wird gespeichert (wer, wann)
   - **Löschen ist nicht mehr möglich** (Schutz der Genossenschaft)

**⚠️ Wichtig:**
- Nur **genehmigte Mandate** werden für Lastschriften verwendet
- **Gelöschte Mandate** können nicht mehr wiederhergestellt werden
- Bei Änderung → neues Mandat hochladen (v2, v3, ...)

---

## 2. Bankdaten importieren

Monatlich erhaltet ihr vom Bankinstitut einen **Kontoauszug als CSV-Datei**. Diese wird importiert, um Zahlungen zu registrieren.

### CSV Import

1. Loggt euch ein → **📥 Admin: Import Kontoauszüge**
2. Unter **"Kontoauszug hochladen"** klickt auf **"📤 Datei wählen"**
3. CSV-Datei vom Bankinstitut auswählen
4. Klickt **"Hochladen"**

Das System:
- ✓ Importiert alle Bankzeilen
- ✓ Erkennt automatisch **Duplikate** (anhand von Datum + Betrag + Partner)
- ✓ Versucht **automatisch zuzuordnen** (Fuzzy-Matching)

### Auto-Match-Funktion

Nach dem Import seht ihr unter **📥 Unzugeordnete Zahlungen** alle Zeilen.

Das System versucht automatisch zuzuordnen anhand von:
1. **Exakte Adresse-Match** im Verwendungszweck (95% Sicherheit)
2. **Nextcloud Benutzername** (89%)
3. **Nachname des Zahlungspflichtigen** (87-90%)

Klickt **"⚡ Automatisch zuordnen"**, um alle automatisch zugeordneten Zahlungen zu verarbeiten.

### Manuelle Zuordnung

Falls eine Zahlung nicht automatisch zugeordnet wird:

1. Klickt in der **Unzugeordnete Zahlungen** Tabelle auf die Zeile
2. Wählt das **Haus** aus dem Dropdown
3. Klickt **"✓ Speichern"**

---

## 3. SEPA Datenträger erstellen

Der SEPA Datenträger ist eine **Datei für die Bank**, mit der wir **Lastschriften einziehen**.

### Ablauf

1. Loggt euch ein → **📄 SEPA-Datenträger**
2. Das System zeigt automatisch:
   - Alle Häuser mit **genehmigten Mandaten** ✓
   - **Offener Saldo** pro Haus (Vorschreibungen - Zahlungen)
   - Nur Häuser mit **offenen Beträgen** werden angezeigt

3. Klickt **"📥 SEPA Datenträger herunterladen"**
4. Das System erzeugt eine **XML-Datei** (ISO 20022 Standard)
5. Datei wird heruntergeladen → an die Bank übermitteln

### Offener Saldo berechnet sich aus:
```
Offener Saldo = Vorschreibungen - eingegangene Zahlungen
```

**Beispiel:**
- Haus "Musterstraße 1"
- Offene Vorschreibungen: 500€
- Eingegangene Zahlungen: 200€
- → Offener Saldo: 300€

Im Datenträger wird dann die **Lastschrift von 300€** eingezogen.

### 🏦 An der Bank einreichen

1. SEPA-Datei zur Bank bringen (oder per Online-Banking hochladen)
2. Bank führt Lastschriften automatisch durch
3. Geld kommt auf Genossenschaftskonto
4. → Wieder **Kontoauszug importieren** (siehe Schritt 2)

---

## 3.1 George Business CSV exportieren

Die moderne Alternative zur XML-Datei: **George Business CSV** für Lastschrift-Batch-Processing.

### 🎯 Wann nutzen?

- **George Business Banking** ist euer Online-Banking-System
- Ihr könnt Lastschriften direkt im Portal einziehen statt XML-Upload
- **CSV-Format** ist einfacher zu verarbeiten als XML
- Jede Zeile = 1 Lastschrift-Auftrag

### 📋 Was wird exportiert?

Das System generiert automatisch:
- ✅ Alle Häuser mit **genehmigten Mandaten**
- ✅ Nur Häuser mit **mindestens 0,10€ offenen Betrag**
- ✅ Korrektes Vorzeichen (Schulden als positive Lastschrift-Beträge)
- ❌ Häuser mit 0€ Saldo (keine unnötigen Einträge)
- ❌ Häuser mit Guthaben/Überzahlung (negative Beträge)

**Beispiel-Daten:**
| Haus | Offener Saldo | Export |
|------|--------------|--------|
| Musterstr. 1 | -240€ | ✅ +240,00€ einziehen |
| Musterstr. 2 | 0€ | ❌ skipped |
| Musterstr. 3 | +50€ | ❌ skipped (Guthaben) |
| Musterstr. 4 | -0,05€ | ❌ skipped (< 0,10€) |

### 🔄 Schritt-für-Schritt

#### Schritt 1: George CSV generieren

**📚 Referenzmaterialien:**
- Siehe auch: `docs/george/CSV-Import-Ausfuellhilfe-DE.pdf` (offizielle Bank-Dokumentation)
- Template: `docs/george/Lastschrift-Vorlage-062025.csv` (leere Vorlage)
- Dokument: `docs/george/SEPA_Basislastschrift_vorausgefuellt.docx` (SEPA Mandat-Vorlage)

1. Loggt euch ein → **📄 SEPA-Datenträger**
2. Das System zeigt:
   - 🟡 **Oben**: Häuser mit **ausstehenden Mandaten** (rot = nicht freigegeben)
   - 🟢 **Unten**: Häuser mit **genehmigten Mandaten** (Kartenlayout)
3. Klickt **"🏦 George Business (SDD) exportieren"**
   - CSV-Datei wird heruntergeladen
   - Dateiname: `sepa-lastschrift-YYYY-MM-DD.csv`

#### Schritt 2: CSV in George Business hochladen

1. **George Business Online-Banking öffnen**
   - https://businessportal.erstegroup.com/ (oder euer Bankinstitut)
   - Mit Benutzername & PIN anmelden
2. Navigiert zu: **Zahlungsverkehr** → **Lastschriften** → **SDD-Sammlung importieren**
3. Klickt **"Datei auswählen"**
4. Wählt die heruntergeladene CSV-Datei
5. Klickt **"Hochladen"** oder **"Weiter"**

#### Schritt 3: Prüfen & Bestätigen

George zeigt euch eine Vorschau:
- **Anzahl Lastschriften**: z.B. "3 Einzüge"
- **Gesamtbetrag**: z.B. "240,00€"
- **Fälligkeitsdatum**: Automatisch berechnet (meist nächster Bankgeschäftstag)

**Prüfet vor Bestätigung:**
- ✓ Anzahl passt (z.B. erwartet ihr 3, seht 3)
- ✓ Gesamtbetrag passt (z.B. erwartet ihr 240€, seht 240€)
- ✓ Keine Fehler-Meldungen (rote Warnungen)

Klickt **"Bestätigen"** oder **"Ausführen"**

#### Schritt 4: Lastschriften verarbeiten

George verarbeitet die Sammlung:
- Status ändert sich zu: **"In Bearbeitung"**
- oder direkt zu: **"Ausgeführt"**
- Timeout: Meist **1-2 Bankgeschäftstage**

**Nach Verarbeitung:**
- George zeigt: **"Lastschrift-Sammlung erfolgreich eingereicht"**
- Ihr erhaltet eine Bestätigungs-Email

#### Schritt 5: Zahlungen im Journal prüfen

Nach 1-2 Tagen:
1. Fragt bei eurer Bank den **aktuellen Kontostand** ab
2. Oder wartet auf **Kontoauszug-Email**
3. Importiert den neuen Kontoauszug (siehe Kapitel 2)
4. System ordnet Zahlungen automatisch zu
5. Im **Journal** seht ihr die eingezogenen Beträge

**Saldo sollte sich erhöhen:**
```
VORHER:
Haus 1: -240€ Schuld

NACHHER (nach Zahlung):
Haus 1: 0€ (wenn exakt bezahlt) oder +X€ (wenn zu viel gezahlt)
```

### ❓ Was passiert bei Fehlern?

#### ❌ George lehnt CSV ab: "Invalid Format"
- CSV ist fehlerhaft → Kontaktiert Technischen Support
- Oder: Datei wurde mit Excel geöffnet & gespeichert (Zeichencodierung kaputt)
  - **Lösung**: Neu exportieren, nicht mit Excel öffnen!

#### ❌ George lehnt ab: "Ungültige IBAN"
- Eine IBAN in der CSV ist fehlerhaft
- **Lösung**: Prüft im Journal, welches Haus fehlerhafte IBAN hat → korrigiert

#### ❌ George lehnt ab: "Mandat nicht genehmigt"
- Eine der Lastschriften hat kein genehmigtes Mandat
- **Lösung**: Das sollte nicht vorkommen! Kontaktiert Technischen Support

#### ❌ George akzeptiert, aber Zahlungen kommen nicht
- Wahrscheinlich: Konto hat nicht genug Deckung
- **Lösung**: George zeigt im Portal den Fehler → Konto-Manager kontaktieren

### 💡 Tipps

1. **Vor Export**: Prüft im **Journal**, ob alle Salden korrekt sind
   - Klickt auf jedes Haus → **"📊 Journal"**
   - Vergewissert euch, dass die Schulden aktuell sind
2. **Mehrfach exportieren**: Ihr könnt die gleiche CSV mehrfach exportieren
   - Aber: **George erkennt Duplikate!** Gleiche Mandats-Referenz = wird skipped
   - Falls ihr mehrmals das gleiche exportiert: **Mandats-Refs ändern sich nicht** → Duplikate-Schutz
3. **CSV-Inhalt**: Öffnet die CSV **nie mit Excel**!
   - Excel zerstört die Zeichencodierung
   - Nutzt: Notepad++, VS Code, oder direkt in George
4. **Backup vor Export**: Vor großen Lastschrift-Läufen empfohlen
   - Geht zu **💾 Backup** → **"Backup jetzt erstellen"**

---

## 4. Zahlungen abgleichen

Nach dem Lastschrift-Einzug erhaltet ihr einen neuen Kontoauszug.

### Prozess

1. **Neuen Kontoauszug importieren** (siehe Schritt 2)
2. System ordnet Zahlungen automatisch zu
3. Unter **📥 Unzugeordnete Zahlungen** seht ihr alle ausstehenden Zeilen
4. Manuelle Zuordnung falls nötig
5. Unter **💰 Zahlungen** seht ihr die aktuellen Zahlungen

### Status-Anzeige

- 🟡 **Unzugeordnet** = Zahlung eingegangen, aber unklar von welchem Haus
- 🟢 **Zugeordnet** = Zahlung ist einem Haus zugeordnet

---

## 5. Journal & Saldo prüfen

Das **Journal** zeigt die komplette Übersicht pro Haus.

### Journal öffnen

1. Loggt euch ein → **👥 Admin: Häuser & Benutzer**
2. Klickt auf ein Haus
3. Klickt **"📊 Journal"** (grüner Button rechts oben)
4. Oder direkt: **📊 Journal** im Hauptmenü → **[Haus auswählen]**

### Im Journal seht ihr:

#### 📋 Vorschreibungen-Tabelle
- Datum der Vorschreibung
- Monat (z.B. "Januar 2025")
- Vorschreibener Betrag (Standard: 60€)
- Status: 🔴 Offen, 🟡 Teilweise bezahlt, 🟢 Bezahlt

#### 💳 Zahlungen-Tabelle
- Datum der Zahlung
- Eingegangener Betrag
- Partner (Bankinstitut oder Zahlungspflichtiger)
- Verwendungszweck

#### 📊 Statistik-Box
```
Summe Vorschreibungen:  500€
- Bezahlte:            300€
+ Offene:              200€

Total eingegangene Zahlungen: 300€

Aktueller Saldo:  +100€ (Guthaben)
```

---

### Saldo-Interpretation

- 🟢 **Positiv (+100€)**: Haus hat zu viel gezahlt → Guthaben
- 🟡 **Neutral (0€)**: Haus und Genossenschaft sind quitt
- 🔴 **Negativ (-100€)**: Haus schuldet noch Geld → Rückstand

---

## 6. Monatliche Routine

Das System **generiert automatisch am 1. des Monats** neue Vorschreibungen (60€ pro Haus).

Falls nötig, könnt ihr auch manuell generieren:

1. Loggt euch ein → **📋 Vorschreibungen**
2. Klickt **"📝 Vorschreibungen generieren"**
3. Wählt **Jahr & Monat**
4. Klickt **"Generieren"**

---

## Häufige Fragen

### F: Was passiert, wenn ein Mitglied die IBAN ändert?
**A:** Kassier:in trägt neue IBAN ein, generiert neue Mandatsvorlage, Mitglied unterschreibt neu. Das alte Mandat bleibt gespeichert (für Audit-Trail), das neue wird genehmigt.

### F: Kann ein Mitglied sein Mandat widerrufen?
**A:** Ja, unter **💳 SEPA Lastschrift** → **"🚫 Mandat widerrufen"**. Dann funktionieren Lastschriften nicht mehr. Die Genossenschaft sieht das aber im Journal (Audit-Trail).

### F: Was wenn eine Zahlung falsch zugeordnet wurde?
**A:** Unter **💰 Zahlungen** könnt ihr die Zuordnung ändern:
1. Klickt auf die Zahlung
2. Klickt **"🔄 Zuordnung ändern"**
3. Wählt das richtige Haus
4. Speichert

### F: Wie lange speichert das System die Mandate?
**A:** Unbegrenzt! Alle unterschriebenen & genehmigten Mandate bleiben im System (für rechtliche Sicherheit). Gelöschte Mandate sind unwiederbringlich weg (deshalb der Schutz).

### F: Was ist die Rolle "Kassier:in"?
**A:** 
- Kann alles sehen (wie Admin)
- **KANN NICHT:** Häuser verwalten, Benutzer löschen, System-Einstellungen ändern
- **KANN:** Mandate genehmigen, Zahlungen zuordnen, Datenträger erstellen

### F: Was ist die Rolle "Admin/Obperson"?
**A:**
- **KANN:** Alles (Häuser, Benutzer, Einstellungen, Mandate)
- Kassier:innen berichten an Admin/Obpersonen

### F: Wie exportiere ich einen Datenträger ohne Lastschriften?
**A:** Geht zu **📄 SEPA-Datenträger** → Es werden nur Häuser mit **offenen Beträgen & genehmigten Mandaten** angezeigt. Sind keine Häuser gelistet, gibt es auch keinen Download-Button.

### F: Wo sehe ich alle Backups?
**A:** Nur Admin/Obpersonen: **📊 Backup-Status** → Zeigt letzte Backups, nächste Backup-Zeit, Download-Optionen.

### F: George CSV vs. SEPA XML Datenträger - was ist der Unterschied?
**A:** 
- **SEPA XML**: Standardformat (ISO 20022), meist für automatisierte Bank-Systeme
- **George CSV**: Modernes Format für George Business Portal, einfacher zu verarbeiten
- **Beide** ziehen die gleichen Lastschriften ein
- **Unterschied**: CSV ist benutzerfreundlicher im George Portal, XML ist universeller für alle Banken

### F: Warum werden manche Häuser nicht in der George CSV exportiert?
**A:**
- ❌ Kein genehmigtes Mandat (Kassier:in muss erst freigeben)
- ❌ Keine IBAN hinterlegt
- ❌ Saldo ist 0€ (keine Schuld)
- ❌ Saldo ist positiv (Guthaben, wir schulden dem Haus Geld)
- ❌ Saldo ist zwischen -0,01€ und -0,09€ (unter 0,10€ Mindestbetrag)
- ✅ Mandat wurde widerrufen (wird nicht exportiert)

### F: Kann ich die George CSV mehrfach hochladen?
**A:** Ja, aber:
- George erkennt Duplikate anhand der "Mandatsreferenz" (= Haus-Adresse)
- Wenn ihr die **gleiche CSV** nächste Woche nochmal hochladet: George blockiert Duplikate
- **Lösung**: Exportiert eine neue CSV (dann sind die Salden anders und George akzeptiert es)

### F: Was heißt "Akontozahlung" (ADVA) in der George CSV?
**A:** 
- ADVA = Vorauszahlung auf unbekannte Rechnung
- Das Geld ist eine **Allgemeine Akontozahlung** (nicht spezifisch für Energie)
- Im Gegensatz zu: ENRG = speziell für Energierechnungen
- **Für Weinsteig**: Nutzen wir ADVA, weil das Geld für diverse Gemeinschaftskosten verwendet wird (Strom, Internet, Wartung, usw.)

---

## 💡 Tipps & Tricks

1. **Vor dem Datenträger-Export**: Prüft im Journal, ob alle Zahlungen korrekt zugeordnet sind
2. **IBAN-Änderungen**: Immer neues Mandat anfordern (alte Mandate sind nicht mehr gültig)
3. **CSV-Import**: Nutzt **Auto-Match** zuerst, dann manuelle Zuordnung für Rest
4. **Journal-Screenshot**: Macht vor größeren Operationen einen Screenshot des aktuellen Saldos
5. **Backup erstellen**: Vor wichtigen Operationen kann auf der **💾 Backup-Status** Seite ein manuelles Backup erstellt werden

---

## 📞 Kontakt & Support

Bei Fragen oder Problemen:
- **Nextcloud Admin**: Kontaktiert die Obpersonen
- **Technischer Support**: hello@energiegenossenschaft-weinsteig.at

---

**Version**: 1.5.0  
**Letzte Aktualisierung**: 2026-08-23  
**Für**: Kassier:innen der Energiegenossenschaft Weinsteig

*Gebaut mit ❤️ für Energiegenossenschaften*
