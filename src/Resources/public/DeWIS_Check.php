<?php
/*
 * ====================================================================================
 * Class DeWIS_Check
 * - Prüft die Erreichbarkeit von DeWIS und schreibt die Antwortzeiten in eine Logdatei
 * - Datei muß als Cronjob eingebunden werden
 * ====================================================================================
 */

/**
 * Contao Open Source CMS, Copyright (C) 2005-2022 Leo Feyer
 */

use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodewis/DeWIS_Check.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php'); 

class DeWIS_Check
{
	public function run()
	{
		// Spielersuche
		$param = array
		(
			'funktion' => 'Spielerliste',
			'nachname' => 'aaa',
			'limit'    => 10,
			'nocache'  => true
		);
		$result = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
		print_r($result);
		log_message($result['querytime'],'dewis-antwortzeiten.log');
	}
}

/**
 * Instantiate controller
 */
$objSpielerdaten = new DeWIS_Check();
$objSpielerdaten->run();
