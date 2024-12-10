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

class Verband extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'dewis_verband';
	protected $subTemplate = 'dewis_sub_verbandsuche';
	protected $infoTemplate = 'queries';
	
	var $startzeit; // Startzeit des Skriptes
	var $dewis;
	
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

			$objTemplate->wildcard = '### DEWIS VERBAND ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			// FE-Modus: URL mit allen möglichen Parametern auflösen
			\Input::setGet('zps', \Input::get('zps')); // ZPS-Nummer des Verbands
			\Input::setGet('toplist', \Input::get('toplist')); // Top x bei Toplistenausgabe
			\Input::setGet('sex', \Input::get('sex')); // Geschlecht bei Toplistenausgabe
			\Input::setGet('age_from', \Input::get('age_from')); // Alter von bei Toplistenausgabe
			\Input::setGet('age_to', \Input::get('age_to')); // Alter bis bei Toplistenausgabe
			\Input::setGet('german', \Input::get('german')); // Nur deutsche Spieler

			// Startzeit setzen
			$this->startzeit = microtime(true);
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
		if(isset($GLOBALS['TL_CONFIG']['dewis_switchedOff']) == true)
		{
			$this->Template = new \FrontendTemplate('dewis_abgeschaltet');
			$this->Template->content = $GLOBALS['TL_CONFIG']['dewis_switchedOffText'];
			return;
		}

		// Blacklist laden
		$Blacklist = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::blacklist();

		// ZPS-Variable holen
		$zps = \Input::get('zps');
		if(!$zps) $zps = '000';
		// Listenvariablen holen und anpassen
		$toplist = \Input::get('toplist');
		if($toplist && $toplist > 950) $toplist = 950;
		$sex = \Input::get('sex'); 
		$age_from = \Input::get('age_from'); 
		$age_to = \Input::get('age_to'); 
		$german = \Input::get('german'); 
		
		$mitglied = \Schachbulle\ContaoDewisBundle\Helper\Helper::getMitglied(); // Daten des aktuellen Mitgliedes laden
		
		$this->Template->hl = 'h1'; // Standard-Überschriftgröße
		$this->Template->shl = 'h2'; // Standard-Überschriftgröße 2
		$this->Template->headline = 'DWZ - Verband'; // Standard-Überschrift
		$this->Template->navigation   = \Schachbulle\ContaoDewisBundle\Helper\Helper::Navigation(); // Navigation ausgeben
		$this->Template->zps = $zps; // Aktuelle ZPS-Nummer

		// Sperrstatus festlegen
		if($GLOBALS['TL_CONFIG']['dewis_karteisperre_gaeste']) $gesperrt = $mitglied->id ? false : true;
		else $gesperrt = false;

		/*********************************************************
		 * Ausgabe Verbandszugehörigkeiten (übergeordnete)
		*/

		// Verbände/Vereine laden
		$result = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Verbandsliste('00000');

		$temp = array();
		$y = 0;
		$suchzps = $zps;
		if($suchzps)
		{
			do
			{
				$temp[$y]['typ']  = ($suchzps == $zps) ? 'active ' : '';
				$temp[$y]['name'] = ($suchzps == $zps) ? sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite().'/%s.html">%s</a> - <a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite().'/%s.html">Vereine</a>', $result['verbaende'][$suchzps]['zps'], $result['verbaende'][$suchzps]['name'], $suchzps) : sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite().'/%s.html">%s</a>', $result['verbaende'][$suchzps]['zps'], $result['verbaende'][$suchzps]['name']);
				$alt = $suchzps;
				$suchzps = $result['verbaende'][$suchzps]['parent'];
				$y++;
			}
			while($suchzps != $alt); // Wenn parent-ZPS ungleich aktueller ZPS, dann läuft die Schleife weiter
		}
		$temp  = array_reverse($temp); // Array umdrehen, damit DSB als erstes kommt
		// Ebene hinzufügen
		for($x = 0; $x < count($temp); $x++)
		{
			$temp[$x]['typ'] .= 'level_'.$x;
		}


		/*********************************************************
		 * Ausgabe Verbandszugehörigkeiten (untergeordnete, eine Ebene)
		*/

		if($result['verbaende'][$zps]['childs'])
		{
			foreach($result['verbaende'][$zps]['childs'] as $key => $value)
			{
				if($value != $zps)
				{
					$temp[$y]['typ']  = 'level_'.$x;
					$temp[$y]['name'] = sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite().'/%s.html">%s</a>', $value, $result['verbaende'][$value]['name']);
					$y++;
				}
			}
		}

		$this->Template->verbaende    = $temp;


		/*********************************************************
		* Ausgabe Suchformular, wenn keine Toplistenausgabe angefordert wurde
		*/

		if(!$toplist)
		{
			$this->Template->searchform = true;
			// Formularanzeige: Seitentitel ändern
			$objPage->pageTitle = 'DWZ-Listen '.$result['verbaende'][$zps]['name'];
			$this->Template->subHeadline = 'DWZ-Listen '.$result['verbaende'][$zps]['name']; // Unterüberschrift setzen
		}


		/*********************************************************
		* Ausgabe Topliste des Verbandes
		*/

		if($zps && $toplist)
		{

			// Abfrageparameter einstellen
			$param = array
			(
				'funktion'    => 'Verbandsliste',
				'cachekey'    => $zps.'-'.$toplist.'-'.$sex.'-'.$age_from.'-'.$age_to.'-'.$german,
				'zps'         => $zps,
				'limit'       => $german ? $toplist + 500 : $toplist + 50,
				'alter_von'   => $age_from,
				'alter_bis'   => $age_to,
				'geschlecht'  => $sex,
			);

			$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

			/*********************************************************
			 * Ausgabe der Verbandsrangliste
			*/

			$referent = $resultArr['result']->ratingOfficer;
			
			// Seitentitel/Unterüberschrift generieren
			
			$titel = $resultArr['result']->organization->name.' Top '.$toplist.(($sex == 'm')?' männlich':(($sex == 'f')?' weiblich':'')).(($age_from) ? ' '.$age_from.' - '.$age_to.' Jahre' : (($age_to == 140) ? '' : ' '.$age_from.' - '.$age_to.' Jahre'));
			$objPage->pageTitle = $titel;
			$this->Template->subHeadline = $titel;

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
					
					if(isset($Blacklist[$m->pid]) || ($GLOBALS['TL_CONFIG']['dewis_passive_ausblenden'] && $m->state == 'P'))
					{
						// Passive überspringen
					}
					else
					{
						// FIDE-Nation laden
						if(isset($GLOBALS['TL_CONFIG']['dewis_eloLocal']))
						{
							// Daten aus Tabelle elo holen
							$fide = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::getFIDE($m->idfide); // Abfrage ausführen
							$nation = $fide['land'];
							$fide_elo = $fide['elo'];
							$fide_titel = $fide['titel'];
						}
						else
						{
							$nation = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Nation($m->pid); // Abfrage ausführen
							$fide_elo = ($m->elo) ? $m->elo : '-----';
							$fide_titel = $m->fideTitle;
						}

						if($german && $nation != 'GER' && $nation != '' && $nation != '-')
						{
							continue; // Nur Deutsche gesucht, also nächsten Datensatz prüfen
						}

						$flag_css = \Schachbulle\ContaoDewisBundle\Helper\Helper::Laendercode($nation);
						// Flagge anzeigen, wenn vorhanden
						if($flag_css)
							$flag_content = '<span class="'.$flag_css.'"></span>';
						else
							$flag_content = '<span class="ioc_code">'.$nation.'</span>';
						
						$z++;
						// Daten zuweisen
						$daten[] = array
						(
							'Platz'       => $z,
							'PKZ'         => $m->pid,
							'Status'      => $m->state,
							'Mglnr'       => sprintf("%04d", $m->membership),
							'Spielername' => \Schachbulle\ContaoDewisBundle\Helper\Helper::Spielername($m, $gesperrt),
							'Geschlecht'  => ($m->gender == 'm') ? '&nbsp;' : ($m->gender == 'f' ? 'f' : strtolower($m->gender)),
							'KW'          => ($gesperrt) ? '&nbsp;' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
							'DWZ'         => (!$m->rating && $m->tcode) ? 'Restp.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($m->rating, $m->ratingIndex),
							'Elo'         => ($fide_elo) ? $fide_elo : '-----',
							'FIDE-Titel'  => $fide_titel,
							'FIDE-Nation' => $flag_content,
							'Verein'      => sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $m->vkz, \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Vereinskurzname($m->club))
						);
					}
					if($z == $toplist) break; // Abbruch wenn Limit erreicht
				}
			}

			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();
			$this->Template->sichtbar = true;
			$this->Template->daten = $daten;
			$this->Template->hinweis = $gesperrt;
			$this->Template->registrierung = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Registrierungshinweis();


			/*********************************************************
			 * Ausgabe zuständiger Wertungsreferent
			*/

			$this->Template->referent = ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($referent);

			/*********************************************************
			 * Ausgabe Metadaten
			*/

			$this->Template->zeit_abfrage = ($resultArr['querytime']) ? 'Abfrage in '.$resultArr['querytime'].' sec' : 'Zwischengespeicherte Abfrage';
			$this->Template->zeit_ausgabe = sprintf("%1.3f", microtime(true) - $this->startzeit) . ' sec';

		}

		
	}

}
