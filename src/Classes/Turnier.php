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

class Turnier extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'dewis_turnier';
	protected $subTemplate = 'dewis_sub_turniersuche';
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

			$objTemplate->wildcard = '### DEWIS TURNIER ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			// FE-Modus: URL mit allen möglichen Parametern auflösen
			\Input::setGet('code', \Input::get('code')); // Turniercode
			\Input::setGet('id', \Input::get('id')); // ID des Spielers

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
		$Blacklist = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::blacklist();

		$code = \Input::get('code'); // Turniercode angefordert?
		$search = \Input::get('search'); // Turniersuche aktiv?
		$turniercode = str_replace(' ','+',\Input::get('code')); // Turniercode, Leerzeichen durch + ersetzen, da der Browser aus + Leerzeichen macht
		$id = \Input::get('id'); // Spieler-ID
		$view = \Input::get('view'); // View
		
		$mitglied = \Schachbulle\ContaoDewisBundle\Helper\Helper::getMitglied(); // Daten des aktuellen Mitgliedes laden
		$aktzeit = time();
		
		$this->Template->hl = 'h1'; // Standard-Überschriftgröße
		$this->Template->shl = 'h2'; // Standard-Überschriftgröße 2
		$this->Template->headline = 'DWZ - Turnier'; // Standard-Überschrift
		$this->Template->navigation   = \Schachbulle\ContaoDewisBundle\Helper\Helper::Navigation(); // Navigation ausgeben

		// Sperrstatus festlegen
		if($GLOBALS['TL_CONFIG']['dewis_karteisperre_gaeste']) $gesperrt = $mitglied->id ? false : true;
		else $gesperrt = false;

		// DeWIS-Klasse initialisieren
		$dewis = new \Schachbulle\ContaoDewisBundle\Helper\DeWIS();

		// Verbands- und Vereinsliste holen
		$liste = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Verbandsliste('00000');

		if($search)
		{

			/*********************************************************
			 * TURNIERSUCHE
			 * ============
			 * Die DeWIS-API ermöglicht keine Turniersuche jahresübergreifend.
			 * Deshalb wird an dieser Stelle ein Array mit den Zeiträumen generiert
			 * und die Jahre einzeln abgefragt.
			*/

			// Übergebenen ZPS-Parameter korrigieren: dreistellig und Großschreibung
			$zps = substr(strtoupper(\Input::get('zps')).'000',0,3);

			// ZPS-Cookie setzen
			setcookie('dewis-verband-zps', rtrim(\Input::get('zps'),0), time()+8640000, '/');
			
			// GET-Parameter korrigieren
			$last_months = 0 + (int)\Input::get('last_months');
			$from_year = sprintf('%04d',\Input::get('from_year'));
			$to_year = sprintf('%04d',\Input::get('to_year'));
			$from_month = sprintf('%02d',\Input::get('from_month'));
			$to_month = sprintf('%02d',\Input::get('to_month'));
			($from_year < 2011) ? $from_year = 2011 : '';
			
			// Zeitraum anpassen, wenn "Letzte x Monate" gewählt wurde
			if($last_months > 0 && $last_months < 13)
			{
				$last_months--; // Wegen aktuellem Monat 1 abziehen
				$from_year = date('Y', strtotime('-'.$last_months.' months', mktime(0,0,0,date("n",$aktzeit),1,date("Y",$aktzeit))));
				$from_month = date('m', strtotime('-'.$last_months.' months', mktime(0,0,0,date("n",$aktzeit),1,date("Y",$aktzeit))));
				$to_year = date('Y', $aktzeit);
				$to_month = date('m', $aktzeit);
			}

			// Nur zulässige Jahre berücksichtigen
			if($from_year != '0000' && $to_year != '0000')
			{
				$zeitraum = array();
				for($year = $from_year; $year <= $to_year; $year++)
				{
					$zeitraum[] = array
					(
						'von' => ($year == $from_year) ? $from_year.'-'.$from_month.'-01' : $year.'-01-01',
						'bis' => ($year == $to_year) ? $to_year.'-'.$to_month.'-'.\Schachbulle\ContaoDewisBundle\Helper\Helper::Monatstage($to_year)[ltrim($to_month,0)] : $year.'-12-31',
					);
				}

				/*********************************************************
				 * Abfrage aller Zeiträume durchführen
				*/

				$daten = array();
				foreach($zeitraum as $periode)
				{
					/*********************************************************
					 * Suchbegriff im Turniersuche-Cache?
					*/

					// ZPS-Nummer des Verbandes
					$param = array
					(
						'funktion'  => 'Turnierliste',
						'cachekey'  => strtolower(\Input::get('keyword')).'-'.$zps.'-'.$periode['von'].'-'.$periode['bis'],
						'von'       => $periode['von'],
						'bis'       => $periode['bis'],
						'zps'       => $zps,
						'suche'     => strtolower(\Input::get('keyword')),
					);

					$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

					if($resultArr['result']->tournaments)
					{
						foreach($resultArr['result']->tournaments as $t)
						{
							$daten[] = array
							(
								'Teilnehmer'  => $t->cntPlayer,
								'Turniercode' => $t->tcode,
								'Turniername' => sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite().'/%s.html" title="%s">%s</a>', $t->tcode, $t->tname, \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Turnierkurzname($t->tname)),
								'Turnierende' => substr($t->finishedOn,8,2).'.'.substr($t->finishedOn,5,2).'.'.substr($t->finishedOn,0,4),
								'Auswerter'   => ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($t->assessor1, false),
							);
						}
					}
				}
			}

			/*********************************************************
			 * Ergebnisse sortieren (nach Turniercode abwärts)
			*/

			// Hilfsarray für Sortierung anlegen
			$sortArray = array();
			if(is_array($daten))
			{
				foreach($daten as $arr)
				{
					foreach($arr as $key=>$value)
					{
						if(!isset($sortArray[$key]))
						{
							$sortArray[$key] = array();
						}
						$sortArray[$key][] = $value;
					}
				}
			}
			
			$orderby = 'Turniercode'; // Sortierschlüssel
			if(is_array($sortArray[$orderby])) array_multisort($sortArray[$orderby],SORT_DESC,$daten); 

			/*********************************************************
			 * Seitentitel ändern
			*/

			$title = 'Ergebnis für den Zeitraum '.$from_month.'/'.$from_year.' bis '.$to_month.'/'.$to_year;
			$objPage->pageTitle = $title;
			$this->Template->subHeadline = $title; // Unterüberschrift setzen


			/*********************************************************
			 * Templates füllen
			*/

			$this->Subtemplate = new \FrontendTemplate($this->subTemplate);
			$this->Subtemplate->daten = $daten;
			$this->Subtemplate->anzahl = is_array($daten) ? count($daten) : 0;
			$this->Subtemplate->search_keyword = $keyword;
			$this->Subtemplate->search_verband = $zps;
			$this->Subtemplate->search_from = $from_month.'/'.$from_year;
			$this->Subtemplate->search_to = $to_month.'/'.$to_year;
			$this->Template->fehler = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::ZeigeFehler();
			$this->Template->searchresult = $this->Subtemplate->parse();
			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();

		}
		elseif($turniercode && $view == 'results')
		{

			/*********************************************************
			 * Ausgabe der Ergebnisse bzw. des Scoresheets
			*/

			/*********************************************************
			 * Auswertung laden und DWZ speichern
			*/

			$playerArr = array(); // Array mit den Teilnehmern
			$resultArr = array(); // Array mit den Ergebnissen
			$result_tausw = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Turnierauswertung($turniercode); // Auswertung laden (für DWZ)

			if($result_tausw->evaluation)
			{
				foreach ($result_tausw->evaluation as $t)
				{
					$playerArr[$t->pid] = array
					(
						'Spielername'	=> $Blacklist[$t->pid] ? '***' : \Schachbulle\ContaoDewisBundle\Helper\Helper::Spielername($t, $gesperrt),
						'Spielername'	=> $Blacklist[$t->pid] ? '***' : ($gesperrt ? sprintf("%s,%s%s", $t->surname, $t->firstname, $t->title ? ',' . $t->title : '') : sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getSpielerseite()."/%s.html\">%s</a>", $t->pid, sprintf("%s,%s%s", $t->surname, $t->firstname, $t->title ? ',' . $t->title : ''))),
						'Scoresheet'	=> $Blacklist[$t->pid] ? '' : ($gesperrt ? '' : sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite()."/%s/%s.html\">SC</a>", $result_tausw->tournament->tcode, $t->pid)),
						'DSB-Mitglied'	=> sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite()."/%s/%s.html\">SC</a>", $result_tausw->tournament->tcode, $t->pid),
						'DWZ'			=> ($t->ratingOld) ? $t->ratingOld : '',
						'Punkte'		=> 0,
						'Partien'		=> 0,
						'Buchholz'		=> 0,
					);
				}
			}

			/*********************************************************
			 * Ergebnisse laden
			*/

			// Abfrageparameter
			$param = array
			(
				'funktion'  => 'Turnierergebnisse',
				'cachekey'  => $turniercode,
				'code'      => $turniercode
			);

			$result = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
			$result_tresult = $result['result'];

			/*********************************************************
			 * Turnierheader
			*/

			$objPage->pageTitle = 'Ergebnisse '.$result_tresult->tournament->tname;
			$this->Template->subHeadline = $result_tresult->tournament->tname; // Unterüberschrift setzen

			$theader = array
			(
				'Auswertung'	=> sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite().'/%s.html">Turnierauswertung</a>', $result_tresult->tournament->tcode),
				'Ergebnisse'	=> sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite().'/%s/Ergebnisse.html">Turnierergebnisse</a>', $result_tresult->tournament->tcode),
				'Turniercode'	=> $result_tresult->tournament->tcode,
				'Turniername'	=> $result_tresult->tournament->tname,
				'Turnierende'	=> \Schachbulle\ContaoDewisBundle\Helper\Helper::datum_mysql2php($result_tresult->tournament->finishedOn),
				'Berechnet'		=> sprintf("%s %s", \Schachbulle\ContaoDewisBundle\Helper\Helper::datum_mysql2php(substr($result_tresult->tournament->computedOn, 0, 10)), substr($result_tresult->tournament->computedOn, 11, 5)),
				'Nachberechnet'	=> $result_tresult->tournament->recomputedOn == 'NULL' || $result_tresult->tournament->recomputedOn == '' ? '&nbsp;' : sprintf("%s %s", \Schachbulle\ContaoDewisBundle\Helper\Helper::datum_mysql2php(substr($result_tresult->tournament->recomputedOn, 0, 10)), substr($result_tresult->tournament->recomputedOn, 11, 5)),
				'Auswerter1'	=> ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($result_tresult->tournament->assessor1),
				'Auswerter2'	=> ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($result_tresult->tournament->assessor2, false),
				'Spieler'		=> $result_tresult->tournament->cntPlayer,
				'Partien'		=> $result_tresult->tournament->cntGames,
				'Runden'		=> $result_tresult->tournament->rounds,
			);

			/*********************************************************
			 * Ergebnisse in einem Array speichern
			*/

			if ($result_tresult->rounds)
			{
				foreach($result_tresult->rounds as $r)
				{
					foreach ($r->games as $g) 
					{
						// Es kann mehrere Ergebnisse je Runde geben, deshalb das Subarray!
						if(!$playerArr[$g->idWhite]['Spielername']) $playerArr[$g->idWhite]['Spielername'] = $g->white;
						$resultArr[$g->idWhite][$r->no][] = array
						(
							'Gegner'   => $g->idBlack,
							'Ergebnis' => mb_substr($g->result,0,1),
							'Farbe'    => 'white',
							'Nummer'   => 0,
						); 
						if(!$playerArr[$g->idBlack]['Spielername']) $playerArr[$g->idBlack]['Spielername'] = $g->black;
						$resultArr[$g->idBlack][$r->no][] = array
						(
							'Gegner'   => $g->idWhite,
							'Ergebnis' => mb_substr($g->result,2,1),
							'Farbe'    => 'black',
							'Nummer'   => 0,
						); 
					}
				}
			}

			/*********************************************************
			 * Punkte addieren
			*/

			foreach($resultArr as $playerId => $roundArr)
			{
				foreach($roundArr as $runde => $dataArr)
				{
					foreach($dataArr as $erg)
					{
						switch($erg['Ergebnis'])
						{
							case '1':
							case '+':
								$playerArr[$playerId]['Punkte'] += 1;
								$playerArr[$playerId]['Partien'] += 1;
								break;
							case '½':
								$playerArr[$playerId]['Punkte'] += .5;
								$playerArr[$playerId]['Partien'] += 1;
								break;
							case '0':
							case '-':
								$playerArr[$playerId]['Partien'] += 1;
								break;
							default:
						}
					}
				}
			}

			/*********************************************************
			 * Buchholz addieren (für Sortierung)
			*/

			foreach($resultArr as $playerId => $roundArr)
			{
				foreach($roundArr as $runde => $dataArr)
				{
					foreach($dataArr as $opp)
					{
						$playerArr[$playerId]['Buchholz'] += $playerArr[$opp['Gegner']]['Punkte'];
					}
				}
			}

			/*********************************************************
			 * Sortierschlüssel hinzufügen und Array für Sortierung umbauen
			*/

			$i = 0;
			$tempArr = array();
			foreach($playerArr as $playerId => $dataArr)
			{
				$i++;
				$key = sprintf('%04d-%03d-%04d-%04d', 9999 - $playerArr[$playerId]['Punkte'] * 10, $playerArr[$playerId]['Partien'], 9999 - $playerArr[$playerId]['Buchholz'], $i);
				$tempArr[$key] = array
				(
					'Spielername'	=> $playerArr[$playerId]['Spielername'],
					'Scoresheet'	=> $playerArr[$playerId]['Scoresheet'],
					'DSB-Mitglied'	=> $playerArr[$playerId]['DSB-Mitglied'],
					'DWZ'			=> $playerArr[$playerId]['DWZ'],
					'Punkte'		=> $playerArr[$playerId]['Punkte'],
					'Partien'		=> $playerArr[$playerId]['Partien'],
					'Buchholz'		=> $playerArr[$playerId]['Buchholz'],
					'ID'			=> $playerId,
				);
			}

			ksort($tempArr); // Nach Schlüssel alphabetisch sortieren

			// Array zurückwandeln
			$playerArr = array();
			$i = 0;
			foreach($tempArr as $key => $dataArr)
			{
				$i++;
				$playerArr[$dataArr['ID']] = array
				(
					'Nummer'		=> $i,
					'Spielername'	=> $dataArr['Spielername'],
					'Scoresheet'	=> $dataArr['Scoresheet'],
					'DSB-Mitglied'	=> $dataArr['DSB-Mitglied'],
					'DWZ'			=> $dataArr['DWZ'],
					'Punkte'		=> $dataArr['Punkte'],
					'Partien'		=> $dataArr['Partien'],
					'Ergebnis'		=> sprintf("%s/%s", \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Punkte($dataArr['Punkte']), $dataArr['Partien']),
					'Buchholz'		=> $dataArr['Buchholz'],
				);
			}

			/*********************************************************
			 * Ergebnisse in Array einfügen und gegnerische Nummer (nach Sortierung) ergänzen
			*/

			foreach($playerArr as $playerId => $dataArr)
			{
				// Nummern der Gegner ergänzen
				if($resultArr[$playerId])
				{
					foreach($resultArr[$playerId] as $runde => $roundArr)
					{
						for($x = 0; $x < count($roundArr); $x++)
						{
							$resultArr[$playerId][$runde][$x]['Nummer'] = $playerArr[$roundArr[$x]['Gegner']]['Nummer']; // gegnerische Nummer ergänzen
						}
					}
				}
				// Ergebnisarray einfügen
				$playerArr[$playerId]['Ergebnisse'] = $resultArr[$playerId];
			}

			/*********************************************************
			 * Ausgabe für Scoresheet modifizieren, wenn $id gesetzt
			*/

			if($id && $playerArr[$id])
			{

				// Seitentitel/Unterüberschrift
				$titel = 'Scoresheet '.strip_tags($playerArr[$id]['Spielername']).' / '.$result_tresult->tournament->tname;
				$objPage->pageTitle = $titel;
				$this->Template->subHeadline = $result_tresult->tournament->tname; // Unterüberschrift setzen
				$this->Template->spielername = strip_tags($playerArr[$id]['Spielername']);
				$this->Template->dwz = $playerArr[$id]['DWZ'];
				
				// Ergebnisarray neu zusammensetzen
				$ergArr = array();
				$sumPunkte = 0; $sumWe = 0;
				if ($playerArr[$id]['Ergebnisse'])
				{
					foreach($playerArr[$id]['Ergebnisse'] as $runde => $dataArr)
					{
						foreach($dataArr as $erg)
						{
							// Punkte addieren
							switch($erg['Ergebnis'])
							{
								case '1':
									$We = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Gewinnerwartung($playerArr[$id]['DWZ'], $playerArr[$erg['Gegner']]['DWZ']);
									if($We)
									{
										$sumPunkte += 1;
										$sumWe += $We;
									}
									break;
								case '½':
									$We = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Gewinnerwartung($playerArr[$id]['DWZ'], $playerArr[$erg['Gegner']]['DWZ']);
									if($We)
									{
										$sumPunkte += .5;
										$sumWe += $We;
									}
									break;
								case '0':
									$We = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Gewinnerwartung($playerArr[$id]['DWZ'], $playerArr[$erg['Gegner']]['DWZ']);
									if($We)
									{
										$sumWe += $We;
									}
									break;
								default:
									$We = false;
							}
							$ergArr[] = array
							(
								'Runde'		=> $runde,
								'Gegner'	=> $playerArr[$erg['Gegner']]['Spielername'],
								'Scoresheet'=> $playerArr[$erg['Gegner']]['Scoresheet'],
								'DWZ'		=> $playerArr[$erg['Gegner']]['DWZ'],
								'Farbe'	    => $erg['Farbe'],
								'Ergebnis'	=> $erg['Ergebnis'],
								'We'	    => $We,
							);
						}
					}
				}
				// Summe hinzufügen
				$ergArr[] = array
				(
					'Runde'		=> 'Σ',
					'Gegner'	=> '',
					'Scoresheet'=> '',
					'DWZ'		=> '',
					'Farbe'	    => '',
					'Ergebnis'	=> $sumPunkte,
					'We'	    => $sumWe,
				);
				
				if(!$gesperrt) 
				{
					$this->Template->daten = $ergArr;
					$this->Template->scoresheet = true;
				}
			}
			else
			{
				$this->Template->daten = $playerArr;
				//if(!$gesperrt) 
				$this->Vereinslose($theader, $playerArr); // Vereinslosen-Statistik schreiben
			}

			//\Schachbulle\ContaoDewisBundle\Helper\DeWIS::debug($playerArr);
			$this->Template->turnierheader = $theader;
			$this->Template->ergebnisse = true;
			$this->Template->ergebnisliste = true;
			$this->Template->hinweis = $gesperrt;
			$this->Template->registrierung = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Registrierungshinweis();
			$this->Template->fehler = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::ZeigeFehler();
			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();

		}
		elseif($turniercode && !$id)
		{

			/*********************************************************
			 * Ausgabe der Auswertung
			*/

			$result_tausw = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Turnierauswertung($turniercode);

			/*********************************************************
			 * Turnierheader
			*/

			$objPage->pageTitle = 'DWZ-Auswertung '.$result_tausw->tournament->tname;
			$this->Template->subHeadline = $result_tausw->tournament->tname; // Unterüberschrift setzen

			$theader = array
			(
				'Ergebnisse'	=> sprintf('<a href="'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite().'/%s/Ergebnisse.html">Turnierergebnisse</a>', $result_tausw->tournament->tcode),
				'Turniercode'	=> $result_tausw->tournament->tcode,
				'Turniername'	=> $result_tausw->tournament->tname,
				'Turnierende'	=> \Schachbulle\ContaoDewisBundle\Helper\Helper::datum_mysql2php($result_tausw->tournament->finishedOn),
				'Berechnet'		=> sprintf("%s %s", \Schachbulle\ContaoDewisBundle\Helper\Helper::datum_mysql2php(substr($result_tausw->tournament->computedOn, 0, 10)), substr($result_tausw->tournament->computedOn, 11, 5)),
				'Nachberechnet'	=> $result_tausw->tournament->recomputedOn == 'NULL' || $result_tausw->tournament->recomputedOn == '' ? '&nbsp;' : sprintf("%s %s", \Schachbulle\ContaoDewisBundle\Helper\Helper::datum_mysql2php(substr($result_tausw->tournament->recomputedOn, 0, 10)), substr($result_tausw->tournament->recomputedOn, 11, 5)),
				'Auswerter1'	=> ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($result_tausw->tournament->assessor1),
				'Auswerter2'	=> ($gesperrt) ? 'Sie müssen sich anmelden, um diese Daten sehen zu können.' : \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Wertungsreferent($result_tausw->tournament->assessor2, false),
				'Spieler'		=> $result_tausw->tournament->cntPlayer,
				'Partien'		=> $result_tausw->tournament->cntGames,
				'Runden'		=> $result_tausw->tournament->rounds,
			);

			/*********************************************************
			 * Auswertung
			*/

			$daten = array();
			if($result_tausw->evaluation)
			{
				$z = 0;
				foreach ($result_tausw->evaluation as $t)
				{
					// Ratingdifferenz errechnen
					$ratingdiff = $t->ratingNew - $t->ratingOld;
					if($ratingdiff > 0) $ratingdiff = "+".$ratingdiff;

					// Schlüssel für Sortierung generieren
					$z++;
					$key = \StringUtil::generateAlias($t->surname.$t->firstname).$z;

					$daten[$key] = array
					(
						'PKZ'			=> $t->pid,
						'Spielername'	=> $Blacklist[$t->pid] ? '***' : \Schachbulle\ContaoDewisBundle\Helper\Helper::Spielername($t, $gesperrt),
						'Scoresheet'	=> $Blacklist[$t->pid] ? '' : ($gesperrt ? '' : sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite()."/%s/%s.html\">SC</a>", $result_tausw->tournament->tcode, $t->pid)),
						'DWZ alt'		=> \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($t->ratingOld, $t->ratingOldIndex),
						'DWZ neu'		=> \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($t->ratingNew, $t->ratingNewIndex),
						'MglNr'         => sprintf("%04d", $t->membership),
						'VKZ'           => sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $t->vkz, sprintf("%s-%s", $t->vkz, sprintf("%04d", $t->membership))),
						'ZPS'           => sprintf("%s-%s", $t->vkz, sprintf("%04d", $t->membership)),
						'Verein'        => sprintf("<a href=\"".\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite()."/%s.html\">%s</a>", $t->vkz, $t->club),
						'Geburt'        => $t->yearOfBirth,
						'Geschlecht'    => ($t->gender == 'm') ? '&nbsp;' : ($t->gender == 'f' ? 'w' : strtolower($t->gender)),
						'Elo'           => ($t->elo) ? $t->elo : '-----',
						'Titel'         => $t->fideTitle ? $t->fideTitle : '&nbsp;',
						'DWZ+-'         => ($t->ratingNew && $t->ratingOld) ? $ratingdiff : "",
						'Punkte'        => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Punkte($t->points),
						'Partien'       => $t->games,
						'Ungewertet'    => $t->unratedGames == 'NULL' || !$t->unratedGames ? '&nbsp;' : $t->unratedGames,
						'E'             => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::DWZ($t->ratingNew, $t->ratingNewIndex) == '-----' ? '&nbsp;' : $t->eCoefficient,
						'Ergebnis'      => sprintf("%s/%s", \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Punkte($t->points), $t->games),
						'We'            => str_replace('.', ',', $t->we),
						'Niveau'        => $t->level,
						'Leistung'      => $t->achievement ? $t->achievement : '&nbsp;',
					);
				}
				// Liste sortieren (ASC)
				ksort($daten);
			}

			$this->Template->turnierheader = $theader;
			$this->Template->daten = $daten;
			$this->Template->auswertung = true;
			$this->Template->hinweis = $gesperrt;
			$this->Template->registrierung = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Registrierungshinweis();
			$this->Template->fehler = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::ZeigeFehler();
			$Infotemplate = new \FrontendTemplate($this->infoTemplate);
			$this->Template->infobox = $Infotemplate->parse();

		}
		else
		{

			/*********************************************************
			 * Suche nicht aktiv, deshalb Suchformular initialisieren
			*/

			// ZPS der Verbände in Cookie gespeichert?
			$zpscookie = \Input::cookie('dewis-verband-zps');
			
			// Auswahl Verbände
			// DeWIS-API erwartet immer dreistellige ZPS!
			foreach($liste['verbaende'] as $key => $value)
			{
				$kurz = rtrim($value['zps'],0);
				$kurzlaenge = strlen($kurz);
				if($zpscookie) 
				{
					// Verband vorselektieren, wenn Cookie gesetzt ist
					$selected = ($zpscookie == $kurz) ? ' selected' : '';
				}
				else
				{
					// Kein oder leeres Cookie, ZPS 0 setzen
					$selected = ($kurzlaenge) ? '' : ' selected';
				}
					
				switch($kurzlaenge)
				{
					case 0:
						$opArray = array('<option value="0" class="level_0"'.$selected.'><b>0 - Alle Verbände</b></option>');
						break;	
					case 1:
						$opArray[] = sprintf('<option value="%s00" class="level_1"'.$selected.'>%s - %s</option>', $kurz, $kurz, $value['name']);
						break;
					case 2:
						$opArray[] = sprintf('<option value="%s0" class="level_2"'.$selected.'>%s - %s</option>', $kurz, $kurz, $value['name']);
						break;
					case 3:
						$opArray[] = sprintf('<option value="%s" class="level_3"'.$selected.'>%s - %s</option>', $kurz, $kurz, $value['name']);
						break;
					default:
				}
			}
			
			$this->Template->form_verbaende = implode("\n",$opArray);

			// Auswahl Zeitraum
			$aktjahr = date("Y");
			$aktmonat = date("n");
			$monate = array
			(
				1 => "Januar",
				2 => "Februar",
				3 => "März",
				4 => "April",
				5 => "Mai",
				6 => "Juni",
				7 => "Juli",
				8 => "August",
				9 => "September",
				10 => "Oktober",
				11 => "November",
				12 => "Dezember"
			);

			// Auswahl Von-Monat/Bis-Monat
			$opArray = array();
			for($x = 1; $x <= 12; $x++)
			{
				$opArray[] = ($x == $aktmonat) ? '<option value="'.sprintf("%02d",$x).'" selected>'.$monate[$x].'</option>' : '<option value="'.sprintf("%02d",$x).'">'.$monate[$x].'</option>';
			}
			$this->Template->form_monat = implode("\n",$opArray);

			// Auswahl Von-Jahr
			$opArray = array();
			for($x = 2011; $x <= $aktjahr; $x++)
			{
				$opArray[] = ($x == $aktjahr - 1) ? '<option value="'.$x.'" selected>'.$x.'</option>' : '<option value="'.$x.'">'.$x.'</option>';
			}
			$this->Template->form_vonjahr = implode("\n",$opArray);
			
			// Auswahl Bis-Jahr
			$opArray = array();
			for($x = 2011; $x <= $aktjahr; $x++)
			{
				$opArray[] = ($x == $aktjahr) ? '<option value="'.$x.'" selected>'.$x.'</option>' : '<option value="'.$x.'">'.$x.'</option>';
			}
			$this->Template->form_bisjahr = implode("\n",$opArray);
		}

	}
	
	function Vereinslose($Turnierheader, $Spieler)
	{
		// Statistik- und Ausgabedatei festlegen
		$statistikdatei = TL_ROOT.'/Vereinslose.dat';
		$ausgabedatei = TL_ROOT.'/Vereinslose.csv';
		// Statistikdatei einlesen
		if(file_exists($statistikdatei)) $daten = unserialize(file_get_contents($statistikdatei));
		else $daten = array();

		// Ausländisches Turnier?
		if(substr($Turnierheader['Turniercode'],5,1) == 'K') $ausland = true;
		else $ausland = false;
		
		if(!$ausland)
		{
			// Zähler anlegen
			$counter = array(0, 0);
			// Statistik anlegen, wenn noch nicht vorhanden
			if(!$daten[$Turnierheader['Turniercode']])
			{
				$daten[$Turnierheader['Turniercode']] = array();
			}

			// Vereinslose/Mitglieder zählen
			foreach($Spieler as $item)
			{
				if($item['DSB-Mitglied'])
				{
					// Spieler ist DSB-Mitglied
					$counter[0]++;
					log_message($Turnierheader['Turniername'].'|'.$item['DSB-Mitglied'].'|Mitglied','vereinslose.log');
				}
				else
				{
					// Spieler ist vereinslos
					$counter[1]++;
					log_message($Turnierheader['Turniername'].'|'.$item['DSB-Mitglied'].'|vereinslos','vereinslose.log');
				}
			}
			// Statistik (über)schreiben
			$daten[$Turnierheader['Turniercode']]['TURNIER'] = $this->is_utf8($Turnierheader['Turniername']) ? utf8_decode($Turnierheader['Turniername']) : $Turnierheader['Turniername'];
			$daten[$Turnierheader['Turniercode']]['JAHR'] = substr($Turnierheader['Turnierende'],6,4);
			$daten[$Turnierheader['Turniercode']]['MONAT'] = substr($Turnierheader['Turnierende'],3,2);
			$daten[$Turnierheader['Turniercode']]['TAG'] = substr($Turnierheader['Turnierende'],0,2);
			$daten[$Turnierheader['Turniercode']]['SPIELER'] = $Turnierheader['Spieler'];
			$daten[$Turnierheader['Turniercode']]['MITGLIEDER'] = $counter[0];
			$daten[$Turnierheader['Turniercode']]['VEREINSLOSE'] = $counter[1];

			// Statistikdatei überschreiben
			$fp = fopen($statistikdatei, 'w');
			fputs($fp, serialize($daten));
			fclose($fp);

			// Ausgabedatei überschreiben
			$fp = fopen($ausgabedatei, 'w');
			// Vereinslose/Mitglieder zählen
			$zeile = 0;
			foreach($daten as $key => $value)
			{
				if($zeile == 0) fputs($fp, "TURNIERCODE;TURNIER;JAHR;MONAT;TAG;SPIELER;MITGLIEDER;VEREINSLOSE\r\n");
				if($key)
				{
					fputs($fp, $key.";");
					fputs($fp, $value['TURNIER'].";");
					fputs($fp, $value['JAHR'].";");
					fputs($fp, $value['MONAT'].";");
					fputs($fp, $value['TAG'].";");
					fputs($fp, $value['SPIELER'].";");
					fputs($fp, $value['MITGLIEDER'].";");
					fputs($fp, $value['VEREINSLOSE']."\r\n");
				}
				$zeile++;
			}
			fclose($fp);
		}
	}

	function is_utf8($str)
	{
		$strlen = strlen($str);
		for($i=0; $i<$strlen; $i++)
		{
			$ord = ord($str[$i]);
			
			if($ord < 0x80) continue; // 0bbbbbbb
			elseif(($ord&0xE0)===0xC0 && $ord>0xC1) $n = 1; // 110bbbbb (exkl C0-C1)
			elseif(($ord&0xF0)===0xE0) $n = 2; // 1110bbbb
			elseif(($ord&0xF8)===0xF0 && $ord<0xF5) $n = 3; // 11110bbb (exkl F5-FF)
			else return false; // ungültiges UTF-8-Zeichen
			
			for($c=0; $c<$n; $c++) // $n Folgebytes? // 10bbbbbb
				if(++$i===$strlen || (ord($str[$i])&0xC0)!==0x80)
					return false; // ungültiges UTF-8-Zeichen
		}
		return true; // kein ungültiges UTF-8-Zeichen gefunden
	}

}
