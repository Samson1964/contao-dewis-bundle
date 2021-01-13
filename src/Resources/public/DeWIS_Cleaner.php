<?php
/* 
 * =============================================================================
 * Räumt den Ordner files/dewis auf
 * - Verschieben der Dateien aus dem Hauptverzeichnis in die Unterordner
 * - Löschen aller Dateien mit 0 Byte
 * - Löschen aller doppelten Dateien (gleiche Größe wie die vorhergehende Datei)
 * =============================================================================
 */

ini_set("display_errors", true);
error_reporting(E_ALL); // Display everything except E_USER_NOTICE

$verzeichnis = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/'; // web-Ordner entfernen und Zielordner anhängen 

echo "Verschieben der Dateien aus dem Hauptordner<br>\n";

// Dateien aus Hauptverzeichnis verschieben
if(is_dir($verzeichnis))
{
	// Hauptverzeichnis einlesen
	if($handle = opendir($verzeichnis))
	{
		// einlesen der Verzeichnisses
		while(($file = readdir($handle)) !== false)
		{
			if(!is_dir($verzeichnis.$file))
			{
				// Jahr aus dem Dateinamen extrahieren
				$jahr = substr($file, -12, -8);

				// Zielordner festlegen
				if(substr($file, 0, 7) == 'dsb-ws8') $zielordner = 'swiss8';
				elseif(substr($file, 0, 7) == 'dsb-ws7') $zielordner = 'swiss';
				elseif(substr($file, 0, 8) == 'LV-0-csv') $zielordner = 'csv';
				elseif(substr($file, 0, 8) == 'LV-0-dos') $zielordner = 'dos';
				elseif(substr($file, 0, 8) == 'LV-0-sql') $zielordner = 'sql';
				else $zielordner = 'lv'.strtolower(substr($file, 3, 1));

				// Ordner ggfs. anlegen
				if(!file_exists($verzeichnis.$jahr)) mkdir($verzeichnis.$jahr, 0777);
				if(!file_exists($verzeichnis.$jahr.'/'.$zielordner)) mkdir($verzeichnis.$jahr.'/'.$zielordner, 0777);

				// Datei verschieben
				$status = rename($verzeichnis.$file, $verzeichnis.$jahr.'/'.$zielordner.'/'.$file);

			}
		}
		closedir($handle);
	}
}

$ordner = array('swiss', 'swiss8', 'csv', 'dos', 'sql', 'lv1', 'lv2', 'lv3', 'lv4', 'lv5', 'lv6', 'lv7', 'lv8', 'lv9', 'lva', 'lvb', 'lvc', 'lvd', 'lve', 'lvf', 'lvg', 'lvh', 'lvl', 'lvm');
// Verzeichnisse nach Duplikaten und 0-Byte-Dateien absuchen
// mit aktuellem Jahr beginnen
for($jahr = date('Y'); $jahr >= 2015; $jahr--)
{
	foreach($ordner as $item)
	{
		if(file_exists($verzeichnis.$jahr))
		{
			$zielpfad = $verzeichnis.$jahr.'/'.$item;
			if(file_exists($verzeichnis.$jahr.'/'.$item))
			{
				$dateien = LeseVerzeichnis($zielpfad);
				echo "Prüfe $zielpfad<br>\n";
				//print_r($dateien);
				// Dateien prüfen
				if($dateien)
				{
					$dateigroesse_alt = -1;
					foreach($dateien as $datei)
					{
						$dateigroesse = filesize($zielpfad.'/'.$datei);
						if($dateigroesse == 0)
						{
							// Datei löschen
							echo "... $datei ($dateigroesse) leer - löschen<br>\n";
							unlink($zielpfad.'/'.$datei);
						}
						elseif($dateigroesse == $dateigroesse_alt)
						{
							// Gleichgroße Datei
							echo "... $datei ($dateigroesse) gleich groß - löschen<br>\n";
							unlink($zielpfad.'/'.$datei);
						}
						elseif($dateigroesse != $dateigroesse_alt)
						{
							// Unterschiedliche Datei
							echo "... $datei ($dateigroesse) ok<br>\n";
							$dateigroesse_alt = $dateigroesse;
						}
					}
				}
			}
		}
	}
}


// ===================================================================================
// ===================================================================================
// ===================================================================================
// ===================================================================================

function LeseVerzeichnis($ordner)
{
	$dateien = array();
	if(is_dir($ordner))
	{
		// Hauptverzeichnis einlesen
		if($handle = opendir($ordner))
		{
			// einlesen der Verzeichnisses
			while(($file = readdir($handle)) !== false)
			{
				if(!is_dir($ordner.$file))
				{
					if($file != '.' && $file != '..')
					{
						$dateien[] = $file;
					}
				}
			}
			closedir($handle);
		}
	}
	sort($dateien);
	return $dateien;
}
