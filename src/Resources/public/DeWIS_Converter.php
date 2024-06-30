<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2020 Leo Feyer
 */

use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodewis/DeWIS_Converter.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

class DeWIS_Converter
{
	protected $zielpfad;
	protected $packpfad;
	protected $archivpfad;
	protected $exportpfad;
	protected $spieler = array();
	protected $vereine = array();
	protected $verbaende = array();
	protected $suchen = array();
	protected $ersetzen = array();
	protected $archive = array();

	/**
	 * Klasse initialisieren
	 */
	public function __construct()
	{
		$this->zielpfad = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/tmp/'; // web-Ordner entfernen und Zielordner anhängen
		if(!file_exists($this->zielpfad)) mkdir($this->zielpfad, 0777);
		$this->packpfad = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/raw/'; // web-Ordner entfernen und Zielordner anhängen
		if(!file_exists($this->packpfad)) mkdir($this->packpfad, 0777);
		$this->archivpfad = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/'.date('Y').'/'; // web-Ordner entfernen und Zielordner anhängen
		if(!file_exists($this->archivpfad)) mkdir($this->archivpfad, 0777);
		$this->exportpfad = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/export/'; // web-Ordner entfernen und Zielordner anhängen
		if(!file_exists($this->exportpfad)) mkdir($this->exportpfad, 0777);
		$this->suchen = array('Ü', 'Ö', 'Ä', 'ü', 'ö', 'ä', 'ß', 'ú', 'ó', 'á', 'é', 'à', 'ò');
		$this->ersetzen = array('Ue', 'Oe', 'Ae', 'ue', 'oe', 'ae', 'ss', 'u', 'o', 'a', 'e', 'a', 'o');
		
		// Zielpfade, Dateinamen und Optionen festlegen
		$this->archive = array
		(
			array('verband' => ''),
			array('verband' => '1'),
			array('verband' => '2'),
			array('verband' => '3'),
			array('verband' => '4'),
			array('verband' => '5'),
			array('verband' => '6'),
			array('verband' => '7'),
			array('verband' => '8'),
			array('verband' => '9'),
			array('verband' => 'A'),
			array('verband' => 'B'),
			array('verband' => 'C'),
			array('verband' => 'D'),
			array('verband' => 'E'),
			array('verband' => 'F'),
			array('verband' => 'G'),
			array('verband' => 'H'),
			array('verband' => 'L'),
			array('verband' => 'M')
		);
	}

	public function run()
	{
		// Download der CSV-Datei Deutschland vom SVW-Server
		$url = 'https://dwz.svw.info/services/files/export/csv/LV-0-csv_v2.zip';
		$datum = date('Ymd');

		$link_array = pathinfo($url);
		$dateiname = $link_array['filename'];
		$suffix = $link_array['extension'];

		// Datei laden
		echo "Lade $url<br>\n";
		$ch = curl_init($url);
		$zieldatei = $this->zielpfad.$dateiname.'_'.$datum.'.'.$suffix;
		$fp = fopen($zieldatei, 'w');
		echo "Schreibe $zieldatei<br>\n";
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_exec($ch);
		fclose($fp);
		echo "Entpacke $zieldatei<br>\n";

		$zip = new ZipArchive;
		$res = $zip->open($zieldatei);
		if($res === TRUE)
		{
			$zip->extractTo($this->zielpfad); // wohin soll es entpackt werden
			$zip->close();
			echo "OK<br>\n";

			// CSV-Dateien einlesen
			$this->spieler = self::readCSV('spieler.csv');
			$this->verbaende = self::readCSV('verbaende.csv');
			$this->vereine = self::readCSV('vereine.csv');

			// Spieler bearbeiten
			self::modifySpieler();

			// Dateien schreiben und packen
			foreach($this->archive as $archiv)
			{
				self::Packer($archiv['verband']);
			}

			// Verzeichnisse aufräumen
			unlink($this->zielpfad.'spieler.csv');
			unlink($this->zielpfad.'vereine.csv');
			unlink($this->zielpfad.'verbaende.csv');
			unlink($this->zielpfad.'readme.txt');
			unlink($this->packpfad.'spieler.csv');
			unlink($this->packpfad.'vereine.csv');
			unlink($this->packpfad.'verbaende.csv');
			unlink($this->packpfad.'readme.txt');
			unlink($this->packpfad.'spieler.sql');
			unlink($this->packpfad.'vereine.sql');
			unlink($this->packpfad.'verbaende.sql');
			
			// Kopien der Dateien im Exportverzeichnis anlegen
			$csvpfad = $this->exportpfad.'csv/';
			$sqlpfad = $this->exportpfad.'sql/';
			if(!file_exists($csvpfad)) mkdir($csvpfad, 0777);
			if(!file_exists($sqlpfad)) mkdir($sqlpfad, 0777);
			foreach($this->archive as $item)
			{
				// Pfade anlegen
				if($item['verband'])
				{
					// Mitgliedsverband
					$quelle = $this->archivpfad.'lv'.strtolower($item['verband']).'/LV-'.$item['verband'].'-csv_'.date('Ymd').'.zip';
					$ziel = $csvpfad.'LV-'.$item['verband'].'-csv.zip';
					echo "Kopiere $quelle => $ziel<br>";
					copy($quelle, $ziel);
					$quelle = $this->archivpfad.'lv'.strtolower($item['verband']).'/LV-'.$item['verband'].'-sql_'.date('Ymd').'.zip';
					$ziel = $sqlpfad.'LV-'.$item['verband'].'-sql.zip';
					echo "Kopiere $quelle => $ziel<br>";
					copy($quelle, $ziel);
				}
				else
				{
					// $verband ist leer, also DSB
					$quelle = $this->archivpfad.'csv/LV-0-csv_'.date('Ymd').'.zip';
					$ziel = $csvpfad.'LV-0-csv.zip';
					echo "Kopiere $quelle => $ziel<br>";
					copy($quelle, $ziel);
					$quelle = $this->archivpfad.'sql/LV-0-sql_'.date('Ymd').'.zip';
					$ziel = $sqlpfad.'LV-0-sql.zip';
					echo "Kopiere $quelle => $ziel<br>";
					copy($quelle, $ziel);
				}
			}
			
		}
		else
		{
			echo 'Fehler<br>\n';
		}
	}

	public function Packer($verband)
	{
		// Pfade anlegen
		if($verband)
		{
			// Mitgliedsverband
			$csvpfad = $this->archivpfad.'lv'.strtolower($verband).'/';
			$sqlpfad = $this->archivpfad.'lv'.strtolower($verband).'/';
			if(!file_exists($csvpfad)) mkdir($csvpfad, 0777);
			
		}
		else
		{
			// $verband ist leer, also DSB
			$csvpfad = $this->archivpfad.'csv/';
			$sqlpfad = $this->archivpfad.'sql/';
			if(!file_exists($csvpfad)) mkdir($csvpfad, 0777);
			if(!file_exists($sqlpfad)) mkdir($sqlpfad, 0777);
		}
	
		// spieler.csv/spieler.sql schreiben
		$fp_csv = fopen($this->packpfad.'spieler.csv', 'w');
		$fp_sql = fopen($this->packpfad.'spieler.sql', 'w');
		$zeile = 0; $anzahl = 0;
		foreach($this->spieler as $item)
		{
			$zeile++;
			if($zeile == 1) 
			{
				fputcsv($fp_csv,$item); // 1. Zeile immer, nur nicht bei SQL
			}
			elseif($verband && $verband == substr($item[1], 0, 1))
			{
				fputcsv($fp_csv,$item); // Nur diesen Verband speichern
				self::SQLPacker($fp_sql, 'dwz_spieler', $item);
				$anzahl++;
			}
			elseif($verband == '') 
			{
				fputcsv($fp_csv,$item); // DSB immer alle Datensätze
				self::SQLPacker($fp_sql, 'dwz_spieler', $item);
			}
		}
		fclose($fp_csv);
		fclose($fp_sql);
		if($verband) $spieleranzahl = $anzahl;
		else $spieleranzahl = $zeile - 1;

		// vereine.csv/vereine.sql schreiben
		$fp_csv = fopen($this->packpfad.'vereine.csv', 'w');
		$fp_sql = fopen($this->packpfad.'vereine.sql', 'w');
		$zeile = 0; $anzahl = 0;
		foreach($this->vereine as $item)
		{
			$zeile++;
			if($zeile == 1) 
			{
				fputcsv($fp_csv,$item); // 1. Zeile immer, nur nicht bei SQL
			}
			elseif($verband && $verband == substr($item[1], 0, 1))
			{
				fputcsv($fp_csv,$item); // Nur diesen Verband speichern
				self::SQLPacker($fp_sql, 'dwz_vereine', $item);
				$anzahl++;
			}
			elseif($verband == '') 
			{
				fputcsv($fp_csv,$item); // DSB immer alle Datensätze
				self::SQLPacker($fp_sql, 'dwz_vereine', $item);
			}
		}
		fclose($fp_csv);
		fclose($fp_sql);
		if($verband) $vereinsanzahl = $anzahl;
		else $vereinsanzahl = $zeile - 1;

		// verbaende.csv/verbaende.sql schreiben
		$fp_csv = fopen($this->packpfad.'verbaende.csv', 'w');
		$fp_sql = fopen($this->packpfad.'verbaende.sql', 'w');
		$zeile = 0; $anzahl = 0;
		foreach($this->verbaende as $item)
		{
			$zeile++;
			if($zeile == 1) 
			{
				fputcsv($fp_csv,$item); // 1. Zeile immer, nur nicht bei SQL
			}
			elseif($verband && $verband == substr($item[1], 0, 1))
			{
				fputcsv($fp_csv,$item); // Nur diesen Verband speichern
				self::SQLPacker($fp_sql, 'dwz_verbaende', $item);
				$anzahl++;
			}
			elseif($verband == '') 
			{
				fputcsv($fp_csv,$item); // DSB immer alle Datensätze
				self::SQLPacker($fp_sql, 'dwz_verbaende', $item);
			}
		}
		fclose($fp_csv);
		fclose($fp_sql);

		// readme.txt anlegen
		self::Readme($verband, $vereinsanzahl, $spieleranzahl, 'csv');

		// CSV-Dateien packen
		$files = array
		(
			$this->packpfad.'spieler.csv',
			$this->packpfad.'vereine.csv',
			$this->packpfad.'verbaende.csv',
			$this->packpfad.'readme.txt'
		);
		$zip = new ZipArchive;

		if($verband) $ziel = $csvpfad.'LV-'.$verband.'-csv_'.date('Ymd').'.zip';
		else $ziel = $csvpfad.'LV-0-csv_'.date('Ymd').'.zip';

		if($zip->open($ziel,ZipArchive::CREATE))
		{
			foreach($files as $file)
			{
				$zip->addFile(realpath($file), str_replace($this->packpfad, '', $file));
			}
			$zip->close();
		}

		// readme.txt anlegen
		self::Readme($verband, $vereinsanzahl, $spieleranzahl, 'sql');

		// SQL-Dateien packen
		$files = array
		(
			$this->packpfad.'spieler.sql',
			$this->packpfad.'vereine.sql',
			$this->packpfad.'verbaende.sql',
			$this->packpfad.'readme.txt'
		);
		$zip = new ZipArchive;

		if($verband) $ziel = $sqlpfad.'LV-'.$verband.'-sql_'.date('Ymd').'.zip';
		else $ziel = $sqlpfad.'LV-0-sql_'.date('Ymd').'.zip';

		if($zip->open($ziel,ZipArchive::CREATE))
		{
			foreach($files as $file)
			{
				$zip->addFile(realpath($file), str_replace($this->packpfad, '', $file));
			}
			$zip->close();
		}

	}

	public function SQLPacker($fp, $tabelle, $daten)
	{
		fputs($fp, 'REPLACE INTO `'.$tabelle.'` VALUES (');
		for($x = 0; $x < count($daten); $x++)
		{
			if($x == count($daten) - 1) fputs($fp, "'" . addslashes($daten[$x]) . "'"); 
			else fputs($fp, "'" . addslashes($daten[$x]) . "', "); 
		}
		fputs($fp, ');'."\r\n");
	}

	public function readCSV($datei)
	{
		$arr = array();
		if(($fp = fopen($this->zielpfad.$datei, "r")) !== FALSE)
		{
			while(($data = fgetcsv($fp, 1000, ',')) !== FALSE)
			{
				$arr[] = $data;
			}
			fclose($fp);
		}
		return $arr;
	}

	public function modifySpieler()
	{
		// Logdatei anlegen
		$fp = fopen($this->packpfad.'log_'.date('Ymd').'.txt', 'w');
		// Spieler-Array anhand Datenbanktabelle elo modifizieren, Abweichungen bei Name und Geschlecht loggen
		for($x = 1; $x < count($this->spieler); $x++)
		{
			$objPlayer = \Database::getInstance()->prepare("SELECT * FROM elo WHERE fideid = ?")
			                                     ->execute($this->spieler[$x][13]);
			$ungleich = array();
			if($objPlayer->numRows)
			{
				$differenzen = self::compareSpieler($x, $this->spieler[$x], $objPlayer);
				if(count($differenzen))
				{
					fputs($fp, $this->spieler[$x][4]."\n");
					foreach($differenzen as $item)
					{
						fputs($fp, '- '.$item."\n");
					}
					fputs($fp, "\n");
				}
			}
		}
		fclose($fp);
	}

	/**
	 * Funktion compareSpieler
	 * Spielerdaten von DeWIS und FIDE miteinander vergleichen und loggen
	 */
	public function compareSpieler($index, $spieler, $objPlayer)
	{
		$ungleich = array();

		// ==========================
		// Name vergleichen
		// ==========================
		$dsbname = str_replace($this->suchen, $this->ersetzen, utf8_encode($spieler[4]));
		$fidename = $objPlayer->surname.','.$objPlayer->prename;
		// Komma am Ende entfernen
		if(substr($fidename, -1) == ',') $fidename = substr($fidename, 0, -1);
		if($dsbname != $fidename)
		{
			$ungleich[] = 'Name: '.$dsbname.' => '.$fidename;
		}

		// ==========================
		// Geschlecht vergleichen
		// ==========================
		if($objPlayer->sex == 'F') $objPlayer->sex = 'W';
		if($spieler[5] != $objPlayer->sex)
		{
			$ungleich[] = 'Geschlecht: '.$spieler[5].' => '.$objPlayer->sex;
		}

		// ==========================
		// Geburtsjahr vergleichen
		// ==========================
		if($spieler[7] != $objPlayer->birthday)
		{
			$ungleich[] = 'Geburtsjahr: '.$spieler[7].' => '.$objPlayer->birthday;
		}

		// ==========================
		// Elo vergleichen und ggfs. ändern
		// ==========================
		if($spieler[11] != $objPlayer->rating)
		{
			$ungleich[] = 'FIDE-Elo: '.$spieler[11].' => '.$objPlayer->rating;
			$this->spieler[$index][11] = $objPlayer->rating;
		}

		// ==========================
		// Titel vergleichen und ggfs. ändern
		// ==========================
		if($spieler[12] != $objPlayer->title)
		{
			$ungleich[] = 'FIDE-Titel: '.$spieler[12].' => '.$objPlayer->title;
			$this->spieler[$index][12] = $objPlayer->title;
		}

		// ==========================
		// Land vergleichen und ggfs. ändern
		// ==========================
		if($spieler[14] != $objPlayer->country)
		{
			$ungleich[] = 'FIDE-Land: '.$spieler[14].' => '.$objPlayer->country;
			$this->spieler[$index][14] = $objPlayer->country;
		}

		return $ungleich;
	}

	public function Readme($verband, $vereine, $spieler, $typ)
	{
		switch($verband)
		{
			case '1': $verbandsname = '10000 - Badischer Schachverband'; break;
			case '2': $verbandsname = '20000 - Bayerischer Schachbund'; break;
			case '3': $verbandsname = '30000 - Berliner Schachverband'; break;
			case '4': $verbandsname = '40000 - Hamburger Schachverband'; break;
			case '5': $verbandsname = '50000 - Hessischer Schachverband'; break;
			case '6': $verbandsname = '60000 - Schachbund Nordrhein-Westfalen'; break;
			case '7': $verbandsname = '70000 - Niedersächsischer Schachverband'; break;
			case '8': $verbandsname = '80000 - Schachbund Rheinland-Pfalz'; break;
			case '9': $verbandsname = '90000 - Saarländischer Schachverband'; break;
			case 'A': $verbandsname = 'A0000 - Schachverband Schleswig-Holstein'; break;
			case 'B': $verbandsname = 'B0000 - Landesschachbund Bremen'; break;
			case 'C': $verbandsname = 'C0000 - Schachverband Württemberg'; break;
			case 'D': $verbandsname = 'D0000 - Landesschachbund Brandenburg'; break;
			case 'E': $verbandsname = 'E0000 - Landesschachverband Mecklenburg-Vorpommern'; break;
			case 'F': $verbandsname = 'F0000 - Schachverband Sachsen'; break;
			case 'G': $verbandsname = 'G0000 - Schachverband Sachsen-Anhalt'; break;
			case 'H': $verbandsname = 'H0000 - Thüringer Schachbund'; break;
			case 'L': $verbandsname = 'L0000 - Deutscher Blinden- und Sehbehinderten-Schachbund'; break;
			case 'M': $verbandsname = 'M0000 - Schwalbe, deutsche Vereinigung für Problemschach'; break;
			default: $verbandsname = '00000 - Deutscher Schachbund'; break;
		}
		
		$content .= 'Landesverband: '.$verbandsname."\r\n";
		$content .= ''."\r\n";
		$content .= 'DWZ-Datenbank vom '.date('d.m.Y').' - '.$spieler.' Spieler in '.$vereine.' Vereinen'."\r\n";
		$content .= ''."\r\n";
		$content .= '==========================================================================='."\r\n";
		$content .= 'Eine Veröffentlichung dieser Daten ist nur nach vorheriger Abspache mit dem'."\r\n";
		$content .= 'zuständigen DWZ-Referenten erlaubt!'."\r\n";
		$content .= '==========================================================================='."\r\n";
		$content .= ''."\r\n";
		// Inhalt für CSV-Daten
		if($typ == 'csv')
		{
			$content .= 'Dateistruktur: (ANSI-Dateien, die Felder sind durch "," getrennt.)'."\r\n";
			$content .= ''."\r\n";
			$content .= 'spieler.csv - sortiert nach DWZ'."\r\n";
			$content .= '-----------'."\r\n";
			$content .= '- ID des Mitglieds'."\r\n";
			$content .= '- ZPS-Nummer des Vereins'."\r\n";
			$content .= '- Mitgliedsnummer im Verein'."\r\n";
			$content .= '- Status der Mitgliedschaft'."\r\n";
			$content .= '    A - Aktiv'."\r\n";
			$content .= '    P - Passiv'."\r\n";
			$content .= '- Name,Vorname'."\r\n";
			$content .= '- Geschlecht'."\r\n";
			$content .= '    M - Männlich'."\r\n";
			$content .= '    W - Weiblich'."\r\n";
			$content .= '- Spielberechtigung'."\r\n";
			$content .= '    D - Deutscher'."\r\n";
			$content .= '    G - Gleichgestellt'."\r\n";
			$content .= '    E - EU-Ausländer'."\r\n";
			$content .= '    A - Ausländer'."\r\n";
			$content .= '    S - Sperre'."\r\n";
			$content .= '- Geburtsjahr'."\r\n";
			$content .= '- Woche der letzten Turnierauswertung (JJJJWW)'."\r\n";
			$content .= '- DWZ'."\r\n";
			$content .= '- Index'."\r\n";
			$content .= '- FIDE-Elozahl'."\r\n";
			$content .= '- FIDE-Titel'."\r\n";
			$content .= '    CM - Candidate Master          WCM - Woman Candidate Master'."\r\n";
			$content .= '    FM - FIDE-Master               WFM - Woman FIDE-Master'."\r\n";
			$content .= '    IM - International Master      WIM - Woman International Master'."\r\n";
			$content .= '    GM - Grandmaster               WGM - Woman Grandmaster'."\r\n";
			$content .= '- FIDE-ID'."\r\n";
			$content .= '- FIDE-Land'."\r\n";
			$content .= ''."\r\n";
			$content .= 'vereine.csv - sortiert nach ZPS-Nummer'."\r\n";
			$content .= '-----------'."\r\n";
			$content .= '- ZPS-Nummer des Vereins'."\r\n";
			$content .= '- Landesverband'."\r\n";
			$content .= '- Übergeordneter Verband'."\r\n";
			$content .= '- Vereinsname'."\r\n";
			$content .= ''."\r\n";
			$content .= 'verband.csv - sortiert nach Verbandnummer'."\r\n";
			$content .= '-----------'."\r\n";
			$content .= '- Verbandnummer'."\r\n";
			$content .= '- Landesverband'."\r\n";
			$content .= '- Übergeordneter Verband'."\r\n";
			$content .= '- Verbandname'."\r\n";
		}
		elseif($typ == 'sql')
		{
			$content .= 'Tabellenstruktur für mySQL:'."\r\n";
			$content .= ''."\r\n";
			$content .= 'CREATE TABLE `dwz_verbaende` ('."\r\n";
			$content .= '  `Verband`            char(3)      NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  `LV`                 char(1)      NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  `Uebergeordnet`      char(3)      NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  `Verbandname`        varchar(60)  NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  PRIMARY KEY (`Verband`)'."\r\n";
			$content .= ') ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;'."\r\n";
			$content .= ''."\r\n";
			$content .= '# --------------------------------------------------------'."\r\n";
			$content .= ''."\r\n";
			$content .= 'CREATE TABLE `dwz_vereine` ('."\r\n";
			$content .= '  `ZPS`                varchar(5)   NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  `LV`                 char(1)      NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  `Verband`            char(3)      NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  `Vereinname`         varchar(60)  NOT NULL DEFAULT \'\','."\r\n";
			$content .= '  PRIMARY KEY (`ZPS`)'."\r\n";
			$content .= ') ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;'."\r\n";
			$content .= ''."\r\n";
			$content .= '# --------------------------------------------------------'."\r\n";
			$content .= ''."\r\n";
			$content .= 'CREATE TABLE `dwz_spieler` ('."\r\n";
			$content .= '  `PID`                int(10) unsigned NULL DEFAULT NULL,'."\r\n";
			$content .= '  `ZPS`                varchar(5)       NULL DEFAULT NULL,'."\r\n";
			$content .= '  `Mgl_Nr`             smallint(4)      NULL DEFAULT NULL,'."\r\n";
			$content .= '  `Status`             char(1)          NULL DEFAULT NULL,'."\r\n";
			$content .= '  `Spielername`        varchar(40)      NULL DEFAULT NULL,'."\r\n";
			$content .= '  `Geschlecht`         char(1)               DEFAULT NULL,'."\r\n";
			$content .= '  `Spielberechtigung`  char(1)          NULL DEFAULT NULL,'."\r\n";
			$content .= '  `Geburtsjahr`        year(4)          NULL DEFAULT NULL,'."\r\n";
			$content .= '  `Letzte_Auswertung`  mediumint(6) unsigned DEFAULT NULL,'."\r\n";
			$content .= '  `DWZ`                smallint(4)  unsigned DEFAULT NULL,'."\r\n";
			$content .= '  `DWZ_Index`          smallint(3)  unsigned DEFAULT NULL,'."\r\n";
			$content .= '  `FIDE_Elo`           smallint(4)  unsigned DEFAULT NULL,'."\r\n";
			$content .= '  `FIDE_Titel`         char(2)               DEFAULT NULL,'."\r\n";
			$content .= '  `FIDE_ID`            int(8)       unsigned DEFAULT NULL,'."\r\n";
			$content .= '  `FIDE_Land`          char(3)               DEFAULT NULL,'."\r\n";
			$content .= '  PRIMARY KEY `PID` (`PID`, `ZPS`),'."\r\n";
			$content .= '  KEY `ZPS` (`ZPS`, `Mgl_Nr`),'."\r\n";
			$content .= '  KEY `FIDE_ID` (`FIDE_ID`),'."\r\n";
			$content .= '  KEY `Spielername` (`Spielername`),'."\r\n";
			$content .= '  KEY `DWZ` (`DWZ`, `DWZ_Index`)'."\r\n";
			$content .= ') ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_german2_ci;'."\r\n";
		}
		file_put_contents($this->packpfad.'readme.txt', utf8_decode($content));
	}
}

/**
 * Instantiate controller
 */
$objSpielerdaten = new DeWIS_Converter();
$objSpielerdaten->run();

//Array
//(
//    [0] => Array
//        (
//            [0] => ID
//            [1] => VKZ
//            [2] => Mgl-Nr
//            [3] => Status
//            [4] => Spielername
//            [5] => Geschlecht
//            [6] => Spielberechtigung
//            [7] => Geburtsjahr
//            [8] => Letzte-Auswertung
//            [9] => DWZ
//            [10] => Index
//            [11] => FIDE-Elo
//            [12] => FIDE-Titel
//            [13] => FIDE-ID
//            [14] => FIDE-Land
//        )
//
//    [1] => Array
//        (
//            [0] => 10029745
//            [1] => 10614
//            [2] => 334
//            [3] => A
//            [4] => Carlsen,Magnus
//            [5] => M
//            [6] => E
//            [7] => 1990
//            [8] => 202305
//            [9] => 2843
//            [10] => 103
//            [11] => 2830
//            [12] => GM
//            [13] => 1503014
//            [14] => NOR
//        )
