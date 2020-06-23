# Abfrage der DeWIS-API

## Version 1.0.2 (2020-06-23)

* Datei LICENSE entfernt
* Fix: PHP Warning: Invalid argument supplied for foreach() in contao-dewis-bundle/src/Classes/Verband.php on line 210
* Add: Backend-CSS
* Add: Backend-Icon (blauer Stern)

## Version 1.0.1 (2020-05-05)

Add: Abhängigkeit terminal42/contao-avatar (für Spielerbilder in den Karteikarten)

## Version 1.0.0 (2020-05-05)

Fix: undefined-Einträge im Chart entfernt
Fix: Sortierung von Verbandslisten geändert - bei höherem Index bessere Plazierung
Fix: 404-Fehlerseite an Contao 4 angepaßt

## Version 0.0.5 (2020-05-04)

Fix: Namespace-Problem in Verein.php
Fix: Turnierkurzname wurde wegen UTF-8 nicht richtig generiert
Fix: Konstanten-Problem in Templates beseitigt

## Version 0.0.4 (2020-05-04)

Fix: Namespace-Problem beim Cache-Löschen im Wartungsmodus
New: Konstante KARTEISPERRE_GAESTE ersetzt durch Feld in tl_settings
New: Konstante PASSIVE_AUSBLENDEN ersetzt durch Feld in tl_settings
New: Konstante GEBURTSJAHR_AUSBLENDEN ersetzt durch Feld in tl_settings
New: Konstante GESCHLECHT_AUSBLENDEN ersetzt durch Feld in tl_settings
New: Alias-Konstanten für Modulseiten im Menü ersetzt

## Version 0.0.3 (2020-05-03)

Fix: Namespaces komplett ersetzt, Pfade CSS/JS korrigiert

## Version 0.0.2 (2020-05-02)

Fix: composer.json

## Version 0.0.1 (2020-05-02)

Übernahme C3-Version 1.3.0 mit neuer Funktion vom 06.08.2019: Hinweis unter Scoresheet auf näherungsweise Berechnung
Initialversion als C4-Bundle
