<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @package   DeWIS
 * @file      Spieler.php
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2016
 *
 * Version 1.0 - 08.06.2016 - Frank Hoppe
 * --------------------------------------
 * DeWIS-Abfrage:
 * Ausgabe Spielersuche / Ausgabe Spielerkarteikarte mit Diagramm
 *
 */

namespace Schachbulle\ContaoDewisBundle\Classes;

class Spieler extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'dewis_spieler';
	protected $subTemplate = 'dewis_sub_spielersuche';
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

			$objTemplate->wildcard = '### DEWIS SPIELER ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			// FE-Modus: URL mit allen möglichen Parametern auflösen
			\Input::setGet('id', \Input::get('id')); // ID
			\Input::setGet('search', \Input::get('search')); // Suchbegriff

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
		if($GLOBALS['TL_CONFIG']['dewis_switchedOff'])
		{
			$this->Template = new \FrontendTemplate('dewis_abgeschaltet');
			$this->Template->content = $GLOBALS['TL_CONFIG']['dewis_switchedOffText'];
			return;
		}

		// Blacklist laden
		$Blacklist = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Blacklist();
		
		// Spielerkartei angefordert?
		$id = \Input::get('id'); 
		// Spielersuche aktiv?
		$search = \Input::get('search'); 

		$mitglied = \Schachbulle\ContaoDewisBundle\Helper\Helper::getMitglied(); // Daten des aktuellen Mitgliedes laden
		
		$this->Template->hl = 'h1'; // Standard-Überschriftgröße
		$this->Template->shl = 'h2'; // Standard-Überschriftgröße 2
		$this->Template->headline = 'DWZ - Spieler'; // Standard-Überschrift
		$this->Template->navigation   = \Schachbulle\ContaoDewisBundle\Helper\Helper::Navigation(); // Navigation ausgeben

		// Sperrstatus festlegen
		if($GLOBALS['TL_CONFIG']['dewis_karteisperre_gaeste']) $gesperrt = $mitglied->id ? false : true;
		else $gesperrt = false;

		if($search)
		{
			$check_search = \Schachbulle\ContaoDewisBundle\Helper\Helper::checkSearchstringPlayer($search); // Suchbegriff analysieren

			$this->Template->subHeadline = 'Suche nach '.$search; // Unterüberschrift setzen
			$this->Template->search = $search;

			// Abfrageparameter einstellen
			if($check_search['typ'] == 'name')
			{
				// Spielersuche
				$param = array
				(
					'funktion' => 'Spielerliste',
					'cachekey' => $search,
					'vorname'  => $check_search['vorname'],
					'nachname' => $check_search['nachname'],
					'limit'    => 500
				);
			}
			if($check_search['typ'] == 'pkz')
			{
				// Spielersuche
				$param = array
				(
					'funktion' => 'Spielerliste',
					'cachekey' => $search,
					'vorname'  => $check_search['vorname'],
					'nachname' => $check_search['nachname'],
					'limit'    => 500
				);
			}
			if($check_search['typ'] == 'zps')
			{
				// Spielersuche
				$param = array
				(
					'funktion' => 'Spielerliste',
					'cachekey' => $search,
					'vorname'  => $check_search['vorname'],
					'nachname' => $check_search['nachname'],
					'limit'    => 500
				);
			}

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

			// Leerzeichen in Suche, deshalb Abfrage wiederholen
			if ($strLeer[1])
			{
				// Spielersuche
				$param = array
				(
					'funktion' => 'Spielerliste',
					'cachekey' => $search.'_leer',
					'vorname'  => $check_search['vorname2'],
					'nachname' => $check_search['nachname2'],
					'limit'    => 500
				);
				$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
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
			}

			// Untertemplate initialisieren und füllen
			$this->Subtemplate = new \FrontendTemplate($this->subTemplate);
			$this->Subtemplate->DSBMitglied = $mitglied->dewisID; // Zugewiesene DeWIS-ID
			$this->Subtemplate->daten = $daten;
			$this->Subtemplate->anzahl = count($daten);
			$this->Subtemplate->maxinfo = ($param['limit'] <= count($daten)) ? 'Ausgabelimit von ' . $param['limit'] . ' Spielern erreicht' : '';
			$this->Subtemplate->guestinfo = $gesperrt ? '' : 'Spieler-Karteikarten sind nur für angemeldete Besucher sichtbar!';
			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();
			$this->Template->searchresult = $this->Subtemplate->parse();
			$this->Template->hinweis = $gesperrt;
			$this->Template->registrierung = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Registrierungshinweis();
			$this->Template->fehler = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::ZeigeFehler();
			$this->Template->searchform = true;

		}

		
		// Kartei anfordern, wenn ID numerisch
		if($id && !$Blacklist[$id])
		{
			// Prüfung $id, ob numerisch (ID) oder String (ZPS)
			if(is_numeric($id))
			{
				// Eine PKZ wurde übergeben, Abfrageparameter einstellen
				$param = array
				(
					'funktion' => 'Karteikarte',
					'cachekey' => $id,
					'id'       => $id
				);
			}
			elseif(strlen($id) == 10 && substr($id,5,1) == '-')
			{
				// Eine ZPS wurde übergeben, Abfrageparameter einstellen
				$param = array
				(
					'funktion' => 'KarteikarteZPS',
					'cachekey' => $id,
					'zps'      => $id
				);
			}

			$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

			if(!$resultArr['result']) \Schachbulle\ContaoDewisBundle\Helper\Helper::get404(); // ID nicht gefunden
			
			// Seitentitel ändern
			$objPage->pageTitle = 'DWZ-Karteikarte '.$resultArr['result']->member->firstname.' '.$resultArr['result']->member->surname;
			$this->Template->subHeadline = 'DWZ-Karteikarte '.$resultArr['result']->member->firstname.' '.$resultArr['result']->member->surname; // Unterüberschrift setzen

			// Sichtbarkeit der Karteikarte festlegen
			$this->Template->sichtbar = $gesperrt ? false : true;
			$this->Template->sperre = $gesperrt;

			// Spieler in tl_dwz_spi suchen
			$objSpieler = \Schachbulle\ContaoDewisBundle\Models\DewisSpielerModel::findOneBy('dewisID', $resultArr['result']->member->pid);
			$this->Template->addImage     = true;
			if($objSpieler->addImage)
			{
				// Spielerfoto vorhanden
				$objFile = \FilesModel::findByPk($objSpieler->singleSRC);
			}
			else
			{
				$objFile = \FilesModel::findByUuid($GLOBALS['TL_CONFIG']['dewis_playerDefaultImage']);
			}
			$objBild = new \stdClass();
			\Controller::addImageToTemplate($objBild, array('singleSRC' => $objFile->path, 'size' => unserialize($GLOBALS['TL_CONFIG']['dewis_playerImageSize'])), \Config::get('maxImageWidth'), null, $objFile);
			
			/*********************************************************
			 * Ausgabe Kopfdaten
			*/

			$this->Template->spielername  = sprintf("%s,%s%s", $resultArr['result']->member->surname, $resultArr['result']->member->firstname, $resultArr['result']->member->title ? ',' . $resultArr['result']->member->title : '');
			$this->Template->geburtsjahr  = $GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden'] ? '****' : $resultArr['result']->member->yearOfBirth;
			$this->Template->geschlecht   = $GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden'] ? '*' : ($resultArr['result']->member->gender == 'm' ? 'M' : ($resultArr['result']->member->gender == 'f' ? 'W' : strtoupper($resultArr['result']->member->gender)));
			$this->Template->dewis_id     = $resultArr['result']->member->pid;
			$this->Template->dwz          = $resultArr['result']->member->rating." - ".$resultArr['result']->member->ratingIndex;
			$this->Template->fide_id      = ($resultArr['result']->member->idfide) ? sprintf('<a href="http://ratings.fide.com/card.phtml?event=%s" target="_blank">%s</a>',$resultArr['result']->member->idfide,$resultArr['result']->member->idfide) : '-';
			$this->Template->elo          = ($resultArr['result']->member->elo) ? $resultArr['result']->member->elo : '-';
			$this->Template->fide_titel   = ($resultArr['result']->member->fideTitle) ? $resultArr['result']->member->fideTitle : '-';
			$this->Template->fide_nation  = ($resultArr['result']->member->fideNation) ? ($resultArr['result']->member->gender == 'f' ? sprintf('<a href="https://ratings.fide.com/topfed.phtml?tops=1&ina=1&country=%s" target="_blank">%s</a>',$resultArr['result']->member->fideNation, $resultArr['result']->member->fideNation) : sprintf('<a href="https://ratings.fide.com/topfed.phtml?tops=0&ina=1&country=%s" target="_blank">%s</a>',$resultArr['result']->member->fideNation, $resultArr['result']->member->fideNation)) : '-';

			$this->Template->image        = $objBild->singleSRC;
			$this->Template->imageSize    = $objBild->imgSize;
			$this->Template->imageTitle   = $objBild->imageTitle;
			$this->Template->imageAlt     = $objBild->alt;
			$this->Template->imageCaption = $objBild->caption;
			$this->Template->thumbnail    = $objBild->src;

			// Alte Datenbank abfragen
			if(!\Schachbulle\ContaoDewisBundle\Helper\DeWIS::Karteisperre($id) && $altdb = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::AlteDatenbank($id))
			{
				$this->Template->historie = ($altdb["status"] == "L") ? 'Vorhanden, aber zuletzt abgemeldet' : sprintf("<a href=\"".$GLOBALS['TL_CONFIG']['dewis_elobase_url']."%s\" target=\"_blank\">Alte Karteikarte</a> (Benutzer/Passwort: dwz)",$altdb["zps"]);
			}
			else $this->Template->historie = '-';

			/*********************************************************
			 * Ausgabe Vereinsdaten
	 		*/

			$referent = '';
			$zps = '';
			$sortiert = array();
			if($resultArr['result']->memberships)
			{
				foreach($resultArr['result']->memberships as $m)
				{
					$status                    = $m->state ? $m->state : 'A';
					$zps_nr                    = sprintf("%s-%04d", $m->vkz, $m->membership);
					$verein                    = substr($m->vkz, 1) == '0000' ? sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite()."/%s.html\">%s</a>", $m->vkz, $m->club) : sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $m->vkz, $m->club);
					$sortiert[$status.$zps_nr] = array
					(
						'name'   => $verein,
						'zps'    => $zps_nr,
						'status' => $status
					);
					if($referent == '')
					{
						$referent = $m->assessor;
						$zps      = $m->vkz;
					}
					if($m->assessor == '')
					{
						$referent = $m->assessor;
						$zps      = $m->vkz;
					}
				}
			}
			ksort($sortiert);
			$this->Template->vereine      = $sortiert;

			/*********************************************************
			 * Ausgabe zuständiger Wertungsreferent
			*/

			$this->Template->referent = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($referent);

			/*********************************************************
			 * Ausgabe der Karteikarte (DeWIS)
			*/
			$temp = array(); 
			$chart = array();
			$i = 0;

			if($resultArr['result']->tournaments)
			{
				foreach($resultArr['result']->tournaments as $t)
				{
					$i++;
					$dwz_alt = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($t->ratingOld, $t->ratingOldIndex);
					$dwz_neu = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($t->ratingNew, $t->ratingNewIndex);

					if($t->ratingNewIndex)
					{
						// Nur vorhandene Indexe berücksichtigen
						$chart[] = array
						(
							'Label'     => $t->ratingNewIndex,
							'DWZ'       => $t->ratingNew,
							'Niveau'    => $t->level,
							'Leistung'  => $t->achievement ? $t->achievement : false,
							'Punkte'    => $t->points,
							'Partien'   => $t->games,
							'We'        => $t->we,
						);
					}
					
					$temp[] = array
					(
						'nummer'     => ($i == count($resultArr['result']->tournaments) && $dwz_neu != '&nbsp;') ? 'AKT' : $i,
						'jahr'       => (substr($t->tcode, 0, 1) > '9' ? '20' . (ord(substr($t->tcode, 0, 1)) - 65) : '19' . substr($t->tcode, 0, 1)) . substr($t->tcode, 1, 1),
						'turnier'    => sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite()."/%s/%s.html\" title=\"%s\">%s</a>", $t->tcode, $resultArr['result']->member->pid, $t->tname, \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Turnierkurzname($t->tname)),
						'punkte'     => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Punkte($t->points),
						'partien'    => $t->games,
						'we'         => $dwz_neu == '&nbsp;' ? '&nbsp;' : str_replace('.', ',', $t->we),
						'e'          => $t->eCoefficient,
						'gegner'     => $t->level ? $t->level : '',
						'leistung'   => $t->achievement ? $t->achievement : '&nbsp;',
						'dwz-neu'    => $dwz_neu,
						'ungewertet' => $t->unratedGames
					);
				}
			}
			$this->Template->kartei = $temp;

			/*********************************************************
			 * Ausgabe Diagramm
			*/

			$this->Template->chartlabel = implode(',',\Schachbulle\ContaoDewisBundle\Helper\Helper::ArrayExtract($chart, 'Label'));
			$this->Template->chartdwz = implode(',',\Schachbulle\ContaoDewisBundle\Helper\Helper::Mittelwerte(\Schachbulle\ContaoDewisBundle\Helper\Helper::ArrayExtract($chart, 'DWZ')));

			// Leistungskurve weicher machen
			for($x = 0; $x < count($chart); $x++)
			{
				if($chart[$x]['Leistung'] == 0)
				{
					$chart[$x]['Leistung'] = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::LeistungSchaetzen($chart[$x]['Niveau'], $chart[$x]['Punkte'], $chart[$x]['Partien'], $chart[$x]['DWZ']); 
				}
			}
			$this->Template->chartleistung = implode(',',\Schachbulle\ContaoDewisBundle\Helper\Helper::Mittelwerte(\Schachbulle\ContaoDewisBundle\Helper\Helper::ArrayExtract($chart, 'Leistung')));

			/*********************************************************
			 * Ausgabe Ranglistenplazierungen und Verbandszugehörigkeiten
			*/

			$temp = array(); $temp2 = array(); $x = 0; $y = 0;
			if($resultArr['result']->ranking[1])
			{
				foreach ($resultArr['result']->ranking[1] as $r)
				{
					$temp[$x]['name']     = $r->organizationType == 'o6' ? sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $r->vkz, $r->organization) : sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite()."/%s.html\">%s</a>", substr($r->vkz, 0, 3), $r->organization);
					$temp[$x]['typ']      = $r->organizationType;
					$temp[$x]['platz']    = $r->rank;
					$temp[$x]['referent'] = $r->assessor;
					if($r->organizationType != 'o6')
					{
						$temp2[$y]['typ']  = $r->organizationType;
						$temp2[$y]['name'] = $temp[$x]['name'];
						$y++;
					}
					$x++;
				}
			}
			$this->Template->rangliste    = $temp;
			$this->Template->verbaende    = $temp2;

			/*********************************************************
			 * Ausgabe Metadaten
			*/

			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();

		}
		else
		{
			$this->Template->searchform = true;
		}

	}

}
