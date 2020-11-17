<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2020 Leo Feyer
 */

use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodewis/Spielerdaten.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php'); 

class Spielerdaten
{
	public function run()
	{
		$fideID = (int)\Input::get('id'); // IDE-ID aus der URL laden und in Integer umwandeln
		$data = array();
		
		if($fideID)
		{
			// Personen laden, die der FIDE-ID entsprechen
			$objPlayers = \Database::getInstance()->prepare("SELECT nachname, vorname, fideElo FROM tl_dwz_spi WHERE fideID = ?")
			                                      ->execute($fideID);

			if($objPlayers->numRows) 
			{
				$i = 0;
				while($objPlayers->next())
				{
					$data['player'][$i] = array
					(
						'surname' => $objPlayers->nachname,
						'prename' => $objPlayers->vorname,
						'elo'     => $objPlayers->fideElo
					);
					$i++;
				}
			}
			else
			{
				$data['error'] = 'FIDE id not found';
			}
		}
		else
		{
			$data['error'] = 'Invalid FIDE id';
		}

		header('Content-Type: application/json');
		echo json_encode($data);

	}
}

/**
 * Instantiate controller
 */
$objSpielerdaten = new Spielerdaten();
$objSpielerdaten->run();

