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

class Verein extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'dewis_verein';
	protected $subTemplate = 'dewis_sub_vereinsuche';
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

			$objTemplate->wildcard = '### DEWIS VEREIN ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			// FE-Modus: URL mit allen möglichen Parametern auflösen
			\Input::setGet('zps', \Input::get('zps')); // ZPS-Nummer des Vereins
			\Input::setGet('search', \Input::get('search')); // Suchbegriff
			\Input::setGet('order', \Input::get('order')); // Sortierung

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
		if(isset($GLOBALS['TL_CONFIG']['dewis_switchedOff']) && $GLOBALS['TL_CONFIG']['dewis_switchedOff'])
		{
			$this->Template = new \FrontendTemplate('dewis_abgeschaltet');
			$this->Template->content = $GLOBALS['TL_CONFIG']['dewis_switchedOffText'];
			return;
		}

		// Blacklist laden
		$Blacklist = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::blacklist();

		// Vereinsliste angefordert?
		$zps = \Input::get('zps');
		// Vereinssuche aktiv?
		$search = \Input::get('search');
		// Sortierung festlegen
		$order = \Input::get('order');
		$order = ($order == 'alpha') ? 'alpha' : 'rang';

		$mitglied = \Schachbulle\ContaoDewisBundle\Helper\Helper::getMitglied(); // Daten des aktuellen Mitgliedes laden

		$this->Template->hl = 'h1'; // Standard-Überschriftgröße
		$this->Template->shl = 'h2'; // Standard-Überschriftgröße 2
		$this->Template->headline = 'DWZ - Verein'; // Standard-Überschrift
		$this->Template->navigation   = \Schachbulle\ContaoDewisBundle\Helper\Helper::Navigation(); // Navigation ausgeben

		// Sperrstatus festlegen
		if($GLOBALS['TL_CONFIG']['dewis_karteisperre_gaeste']) $gesperrt = $mitglied->id ? false : true;
		else $gesperrt = false;

		// Auf ungültige Zeichen im Suchbegriff prüfen (alles außer Buchstaben, Zahlen, Umlaute, Leerzeichen ist nicht erlaubt)
		if(!preg_match("#^[a-zA-Z0-9äöüÄÖÜß ]+$#", $search))
		{
			$this->Template->fehler = 'Der Suchbegriff darf nur Buchstaben, Zahlen und Leerzeichen enthalten!';
			$this->Template->search = $search;
			$search = '';
		}
		else
		{
			$this->Template->search = $search;
		}

		if($search)
		{

			$search = \StringUtil::generateAlias($search); // Suche modifizieren

			/*********************************************************
			 * Verbands- und Vereinsliste holen
			*/

			$liste = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Verbandsliste('00000');


			/*********************************************************
			 * Suchbegriff im Vereinssuche-Cache?
			*/

			$result_vn = array();
			if($GLOBALS['TL_CONFIG']['dewis_cache'])
			{
				$cache_vn = new \Schachbulle\ContaoHelperBundle\Classes\Cache(array('name' => 'vereinssuche', 'extension' => '.cache'));
				$cache_vn->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

				// Cache laden
				if($cache_vn->isCached($search))
				{
					$result_vn = $cache_vn->retrieve($search);
				}
			}


			/*********************************************************
			 * Suchbegriff im Verbandssuche-Cache?
			*/

			$result_vb = array();
			if($GLOBALS['TL_CONFIG']['dewis_cache'])
			{
				$cache_vb = new \Schachbulle\ContaoHelperBundle\Classes\Cache(array('name' => 'verbandssuche', 'extension' => '.cache'));
				$cache_vb->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

				// Cache laden
				if($cache_vb->isCached($search))
				{
					$result_vb = $cache_vb->retrieve($search);
				}
			}


			/*********************************************************
			 * Verbandsliste durchsuchen, Treffer in Array speichern und im Cache lagern
			*/

			if(!$result_vb)
			{
				// Nichts im Cache, Daten deshalb neu übernehmen
				foreach($liste['verbaende'] as $key => $value)
				{
					if(!empty($search)) $pos = strpos($value['order'], $search);
					if($pos !== false)
					{
						$result_vb[] = array
						(
							'zps'       => $value['zps'],
							'name'      => sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite().'/%s.html">%s</a>', $value['zps'], $value['name']),
						);
					}
				}
				// im Cache speichern
				if($GLOBALS['TL_CONFIG']['dewis_cache']) $cache_vb->store($search, $result_vb, $GLOBALS['TL_CONFIG']['dewis_cache_verband'] * 3600);
			}


			/*********************************************************
			 * Vereinsliste durchsuchen, Treffer in Array speichern und im Cache lagern
			*/

			if(!$result_vn)
			{
				// Nichts im Cache, Daten deshalb neu übernehmen
				foreach($liste['vereine'] as $key => $value)
				{
					if(!empty($search)) $pos = strpos($value['order'], $search);
					if($pos !== false)
					{
						$result_vn[] = array
						(
							'zps'       => $value['zps'],
							'name'      => sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite().'/%s.html">%s</a>', $value['zps'], $value['name']),
						);
					}
				}
				// im Cache speichern
				if($GLOBALS['TL_CONFIG']['dewis_cache']) $cache_vn->store($search, $result_vn, $GLOBALS['TL_CONFIG']['dewis_cache_verband'] * 3600);
			}


			/*********************************************************
			 * Seitentitel ändern
			*/

			$objPage->pageTitle = 'Suche nach '.$search;
			$this->Template->subHeadline = 'Suche nach '.$search; // Unterüberschrift setzen


			/*********************************************************
			 * Direkt zum Verein springen, wenn nur 1 Treffer
			*/
			if(count($result_vb) == 0 && count($result_vn) == 1)
			{
				header('Location:'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite().'/'.$result_vn[0]['zps'].'.html');
			}

			/*********************************************************
			 * Templates füllen
			*/

			$this->Subtemplate = new \FrontendTemplate($this->subTemplate);
			$this->Subtemplate->Sperre = $gesperrt; // Sperre?
			$this->Subtemplate->DSBMitglied = $mitglied->dewisID; // Zugewiesene DeWIS-ID
			$this->Subtemplate->daten_vb = $result_vb;
			$this->Subtemplate->anzahl_vb = count($result_vb);
			$this->Subtemplate->daten_vn = $result_vn;
			$this->Subtemplate->anzahl_vn = count($result_vn);
			$this->Template->searchresult = $this->Subtemplate->parse();
			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();

		}


		// Vereinsliste anfordern
		if($zps)
		{

			// Abfrageparameter einstellen
			$param = array
			(
				'funktion' => 'Vereinsliste',
				'cachekey' => $zps,
				'zps'      => $zps
			);

			$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

			// Sichtbarkeit der Vereinsliste festlegen
			$this->Template->sichtbar = true;


			/*********************************************************
			 * Kein Suchergebnis für $zps -> in Verbandsliste suchen
			*/

			if(!$resultArr['result'])
			{
				$liste = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Verbandsliste('00000'); // Vereins- und Verbandsliste laden
				$vzps = rtrim($zps,0); // In ZPS Nullen hinten entfernen = ZPS des Verbandes

				// Vereinsliste für $zps laden
				foreach($liste['vereine'] as $key => $value)
				{
					if($vzps && $vzps == substr($value['parent'], 0, strlen($vzps)))
					{
						$result[] = array
						(
							'zps'       => $value['zps'],
							'name'      => sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite().'/%s.html">%s</a>', $value['zps'], $value['name']),
						);
					}
				}

				$this->Template->fehler = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::ZeigeFehler();
				if(!$result && !$this->Template->fehler) \Schachbulle\ContaoDewisBundle\Helper\Helper::get404(); // VZPS nicht gefunden

				// Titel-Ausgabe modifizieren
				$ausgabetitel = $liste['verbaende'][$zps]['name'] ? $liste['verbaende'][$zps]['name'] : 'ZPS-Raum '.$zps;
				$objPage->pageTitle = 'Suche nach Vereinen in '.$ausgabetitel;
				$this->Template->subHeadline = 'Suche nach Vereinen in '.$ausgabetitel; // Unterüberschrift setzen

				// Templates füllen
				$this->Subtemplate = new \FrontendTemplate($this->subTemplate);
				$this->Subtemplate->daten_vn = $result;
				$this->Subtemplate->anzahl_vn = count($result);
				$this->Template->searchresult = $this->Subtemplate->parse();
			}

			// Verein in tl_dwz_ver suchen
			$objVerein = \Schachbulle\ContaoDewisBundle\Models\DewisVereinModel::findOneBy('zpsver', $zps);
			//print_r($objVerein);
			$this->Template->addImage     = true;
			$this->Template->homepage     = isset($objVerein->homepage) ? $objVerein->homepage : '';
			$this->Template->info         = isset($objVerein->info) ? $objVerein->info : '';

			/*********************************************************
			 * Logo des Vereins
			*/

			if($objVerein->addImage)
			{
				// Vereinslogo vorhanden
				$objFile = \FilesModel::findByPk($objVerein->singleSRC);
			}
			else
			{
				// Standardlogo verwenden
				$objFile = \FilesModel::findByUuid($GLOBALS['TL_CONFIG']['dewis_clubDefaultImage']);
			}

			// Bild für das Template erstellen (Methode ab Contao 4.10 möglich)
			$figureBuilder = \System::getContainer()->get('contao.image.studio')->createFigureBuilder();
			$figure = $figureBuilder->fromPath($objFile->path)
			                        ->setSize(unserialize($GLOBALS['TL_CONFIG']['dewis_clubImageSize']))
			                        ->enableLightbox(true)
			                        ->disableMetadata(true)
			                        ->build();
			$figure->applyLegacyTemplateData($this->Template);


			/*********************************************************
			 * Ausgabe Kopfdaten
			*/

			$vereinsname = (isset($objVerein->altname) && $objVerein->altname != '') ? $objVerein->altname : $resultArr['result']->union->name;
			$this->Template->listenlink = ($order == 'alpha') ? sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html?order=rang\">Rangliste</a>", $resultArr['result']->union->vkz, $vereinsname) : sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html?order=alpha\">Alphaliste</a>", $resultArr['result']->union->vkz, $vereinsname);
			$this->Template->vereinsname = $vereinsname;
			$referent = $resultArr['result']->ratingOfficer; // Wertungsreferent zuweisen


			/*********************************************************
			 * Ausgabe der Vereinsliste
			*/

			$daten = array();
			$z = 0;
			if($resultArr['result']->members)
			{
				// Seitentitel ändern
				$objPage->pageTitle = ($order == 'alpha') ? 'DWZ-Vereinsliste '.$vereinsname : 'DWZ-Rangliste '.$vereinsname;
				$this->Template->subHeadline = ($order == 'alpha') ? 'DWZ-Vereinsliste '.$vereinsname : 'DWZ-Rangliste '.$vereinsname; // Unterüberschrift setzen

				foreach($resultArr['result']->members as $m)
				{

					if($GLOBALS['TL_CONFIG']['dewis_passive_ausblenden'] && $m->state == 'P')
					{
						// Passive überspringen
					}
					else
					{
						// Schlüssel für Sortierung generieren
						$z++;
						$key = ($order == 'alpha') ? \StringUtil::generateAlias($m->surname.$m->firstname.$z) : sprintf('%05d-%04d-%s-%03d', 10000 - $m->rating, 1000 - $m->ratingIndex, ($m->tcode) ? $m->tcode : 'Z', $z);
						// Daten zuweisen
						if(!isset($Blacklist[$m->pid]))
						{
							$daten[$key] = array
							(
								'PKZ'         => $m->pid,
								'Mglnr'       => ($gesperrt) ? '&nbsp;' : sprintf("%04d", $m->membership),
								'Status'      => ($gesperrt) ? '&nbsp;' : $m->state,
								'Spielername' => \Schachbulle\ContaoDewisBundle\Helper\Helper::Spielername($m, $gesperrt),
								'Geschlecht'  => ($m->gender == 'm') ? '&nbsp;' : ($m->gender == 'f' ? 'w' : strtolower($m->gender)),
								'KW'          => ($gesperrt) ? '&nbsp;' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
								'DWZ'         => (!$m->rating && $m->tcode) ? 'Restp.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($m->rating, $m->ratingIndex),
								'Elo'         => ($m->elo) ? $m->elo : '-----',
								'FIDE-Titel'  => $m->fideTitle
							);
						}
					}
				}
				// Liste sortieren (ASC)
				ksort($daten);
				// Platzierung hinzufügen
				if($order == 'rang')
				{
					$this->Template->rangliste = true;
					$z = 1;
					foreach($daten as $key => $value)
					{
						$daten[$key]['Platz'] = $z;
						$z++;
					}
				}
			}
			$this->Template->daten = $daten;
			$this->Template->tablesorter = ($order == 'rang') ? array(4, 5, 7) : array(3, 4, 6); // Spaltenposition für Tablesorter-Parser

			/*********************************************************
			 * Ausgabe Verbandszugehörigkeiten
			*/

			$result = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Verbandsliste('00000');

			$temp = array();
			$y = 0;
			if(isset($result['vereine'][$zps])) $suchzps = $result['vereine'][$zps]['parent'];
			if($suchzps)
			{
				do
				{
					$temp[$y]['typ']  = '';
					$temp[$y]['name'] = sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite().'/%s.html">%s</a>', $result['verbaende'][$suchzps]['zps'], $result['verbaende'][$suchzps]['name']);
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
				$temp[$x]['typ'] = 'level_'.$x;
			}

			$this->Template->verbaende    = $temp;


			/*********************************************************
			 * Ausgabe zuständiger Wertungsreferent
			*/

			$this->Template->referent = ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($referent);


			/*********************************************************
			 * Ausgabe Metadaten
			*/

			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();
			$this->Template->hinweis = $gesperrt;
			$this->Template->registrierung = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Registrierungshinweis();

		}


	}

}
