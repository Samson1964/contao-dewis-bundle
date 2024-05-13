<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2020 Leo Feyer
 */

use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodewis/DeWIS_Download.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php'); 

class DeWIS_Download
{
	public function run()
	{
		// Downloads aller DWZ-Dateien vom SVW-Server
		$links = array
		(
			'swisschess/dsb-ws833.zip',
			'swisschess/dsb-ws822.zip',
			'swisschess/dsb-ws7.zip',
			'export/csv/LV-0-csv.zip', 'export/dos/LV-0-dos.zip', 'export/sql/LV-0-sql.zip',
			'export/csv/LV-1-csv.zip', 'export/dos/LV-1-dos.zip', 'export/sql/LV-1-sql.zip',
			'export/csv/LV-2-csv.zip', 'export/dos/LV-2-dos.zip', 'export/sql/LV-2-sql.zip',
			'export/csv/LV-3-csv.zip', 'export/dos/LV-3-dos.zip', 'export/sql/LV-3-sql.zip',
			'export/csv/LV-4-csv.zip', 'export/dos/LV-4-dos.zip', 'export/sql/LV-4-sql.zip',
			'export/csv/LV-5-csv.zip', 'export/dos/LV-5-dos.zip', 'export/sql/LV-5-sql.zip',
			'export/csv/LV-6-csv.zip', 'export/dos/LV-6-dos.zip', 'export/sql/LV-6-sql.zip',
			'export/csv/LV-7-csv.zip', 'export/dos/LV-7-dos.zip', 'export/sql/LV-7-sql.zip',
			'export/csv/LV-8-csv.zip', 'export/dos/LV-8-dos.zip', 'export/sql/LV-8-sql.zip',
			'export/csv/LV-9-csv.zip', 'export/dos/LV-9-dos.zip', 'export/sql/LV-9-sql.zip',
			'export/csv/LV-A-csv.zip', 'export/dos/LV-A-dos.zip', 'export/sql/LV-A-sql.zip',
			'export/csv/LV-B-csv.zip', 'export/dos/LV-B-dos.zip', 'export/sql/LV-B-sql.zip',
			'export/csv/LV-C-csv.zip', 'export/dos/LV-C-dos.zip', 'export/sql/LV-C-sql.zip',
			'export/csv/LV-D-csv.zip', 'export/dos/LV-D-dos.zip', 'export/sql/LV-D-sql.zip',
			'export/csv/LV-E-csv.zip', 'export/dos/LV-E-dos.zip', 'export/sql/LV-E-sql.zip',
			'export/csv/LV-F-csv.zip', 'export/dos/LV-F-dos.zip', 'export/sql/LV-F-sql.zip',
			'export/csv/LV-G-csv.zip', 'export/dos/LV-G-dos.zip', 'export/sql/LV-G-sql.zip',
			'export/csv/LV-H-csv.zip', 'export/dos/LV-H-dos.zip', 'export/sql/LV-H-sql.zip',
			'export/csv/LV-L-csv.zip', 'export/dos/LV-L-dos.zip', 'export/sql/LV-L-sql.zip',
			'export/csv/LV-M-csv.zip', 'export/dos/LV-M-dos.zip', 'export/sql/LV-M-sql.zip',
			'export/csv/LV-0-csv_v2.zip', 'export/dos/LV-0-dos_v2.zip', 'export/sql/LV-0-sql_v2.zip',
			'export/csv/LV-1-csv_v2.zip', 'export/dos/LV-1-dos_v2.zip', 'export/sql/LV-1-sql_v2.zip',
			'export/csv/LV-2-csv_v2.zip', 'export/dos/LV-2-dos_v2.zip', 'export/sql/LV-2-sql_v2.zip',
			'export/csv/LV-3-csv_v2.zip', 'export/dos/LV-3-dos_v2.zip', 'export/sql/LV-3-sql_v2.zip',
			'export/csv/LV-4-csv_v2.zip', 'export/dos/LV-4-dos_v2.zip', 'export/sql/LV-4-sql_v2.zip',
			'export/csv/LV-5-csv_v2.zip', 'export/dos/LV-5-dos_v2.zip', 'export/sql/LV-5-sql_v2.zip',
			'export/csv/LV-6-csv_v2.zip', 'export/dos/LV-6-dos_v2.zip', 'export/sql/LV-6-sql_v2.zip',
			'export/csv/LV-7-csv_v2.zip', 'export/dos/LV-7-dos_v2.zip', 'export/sql/LV-7-sql_v2.zip',
			'export/csv/LV-8-csv_v2.zip', 'export/dos/LV-8-dos_v2.zip', 'export/sql/LV-8-sql_v2.zip',
			'export/csv/LV-9-csv_v2.zip', 'export/dos/LV-9-dos_v2.zip', 'export/sql/LV-9-sql_v2.zip',
			'export/csv/LV-A-csv_v2.zip', 'export/dos/LV-A-dos_v2.zip', 'export/sql/LV-A-sql_v2.zip',
			'export/csv/LV-B-csv_v2.zip', 'export/dos/LV-B-dos_v2.zip', 'export/sql/LV-B-sql_v2.zip',
			'export/csv/LV-C-csv_v2.zip', 'export/dos/LV-C-dos_v2.zip', 'export/sql/LV-C-sql_v2.zip',
			'export/csv/LV-D-csv_v2.zip', 'export/dos/LV-D-dos_v2.zip', 'export/sql/LV-D-sql_v2.zip',
			'export/csv/LV-E-csv_v2.zip', 'export/dos/LV-E-dos_v2.zip', 'export/sql/LV-E-sql_v2.zip',
			'export/csv/LV-F-csv_v2.zip', 'export/dos/LV-F-dos_v2.zip', 'export/sql/LV-F-sql_v2.zip',
			'export/csv/LV-G-csv_v2.zip', 'export/dos/LV-G-dos_v2.zip', 'export/sql/LV-G-sql_v2.zip',
			'export/csv/LV-H-csv_v2.zip', 'export/dos/LV-H-dos_v2.zip', 'export/sql/LV-H-sql_v2.zip',
			'export/csv/LV-L-csv_v2.zip', 'export/dos/LV-L-dos_v2.zip', 'export/sql/LV-L-sql_v2.zip',
			'export/csv/LV-M-csv_v2.zip', 'export/dos/LV-M-dos_v2.zip', 'export/sql/LV-M-sql_v2.zip'
		);
		
		$url = 'https://dwz.svw.info/services/files/';
		$datum = date('Ymd');
		$zielpfad = substr($_SERVER['DOCUMENT_ROOT'], 0, -3).'files/dewis/'; // web-Ordner entfernen und Zielordner anhängen 
		
		foreach($links as $link)
		{
			$link_array = pathinfo($link);
			$dateiname = $link_array['filename'];
			$suffix = $link_array['extension'];
			// Datei laden
			echo "Lade $link<br>\n";
			$ch = curl_init($url.$link);
			$zieldatei = fopen($zielpfad.$dateiname.'_'.$datum.'.'.$suffix, 'w');
			curl_setopt($ch, CURLOPT_FILE, $zieldatei);
			curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_exec($ch);
			fclose($zieldatei);
		}
		
		echo "Fertig";
	}

}

/**
 * Instantiate controller
 */
$objSpielerdaten = new DeWIS_Download();
$objSpielerdaten->run();

