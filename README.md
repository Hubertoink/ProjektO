# ProjektO

WordPress Plugin zur Verwaltung von Projekten inkl. Status & Zuständigkeiten und Ausgabe als Gutenberg-Block (Badges + Modal-Details).

## Features

- Custom Post Type: **Projekte**
- Taxonomien: **Status**, **Zuständigkeit**, **Arbeitsbereich**
- Gutenberg Block: **ProjektO – Projekte** (`projekto/projects`)
  - Filter/Legende
  - Anzeige als Badges
  - Details per Modal
  - Option: Status-Punkt (Pin) im Collapsed Mode ein-/ausblenden
- Optionaler Dark-Mode/Theme-Kompatibilität via Einstellungen

## Installation

1. Plugin-Ordner in `wp-content/plugins/ProjektO` ablegen
2. Im WordPress-Backend unter **Plugins** aktivieren
3. Unter **Projekte** Inhalte pflegen (Status/Zuständigkeiten/Arbeitsbereich)
4. Im Editor den Block **ProjektO – Projekte** einfügen

## Block-Optionen (Auszug)

Im Block-Inspector findest du u.a.:

- **Legende anzeigen**
- **Details im Modal öffnen**
- **Status-Punkt (Pin) im Collapsed Mode**
- **Auf Projektseite verlinken**
- **Kurzbeschreibung anzeigen**
- **Zuständigkeit mit Foto**
- **Eckige Badges**
- **Max. Anzahl**, Sortierung

## Entwicklung

Dieses Plugin ist bewusst „plain“ gehalten (kein Build-Step). Block-Editor-Code liegt direkt in:

- `blocks/projekto-projects/editor.js`
- `blocks/projekto-projects/style.css`

## Lizenz

Noch nicht festgelegt (TODO).
