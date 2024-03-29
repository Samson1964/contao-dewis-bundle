<?php
/*
 * =============================================================================
 * R�umt den Ordner files/dewis auf
 * - Verschieben der Dateien aus dem Hauptverzeichnis in die Unterordner
 * - L�schen aller Dateien mit 0 Byte
 * - L�schen aller doppelten Dateien (gleiche Gr��e wie die vorhergehende Datei)
 * =============================================================================
 */

/**
 * Contao Open Source CMS, Copyright (C) 2005-2020 Leo Feyer
 */

use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodewis/DeWIS_Cleaner.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php'); 

class DeWIS_Cleaner
{
	public function run()
	{
		$verzeichnis = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/'; // web-Ordner entfernen und Zielordner anh�ngen
		
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
						if($file != '.' && $file != '..' && $file != '.public')
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
						$dateien = self::LeseVerzeichnis($zielpfad);
						echo "Pr�fe $zielpfad<br>\n";
						//print_r($dateien);
						// Dateien pr�fen
						if($dateien)
						{
							$dateigroesse_alt = -1;
							foreach($dateien as $datei)
							{
								$dateigroesse = filesize($zielpfad.'/'.$datei);
								if($dateigroesse == 0)
								{
									// Datei l�schen
									echo "... $datei ($dateigroesse) leer - l�schen<br>\n";
									unlink($zielpfad.'/'.$datei);
								}
								elseif($dateigroesse == $dateigroesse_alt)
								{
									// Gleichgro�e Datei
									echo "... $datei ($dateigroesse) gleich gro� - l�schen<br>\n";
									unlink($zielpfad.'/'.$datei);
								}
								elseif($dateigroesse != $dateigroesse_alt)
								{
									// Unterschiedliche Datei
									$pfad = substr(str_replace(TL_ROOT, '', $zielpfad), 1);
									self::writeDbafs($pfad, $datei);
									echo "... $datei ($dateigroesse) ok<br>\n";
									$dateigroesse_alt = $dateigroesse;
								}
							}
						}
					}
				}
			}
		}
	}

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
						if($file != '.' && $file != '..' && $file != '.public')
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

	/**
	 * Generiert den Datensatz in tl_files
	 * (copied from FormFileUpload.php)
	 *   
	 * @param string $strUploadFolder  ohne Prefix TL_ROOT/, ohne Suffix /
	 * @param string $filename
	 */
	protected function writeDbafs($strUploadFolder, $filename)
	{
		// Generate the DB entries
		$strFile = $strUploadFolder . '/' . $filename;
		$objFile = \FilesModel::findByPath($strFile);
		
		// Existing file is being replaced (see contao/core#4818)
		if ($objFile !== null)
		{
			$objFile->tstamp = time();
			$objFile->path   = $strFile;
			$objFile->hash   = md5_file(TL_ROOT . '/' . $strFile);
			$objFile->save();
		}
		else
		{
			\Dbafs::addResource($strFile);
		}
		
		// Update the hash of the target folder
		\Dbafs::updateFolderHashes($strUploadFolder);
		
	}

}

/**
 * Instantiate controller
 */
$objSpielerdaten = new DeWIS_Cleaner();
$objSpielerdaten->run();
