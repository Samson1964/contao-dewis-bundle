# Abfrage der DeWIS-API

## Version 2.0.2 (2024-12-10)

* Fix: DWZ--Abfrage nicht möglich, wird ständig angezeigt

## Version 2.0.1 (2024-12-10)

* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite() cannot be called statically -> public static statt nur public
* Fix: Warning: Undefined array key "dwz_gender_options" in src/Resources/contao/dca/tl_module.php (line 122) -> &$ davorgesetzt statt nur $
* Fix: Warning: Undefined array key "dewis_switchedOff" in src/Classes/Verband.php (line 76) -> Prüfung mit isset
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::Blacklist() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\Helper::getMitglied() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\Helper::Navigation() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::Verbandsliste() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery() cannot be called statically -> public static statt nur public
* Fix: Warning: Undefined array key "cachetime" in src/Helper/DeWIS.php (line 86) -> Prüfung mit isset
* Fix: Warning: Undefined variable $result in src/Helper/DeWIS.php (line 90) -> Prüfung mit isset
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::Abfrage() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::AddWuerttemberg() cannot be called statically -> public static statt nur public
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::ModifiziereElo() cannot be called statically -> public static statt nur public
* Fix: Warning: Undefined array key "dewis_eloLocal" in src/Helper/DeWIS.php (line 940) -> Prüfung mit isset
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::AktualisiereDWZTabellen() cannot be called statically -> public static statt nur public
* Change: DeWIS.php/Helper.php -> alle public function durch public static function ersetzt
* Fix: Warning: Undefined global variable $DeWIS-Cache in src/Helper/DeWIS.php (line 106) -> Prüfung mit isset
* Fix: Warning: Undefined array key "nocache" in src/Helper/DeWIS.php (line 70) -> Prüfung mit isset
* Fix: Warning: Undefined global variable $DeWIS-Cache in src/Helper/DeWIS.php (line 115) -> Prüfung mit isset
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::org() cannot be called statically -> protected static statt nur protected
* Fix: Non-static method Schachbulle\ContaoDewisBundle\Helper\DeWIS::sub_org() cannot be called statically -> protected static statt nur protected
* Fix: Warning: Undefined array key "ZPS" in src/Helper/DeWIS.php (line 448) -> Prüfung mit isset
* Fix: Warning: Undefined array key "dewis-queries" in src/Helper/DeWIS.php (line 106) -> Prüfung mit isset
* Fix: Warning: Undefined array key 10029745 in src/Classes/Verband.php (line 226) -> Prüfung mit isset
* Fix: An exception occurred while executing a query: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'db107305_42.elo' doesn't exist -> Abfrage dewis_eloLocal fehlte
* Fix: Warning: Undefined array key "dewis-queriestimes" in src/Helper/DeWIS.php (line 108) -> Prüfung mit isset
* Fix: Warning: Undefined array key "dewis_switchedOff" in src/Classes/Verein.php (line 73) -> Prüfung mit isset
* Fix: Warning: Attempt to read property "homepage" on null in src/Classes/Verein.php (line 303) -> Prüfung mit isset
* Fix: Warning: Attempt to read property "info" on null in src/Classes/Verein.php (line 304) -> Prüfung mit isset
* Fix: Warning: Attempt to read property "addImage" on null in src/Classes/Verein.php (line 305) -> Prüfung mit isset
* Fix: Warning: Undefined array key 10131923 in src/Classes/Verein.php (line 354) -> Prüfung mit isset
* Fix: Warning: Undefined array key "dewis-queries" in src/Resources/contao/templates/queries.html5 (line 12) -> in DeWIS.php Variablenzuweisung ergänzt
* Fix: Warning: Undefined array key "dewis_switchedOff" in src/Classes/Spieler.php (line 78) -> Prüfung mit isset
* Fix: Warning: Undefined array key 1 in src/Helper/Helper.php (line 645) 
* Fix: Warning: Undefined array key 10856739 in src/Classes/Spieler.php (line 158) 
* Fix: und weitere Änderungen wegen PHP 8
* Unlösbar: Vereinslogos werden in Originalgröße angezeigt -> kann in Entwicklungsumgebung nicht nachvollzogen werden.

## Version 2.0.0 (2024-11-29)

* Change: Kompatibilität mit PHP 8 in composer.json
* Change: Helper::getSpielerseite() auf public static statt nur public

## Version 1.9.0 (2024-07-03)

* Fix: Klasse Verband liefert keine aktuellen Nationen -> kommen aus DeWIS statt korrekter Nation aus FIDE-Daten
* Add: verein.php, spieler.php und verband.php aus der bisher ausgelagerten API (nur zu Analysezwecken)
* Add: api.php im public-Ordner -> die neue API
* Change: Klasse DeWIS_Converter -> Anpassung SQL-Schema für readme.txt (bei PRIMARY KEY PID Mgl_Nr hinzugefügt)
* Change: Klasse DeWIS_Download -> im Zieldateinamen "_dewis-version" hinzugefügt, damit auch diese Dateien wieder auf dem DSB-Server gesichert werden können.

## Version 1.8.1 (2024-06-30)

* Change: Klasse DeWIS_Converter modifiziert, das Downloads gleich mit in das öff. Verzeichnis kopiert werden.

## Version 1.8.0 (2024-06-27)

* Add: Public-Klasse DeWIS_Converter zum Laden der CSV-Datei Deutschland und Konvertieren derselben

## Version 1.7.5 (2024-06-25)

* Change: DeWIS.getElo in getFIDE umbenannt, weil neben der Elo, auch der Titel und die Nation zurückgegeben werden.
* Change: DeWIS.ModifiziereElo lädt jetzt auch Titel und Nation
* Change: tl_settings.dewis_eloLocal -> gilt jetzt auch für FIDE-Titel und -Nation

## Version 1.7.4 (2024-05-13)

* Fix: PHP Warning strpos(): Empty needle in Verein.php on line 171
* Fix: PHP Warning strpos(): Empty needle in Verein.php on line 195
* Add: DeWIS_Download um Downloads im neuen Format (mit Spieler-ID) ergänzt

## Version 1.7.3 (2024-04-13)

* Fix: Invalid argument supplied for foreach() in Turnier.php on line 201
* Fix: count(): Parameter must be an array or an object that implements Countable in Turnier.php on line 231

## Version 1.7.2 (2024-03-18)

* Fix: Caching muß nach der Elo-Korrektur erfolgen

## Version 1.7.1 (2024-03-07)

* Change: DeWIS-Klasse -> württembergische Vereine korrigiert: C0220 SV statt SC, neu: C030D Schachklub Freibauer Esslingen, verschwunden in MIVIS ist: C0130 SC Bad Schussenried

## Version 1.7.0 (2024-03-06)

* Add: Fallback für den Fall, das Elo-Zahlen nicht von DeWIS genommen werden sollen
* Add: tl_settings.dewis_eloLocal -> Checkbox: Elo von lokaler Quelle (Tabelle elo) laden
* Add: Klasse DeWIS, Funktion ModifiziereElo -> Lädt die Elo aus der lokalen Quelle, wenn erwünscht

## Version 1.6.2 (2023-11-22)

* Change: tl_dwz_spi -> Operation editHeader nach links verschoben in Icon-Liste
* Add: Toggle-Button Haste in tl_dwz_spi und tl_dwz_ver
* Add: Abhängigkeit codefog/contao-haste
* Add: Spezialfilter Spieler mit/ohne Homepage
* Add: Filter Spieler mit/ohne Bild
* Add: Spezialfilter Vereine mit/ohne Kurzporträt, mit/ohne Homepage

## Version 1.6.1 (2023-10-27)

* Add: Ausgabe der Teilnehmeranzahl in der Turniersuche

## Version 1.6.0 (2023-05-13)

* Change: tl_dwz_spi -> bei Bild alle Extras entfernt: alt,size,imagemargin,imageUrl,fullsize,caption,floating -> kommt in globale Bildeinstellung
* Change: tl_dwz_ver -> bei Bild alle Extras entfernt: size,caption -> kommt in globale Bildeinstellung
* Add: tl_settings -> Felder für Standardbilder und Bildgrößen Spieler und Vereine
* Add: Model für Abfrage tl_dwz_spi
* Add: Model für Abfrage tl_dwz_ver
* Change: Template dewis_spieler -> mit Spielerbild
* Change: Template dewis_verein -> mit Vereinslogo

## Version 1.5.7 (2023-02-08)

* Fix: tl_member wird nicht übernommen -> PaletteManipulator eingebaut
* Add: Deutsch für tl_member
* Change: Vereinsstruktur Württemberg in DeWIS-Klasse: Leipheim, Schachritter Kirchheim/Teck, TSV Undingen, Schachclub Strateg Stuttgart, Post-SG Schwäbisch Hall, SF Pattonville
* Delete: Vereinsstruktur Württemberg in DeWIS-Klasse: Schwarz Weiß Münsingen, DJK Sportbund Stuttgart, Vardar Sindelfingen, Lachender Turm Schwäbisch Hall

## Version 1.5.6 (2022-12-01)

* Change: Link auf alte Karteikarte um Zugangsinformationen ergänzt

## Version 1.5.5 (2022-10-28)

* Fix: Prüfung $params['nocache'] in DeWIS.php an falscher Stelle eingebaut -> Cache-Klasse war noch nicht initialisiert (Fatal error: Uncaught Error: Call to a member function store() on null in Helper/DeWIS.php:102)

## Version 1.5.4 (2022-10-25)

* Change: Verbandssuche nach Deutschen berücksichtigt auch Leerstring oder "-" bei der FIDE-Nation
* Fix: Verein.php, Zeile 103 -> In preg_match-Funktion fehlte bei den erlaubten Zeichen das ß -> altlußheim wurde nicht gefunden
* Add: public/DeWIS_Check zum Loggen der Verbindungen zu DeWIS
* Add: DeWIS.php::autoQuery -> neuer Parameter nocache (true = Cache nicht berücksichtigen bei Abfrage)

## Version 1.5.3 (2022-05-16)

* Add: Option in den Einstellungen, um die DWZ-Abfragen generell abzuschalten

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
