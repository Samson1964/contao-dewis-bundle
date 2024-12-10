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

namespace Schachbulle\ContaoDewisBundle\Classes;

class Suche extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'dewis_suche';

	var $Helper;

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_dewis');

			$objTemplate->wildcard = '### DEWIS SUCHMASCHINE ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			$this->Helper = \Schachbulle\ContaoDewisBundle\Helper\Helper::getInstance(); // Hilfsfunktionen bereitstellen
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
	
		global $objPage;
		
		// DWZ-Abfragen abgeschaltet?
		if(isset($GLOBALS['TL_CONFIG']['dewis_switchedOff']) && $GLOBALS['TL_CONFIG']['dewis_switchedOff'])
		{
			$this->Template = new \FrontendTemplate('dewis_abgeschaltet');
			$this->Template->content = $GLOBALS['TL_CONFIG']['dewis_switchedOffText'];
			return;
		}

		// Blacklist laden
		$Blacklist = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::blacklist();

		// ZPS-Variable holen
		$search = \Input::get($this->dewis_searchkey);

		if($search)
		{
			// ==================
			// SUCHE NACH SPIELER
			// ==================
			$check_search = \Schachbulle\ContaoDewisBundle\Helper\Helper::checkSearchstringPlayer($search); // Suchbegriff analysieren

			// Abfrage vorbereiten
			$param = array
			(
				'funktion' => 'Spielerliste',
				'cachekey' => $search,
				'vorname'  => $check_search['vorname'],
				'nachname' => $check_search['nachname'],
				'limit'    => 500
			);
			$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

			// Daten konvertieren für Ausgabe
			$daten = array();
			if($resultArr['result']->members)
			{
				foreach($resultArr['result']->members as $m)
				{
					
					if($Blacklist[$m->pid] || ($GLOBALS['TL_CONFIG']['dewis_passive_ausblenden'] && $m->state == 'P'))
					{
						// Blacklist und Passive überspringen
					}
					else
					{
						$daten[] = array
						(
							'PKZ'         => $m->pid,
							'Verein'      => sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $m->vkz, $m->club),
							'Spielername' => \Schachbulle\ContaoDewisBundle\Helper\Helper::Spielername($m, $gesperrt),
							'KW'          => ($gesperrt) ? '&nbsp;' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
							'DWZ'         => (!$m->rating && $m->tcode) ? 'Restp.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($m->rating, $m->ratingIndex),
							'Elo'         => ($m->elo) ? $m->elo : '-----'
						);
					}
				}
			}

			$this->Template->result_spieler = $daten;

		}

	}

}
