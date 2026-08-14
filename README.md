# energiegenossenschaft-weinsteig-finance

Nextcloud-App **weinsteigfinance** für die Energiegenossenschaft Weinsteig:
Vorschreibungen, SEPA-Mandate und Zahlungen für die 22 Häuser, die sich den
gemeinsamen Technikraum "Brunnenstube" (Strom, Internet) teilen.

Status: Grundgerüst (Hello World). Datenmodell folgt.

## Struktur

| Pfad | Inhalt |
| --- | --- |
| `appinfo/info.xml` | App-Metadaten, Navigationseintrag, Nextcloud-Version |
| `appinfo/routes.php` | Routen (`weinsteigfinance.page.index`) |
| `lib/AppInfo/Application.php` | Bootstrap, `APP_ID` |
| `lib/Controller/PageController.php` | Hello-World-Seite |
| `templates/index.php` | Markup der Seite |
| `css/main.css` | Styles |

Namespace: `OCA\WeinsteigFinance\` → `lib/`

## Entwicklung

```bash
make lint      # PHP-Syntaxcheck
make package   # build/weinsteigfinance.tar.gz für den Server
```

## Installation auf einem Server

Verzeichnis (bzw. Symlink) `weinsteigfinance` unter `apps/` bzw. `apps-extra/`
der Nextcloud-Installation ablegen, dann:

```bash
sudo -u www-data php occ app:enable weinsteigfinance
```

Die App erscheint anschließend als "Weinsteig Finance" in der Kopfzeile.
