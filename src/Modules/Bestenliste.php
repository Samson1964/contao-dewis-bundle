<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   DeWIS
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

namespace Schachbulle\ContaoDewisBundle\Modules;

class Bestenliste extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'dewis_bestenliste';
	
	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_dewis');

			$objTemplate->wildcard = '### DEWIS BESTENLISTE ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{

		global $objPage;
		
		// Blacklist laden
		$Blacklist = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::blacklist();

		// Parameter für Bestenliste

		if($this->dwz_topcount)
		{

			// Faktor festlegen für Anzahl der abzurufenden Spieler
			if($this->dwz_topcount < 11) $faktor = 11;
			elseif($this->dwz_topcount < 21) $faktor = 10;
			elseif($this->dwz_topcount < 51) $faktor = 8;
			elseif($this->dwz_topcount < 101) $faktor = 5;
			elseif($this->dwz_topcount < 201) $faktor = 4;
			else $faktor = 3;

			// Abfrageparameter einstellen
			$param = array
			(
				'funktion'   => 'Verbandsliste',
				'cachekey'   => '0-'.$this->dwz_topcount.'-'.$this->dwz_gender.'-0-140',
				'cachetime'  => 86400, // 1 Tag
				'zps'        => '0',
				'limit'      => $this->dwz_topcount * $faktor,
				'alter_von'  => 0,
				'alter_bis'  => 140,
				'geschlecht' => $this->dwz_gender == 'm' ? '' : $this->dwz_gender,
			);

			$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

			/*********************************************************
			 * Ausgabe der Verbandsrangliste
			*/

			// Verbandsliste neu sortieren, vorher das Objekt mittels json in ein Array umwandeln
			$sorted = \Schachbulle\ContaoHelperBundle\Classes\Helper::sortArrayByFields(
				json_decode(json_encode($resultArr['result']->members), true),
				array(
					'rating'      => SORT_DESC,
					'ratingIndex' => SORT_DESC,
					'surname'     => array(SORT_ASC, SORT_STRING),
					'firstname'   => array(SORT_ASC, SORT_STRING)
				)
			);
			// Sortiertes Array umwandeln in Objekt
			$liste = json_decode(json_encode($sorted), false);

			$daten = array();
			$z = 0;

			if(is_array($liste))
			{
				foreach($liste as $m)
				{
					
					if(isset($Blacklist[$m->pid]) || $m->state == 'P')
					{
						// Blacklist-Spieler oder passive Spieler überspringen
					}
					else
					{

						// FIDE-Nation laden
						$nation = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Nation($m->pid); // Abfrage ausführen

						if($nation == 'GER')
						{
							$z++;
							// Daten zuweisen
							$daten[] = array
							(
								'Platz'       => $z,
								'PKZ'         => $m->pid,
								'Spielername' => \Schachbulle\ContaoDewisBundle\Helper\Helper::Spielername($m, false, 1),
								'DWZ'         => (!$m->rating && $m->tcode) ? 'Restp.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($m->rating, $m->ratingIndex),
								'FIDE-Titel'  => $m->fideTitle,
								'Verein'      => sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $m->vkz, \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Vereinskurzname($m->club))
							);
						}
					}
					if($z == $this->dwz_topcount) break; // Abbruch wenn Limit erreicht
				}
			}

			$this->Template->liste = $daten;
		}
		
	}

}
