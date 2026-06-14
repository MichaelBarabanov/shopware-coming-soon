# 1.1.1

- Fix: "Bestimmte URLs" greifen jetzt auch bei SEO-URLs von Kategorien und Produkten (z. B. /sommer-sale), die Shopware intern auf /navigation/<id> umschreibt, bevor die Prüfung lief. Es wird nun sowohl der SEO-Pfad als auch der technische Pfad geprüft.

# 1.1.0

- Neue Option "Bestimmte URLs": einzelne Pfade sperren (z. B. /sommer-sale) statt des gesamten Verkaufskanals
- Pfad-Matching ist segmentgenau und case-insensitiv, berücksichtigt Unterseiten und den Platzhalter *
- Bestimmte URLs wirken unabhängig vom kanalweiten Schalter; IP-Whitelist, Vorschau-Link und Countdown-Auto-Ende greifen weiterhin
- Kompatibel mit Shopware 6.7

# 1.0.0

- Coming-Soon-/Wartungsseite pro Verkaufskanal
- Konfigurierbarer Titel, Text, Hintergrundbild, Logo und Akzentfarbe
- Optionaler Countdown mit konfigurierbarem Startdatum
- Optionale automatische Deaktivierung nach Ablauf des Countdowns
- IP-Whitelist (IPv4/IPv6, CIDR-Notation unterstützt)
- Vorschau-Link mit geheimem Token zum Testen bei aktiver Seite
- SEO-sicher: Antwortet mit HTTP 503, Retry-After und noindex-Headern
- HTTP-Cache wird beim Aktivieren/Deaktivieren automatisch geleert
