# Abfrage der DeWIS-API

## Version 1.5.2 (2022-04-26)

* Fix: Modules/Bestenliste.php - Ermittlung der FIDE-Nation mit neuer Funktion DeWIS::Nation -> Top-100 alle von 80,86 auf 4,07 sec (19,9 mal schneller), Top-100 wbl. von 43,74 auf 3,02 sec (14,5 mal schneller) beschleunigt

## Version 1.5.1 (2022-04-25)

* Fix: Falsche Verbandslinks in Plazierungsstatistik in der Spielerkarteikarte (5-stellig statt 3-stellig)
* Change: Template dewis_verband - Formular von div auf table umgebaut
* Fix: Abfrage der FIDE-Nation für Verbandslisten deutlich beschleunigt -> neue Funktion DeWIS::Nation, die zuerst tl_dwz_spi abfragt -> Top-100 Deutschland nun 378% schneller geladen: 6,24 sec statt 23,61 sec

## Version 1.5.0 (2022-04-21)

* Fix: Spielersuche nach O'Donnell nicht möglich -> ' wurde in &#39; umkodiert
* Change: Konstanten ALIAS_* überall durch Funktionsaufrufe ersetzt. Funktionen entsprechend angepaßt, das ggfs. nur das Alias geliefert wird.
* Add: Verbandslisten nach deutschen Spielern filtern

## Version 1.4.4 (2022-02-03)

* Change: Chart.js Update von 1.0.2 auf 3.7.0
* Fix: In Verbandslisten fehlt in der Überschrift das Wort "weiblich" (bei Parameter sex=f)
* Fix: Parameter sex in Verbandslisten sollte mit Option f laufen, aber w gibt es wohl auch (noch) -> Umstellung w auf f
* Fix: Schwalbe-Link funktioniert nicht als Vereinslink auf Spielerkarteien von Schwalbemitgliedern -> Weiche eingebaut auf Verband

## Version 1.4.3 (2021-12-02)

* Fix: DeWIS_Cleaner.php in ANSI umgewandelt. UltraEdit speichert UTF8 mit BOM.

## Version 1.4.2 (2021-12-02)

* Change: DeWIS_Download.php in ein Contao-Skript umgewandelt (mit system/initialize.php)
* Change: DeWIS_Cleaner.php in ein Contao-Skript umgewandelt (mit system/initialize.php)
* Add: DeWIS_Cleaner.php - Synchronisierung mit Dateiverwaltung damit die Download-Elemente Daten erhalten

## Version 1.4.1 (2021-12-01)

* Fix: DWZ-Bestenliste wurde nicht gecached
* Fix: Voreinstellung tl_module.dwz_gender war nicht richtig gesetzt

## Version 1.4.0 (2021-12-01)

* Fix: tl_module.space aus Paletten entfernt, da nicht mehr unterstützt
* Add: DWZ-Bestenliste als Frontend-Modul
* Add: DeWIS.php - Cachezeit kann separat im Parameter-Array übergeben werden

## Version 1.3.10 (2021-10-01)

* Fix: tl_dwz_spi - 1048 Column 'zpsver' cannot be null (in Helper/DeWIS.php)

## Version 1.3.9 (2021-10-01)

* Fix: Suche nach Vereinsname mit *** und +++ führt zu einem Fehler
* Add: Ausgabe des Suchbegriffes bei den Suchen
* Fix: Fehlerausgabe in den Templates direkt unter das Eingabefeld gesetzt
* Add: FIDE-Nation in Verbandslisten anzeigen
* Add: Abhängigkeit components/flag-icon-css (für Länderflaggen)
* Add: Helper-Funktion Laendercode

## Version 1.3.8 (2021-09-09)

* Fix: 1048 Column 'dwz' cannot be null (bei UPDATE tl_dwz_spi)
* Fix: dwzIndex ebenfalls entsprechend korrigiert

## Version 1.3.7 (2021-09-09)

* Fix: 1048 Column 'fideTitel' cannot be null (bei UPDATE tl_dwz_spi)
* Fix: fideNation ebenfalls entsprechend korrigiert

## Version 1.3.6 (2021-09-09)

* Fix: 1048 Column 'fideElo' cannot be null (bei UPDATE tl_dwz_spi)

## Version 1.3.5 (2021-09-09)

* Fix: 1048 Column 'fideID' cannot be null (bei UPDATE tl_dwz_spi)

## Version 1.3.4 (2021-07-08)

* Fix: Column 'zpsver' cannot be null (beim Import von Spielerdaten)

## Version 1.3.3 (2021-03-23)

* Fix: Helper/DeWIS.php - SOAPClient, Parameter 'stream_context' wieder aktiviert

## Version 1.3.2 (2021-02-26)

* Change: Wenn nicht angemeldet, in Vereinslisten Status und Mitgliedsnummer ausblenden (Wunsch AG Datenschutz)
* Change: Wertungsreferent ausblenden, wenn nicht angemeldet (Wunsch AG datenschutz: nur Adresse ausblenden)

## Version 1.3.1 (2021-01-13)

* Fix: DeWIS_Cleaner.php hat .public gelöscht

## Version 1.3.0 (2021-01-13)

* Add: Downloadskript von DWZ-Dateien DeWIS_Download.php
* Add: Aufräumskript für DWZ-Dateien DeWIS_Cleaner.php

## Version 1.2.0 (2020-11-17)

* JSON-Schnittstelle für Abfrage Spielerdaten aus tl_dwz_spi mittels FIDE-ID eingebaut

## Version 1.1.2 (2020-10-13)

* DIV-Container um Tabellen hinzugefügt mit Klasse table_responsiv
* CSS-Klasse table_responsiv erstellt, um Tabellen horizontal scrollbar zu machen

## Version 1.1.1 (2020-09-28)

* CSS-Fehler im Backend beseitigt

## Version 1.1.0 (2020-08-02)

* tl_dwz_kar.php, tl_dwz_spi.php alte Pfade zu assets ersetzt
* Alte Datenbank optional aktivierbar statt dauerhaft

## Version 1.0.4 (2020-06-24)

* Fix: Fehler in contao-dewis-bundle/src/Classes/Verband.php on line 211

## Version 1.0.3 (2020-06-24)

* Fix: Syntax-Fehler in contao-dewis-bundle/src/Classes/Verband.php on line 213

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
