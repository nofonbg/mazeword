# mazeword
find a solution word in a maze  

nor issues nor pull requests nor anything else will be responded, use as it is

tested only with moodle 5.3dev(Build: 20260807) 

![](https://github.com/nofonbg/mazeword/blob/main/pix/icon_large.svg)  

# Modul: Maze Word (mod_mazeword)

Ein interaktives Labyrinth-Worträtsel fuer Moodle.

## Beschreibung

Schueler navigieren durch ein generiertes 20x20-Labyrinth und suchen den Weg, der das Lösungswort bildet. Die Buchstaben des Lösungswortes werden rot hervorgehoben und "folgen" dem Spieler als Kette. Sackgassen enthalten falsche Wörter als Ablenkung. Beim Erreichen des Ziels mit allen gesammelten Buchstaben gibt es ein Feuerwerk.

### Features

- **Dynamisches Labyrinth** – wird bei jedem Start neu generiert (10x10 bis 30x30)
- **Loesungswort** – Buchstaben auf dem korrekten Weg, werden rot gesammelt
- **Wurm-Effekt** – gesammelte Buchstaben folgen dem Cursor als Kette
- **Sackgassen** – 3 Ablenkungswörter in Nebenwegen
- **Tastatursteuerung** – Pfeiltasten oder WASD
- **On-Screen-Buttons** – für Mobile-Geräte
- **Spielstand** – wird automatisch gespeichert (Position, gesammelte Buchstaben)
- **Umlaute** – Ä, Ö, Ü werden unterstuetzt, ß wird zu SS konvertiert
- **Lehrer-Statistik** – Anzahl Versuche und Loesungen

## Anforderungen

- Moodle 5.0+
- PHP 8.0+

## Installation

1. Ordner `mazeword` in `/mod/` kopieren
2. Im Moodle-Admin unter *Erweiterungen* das Plugin installieren
3. Datenbanktabellen werden automatisch angelegt

### Manuelle Installation

```bash
cp -r mazeword /path/to/moodle/mod/
cd /path/to/moodle
php admin/cli/upgrade.php
```

## Einstellungen

### Plugin-Einstellungen (Admin)

Keine globalen Einstellungen.

### Aktivitaets-Einstellungen (Lehrer)

| Feld | Beschreibung |
|------|-------------|
| Loesungswort | Das richtige Wort auf dem korrekten Weg (3-30 Zeichen, A-Z, Ä, Ö, Ü) |
| Falsches Wort 1 | Ablenkung in Sackgasse 1 |
| Falsches Wort 2 | Ablenkung in Sackgasse 2 |
| Falsches Wort 3 | Ablenkung in Sackgasse 3 |
| Laabyrinth-Groesse | 10x10, 15x15, 20x20 (Standard), 25x25, 30x30 |

## Spielablauf

1. Lehrer legt Aktivitaet an mit Loesungswort und 3 Falschwoertern
2. Schueler oeffnet Aktivitaet, Labyrinth wird generiert
3. Schueler navigiert von Start (grün) zum Ziel (rot)
4. Ueberfahrene normale Buchstaben verschwinden
5. Loesungsbuchstaben werden rot und folgen als Kette
6. Sieg: Alle Loesungsbuchstaben gesammelt + Ziel erreicht

## Technische Details

### Datenbank

**mdl_mazeword** – Aktivitaetsinstanz

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| id | int | Primärschluessel |
| course | int | Kurs-ID |
| name | varchar(255) | Aktivitaetsname |
| intro | text | Beschreibung |
| introformat | int | HTML-Format |
| solutionword | varchar(255) | Loesungswort (uppercase) |
| wrongword1-3 | varchar(255) | Falschwoerter |
| mazesize | int | Labyrinth-Groesse |
| timecreated | int | Erstellt-Zeitstempel |
| timemodified | int | Geaendert-Zeitstempel |

**mdl_mazeword_attempts** – Schueler-Versuche

| Feld | Typ | Beschreibung |
|------|-----|-------------|
| id | int | Primärschluessel |
| mazewordid | int | FK zu mdl_mazeword |
| userid | int | FK zu mdl_user |
| playerx/playery | int | Aktuelle Spielerposition |
| visitedcells | text | JSON: Besuchte Zellen |
| solutionvisited | text | JSON: Besuchte Loesungszellen |
| clearedcells | text | JSON: Geloeschte Zellen |
| collectedletters | text | JSON: Gesammelte Buchstaben |
| movetrail | text | JSON: Bewegungshistorie |
| completed | int | 0=noch aktiv, 1=geloest |
| timecreated/timemodified | int | Zeitstempel |
| timecompleted | int | Zeitpunkt der Loesung |

### Dateistruktur

```
mod/mazeword/
├── version.php                 # Plugin-Version
├── lib.php                     # Moodle-Hooks
├── mod_form.php                # Lehrer-Formular
├── view.php                    # Hauptseite + AJAX
├── locallib.php                # Hilfsfunktionen
├── index.php                   # Kursuebersicht
├── settings.php                # Admin-Einstellungen
├── styles.css                  # CSS (Referenz)
├── lang/en/mazeword.php        # Englische Strings
├── db/
│   ├── install.xml             # Datenbankschema
│   └── access.php              # Berechtigungen
├── classes/
│   ├── mazeword.php            # Hauptklasse
│   ├── output/
│   │   ├── game_view.php       # Renderable
│   │   └── renderer.php        # Renderer + JS
│   ├── event/
│   │   ├── course_module_viewed.php
│   │   └── attempt_completed.php
│   └── privacy/
│       └── provider.php        # DSGVO
├── backup/moodle2/
│   ├── backup_mazeword_activity_task.class.php
│   ├── backup_mazeword_stepslib.php
│   ├── backup_mazeword_settingslib.php
│   ├── restore_mazeword_activity_task.class.php
│   └── restore_mazeword_stepslib.php
├── pix/
│   ├── icon.svg                # 24x24 Icon
│   └── icon_large.svg          # 150x50 Icon
└── readme.md                   # Diese Datei
```

### Berechtigungen

| Berechtigung | Beschreibung |
|-------------|-------------|
| mod/mazeword:addinstance | Aktivitaet anlegen |
| mod/mazeword:view | Aktivitaet ansehen |
| mod/mazeword:attempt | Spiel spielen |
| mod/mazeword:manageattempts | Versuche verwalten |
| mod/mazeword:configure | Aktivitaet konfigurieren |

### Backup/Restore

Unterstuetzt vollstaendiges Backup und Restore inklusive:
- Aktivitaetsinstanz (Loesungswort, Falschwoerter, Laabyrinth-Groesse)
- Schueler-Versuche (Position, gesammelte Buchstaben, Spielstand)
- Dateien (Intro-Beschreibung)
- User-Mapping bei Kursuebertrag

## Lizenz

GPLv3 oder spaeter

## Autor

Norbert Forster

## Changelog

### Version 1.1 (2026-08-09)
- Umlaute (Ä, Ö, Ü) unterstuetzt, ß wird zu SS konvertiert
- Backup/Restore-Funktionalitaet hinzugefuegt
- CSS inline im Renderer (kein externer Import noetig)
- DSGVO-konforme Datenverarbeitung
- Verschiedene Bugfixes

### Version 1.0 (2026-08-09)
- Initiale Version
- Dynamisches Labyrinth mit Loesungswort
- Wurm-Effekt fuer gesammelte Buchstaben
- Tastatur- und Button-Steuerung
- Automatische Spielstandspeicherung
- Lehrer-Statistik
