<?php
/*
 * ====================================================================================
 * Klasse api
 * Vereint die Funktionen der alten, ausgelagerten API-Dateien
 * vereine.php, verband.php und spieler.php
 * ====================================================================================
 */

/**
 * Contao Open Source CMS, Copyright (C) 2005-2024 Leo Feyer
 */
namespace Schachbulle\ContaoDewisBundle\Api;

use Contao\Controller;

/**
 * Contao-System initialisieren
 */
define('TL_MODE', 'FE');
define('TL_SCRIPT', 'bundles/contaodewis/api.php');
require($_SERVER['DOCUMENT_ROOT'].'/../system/initialize.php');

class api
{

	var $token = '';
	var $function = '';
	var $value = '';
	var $format = '';
	var $limit = '';
	var $ausgabe = array();

	public function __construct()
	{
		$this->token = \Input::get('token');
		$this->modul = \Input::get('modul');
		$this->value = \Input::get('value');
		$this->format = \Input::get('format');
		$this->limit = \Input::get('limit');

		header("Access-Control-Allow-Origin: *");

		switch($this->modul)
		{
			case 'verein': self::Verein(); break;
			case 'verband': self::Verband(); break;
			case 'spieler': self::Spieler(); break;
			default: self::Fehler();
		}

		switch($this->format)
		{
			case 'array':
				header("Content-type: application/php-serialized; charset=utf-8");
				echo $this->ausgabe['array'];
				break;
			case 'csv':
				header("Content-type: text/csv; charset=utf-8");
				echo $this->ausgabe['csv'];
				break;
			case 'xml':
				header("Content-type: application/xml; charset=utf-8");
				echo $this->ausgabe['xml'];
				break;
			case 'json':
				header("Content-type: application/json; charset=utf-8");
				echo $this->ausgabe['json'];
				break;
			default:
				header("Content-type: application/json; charset=utf-8");
				echo $this->ausgabe['json'];
		}
		return;
	}

	public function Verein()
	{
		// Vereinsliste anzeigen
		$param = array
		(
			'funktion' => 'Vereinsliste',
			'zps'      => $this->value,
		);
		$daten = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
		if($daten['result'])
		{
			//echo "<pre>";
			//print_r($daten);
			//echo "</pre>";

			// Daten umwandeln und ausliefern
			$zaehler = 0;
			// XML-Kopf
			$dom = new \DOMDocument('1.0','utf-8');
			$root = $dom->createElement('Liste');
			$dom->appendChild($root);
			// CSV-Kopf
			$this->ausgabe['csv'] = 'id|nachname|vorname|titel|verein|mglnr|status|dwz|dwzindex|turniercode|turnierende';
			if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['csv'] .= '|geschlecht';
			if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['csv'] .= '|geburtsjahr';
			$this->ausgabe['csv'] .= '|fideid|fideelo|fidetitel'."\r\n";

			foreach($daten['result']->members as $m)
			{
				// Ausgabe für serialisiertes Array
				$this->ausgabe['array'][$zaehler]['id'] = $m->pid;
				$this->ausgabe['array'][$zaehler]['nachname'] = $m->surname;
				$this->ausgabe['array'][$zaehler]['vorname'] = $m->firstname;
				$this->ausgabe['array'][$zaehler]['titel'] = $m->title;
				$this->ausgabe['array'][$zaehler]['verein'] = $daten['result']->union->vkz;
				$this->ausgabe['array'][$zaehler]['mglnr'] = $m->membership;
				$this->ausgabe['array'][$zaehler]['status'] = $m->state;
				$this->ausgabe['array'][$zaehler]['dwz'] = $m->rating;
				$this->ausgabe['array'][$zaehler]['dwzindex'] = $m->ratingIndex;
				$this->ausgabe['array'][$zaehler]['turniercode'] = $m->tcode;
				$this->ausgabe['array'][$zaehler]['turnierende'] = $m->finishedOn;
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['array'][$zaehler]['geschlecht'] = $m->gender;
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['array'][$zaehler]['geburtsjahr'] = $m->yearOfBirth;
				$this->ausgabe['array'][$zaehler]['fideid'] = $m->idfide ? $m->idfide : '';
				$this->ausgabe['array'][$zaehler]['fideelo'] = $m->elo ? $m->elo : '';
				$this->ausgabe['array'][$zaehler]['fidetitel'] = $m->fideTitle ? $m->fideTitle : '';
				// XML-Ausgabe
				$root->appendChild($firstNode = $dom->createElement('Spieler'));
				//$firstNode->setAttribute('Index',$zaehler);
				$firstNode->appendChild($dom->createElement('id',$m->pid));
				$firstNode->appendChild($dom->createElement('nachname',$m->surname));
				$firstNode->appendChild($dom->createElement('vorname',$m->firstname));
				$firstNode->appendChild($dom->createElement('titel',$m->title));
				$firstNode->appendChild($dom->createElement('verein',$daten['result']->union->vkz));
				$firstNode->appendChild($dom->createElement('mglnr',$m->membership));
				$firstNode->appendChild($dom->createElement('status',$m->state));
				$firstNode->appendChild($dom->createElement('dwz',$m->rating));
				$firstNode->appendChild($dom->createElement('dwzindex',$m->ratingIndex));
				$firstNode->appendChild($dom->createElement('turniercode',$m->tcode));
				$firstNode->appendChild($dom->createElement('turnierende',$m->finishedOn));
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $firstNode->appendChild($dom->createElement('geschlecht',$m->gender));
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $firstNode->appendChild($dom->createElement('geburtsjahr',$m->yearOfBirth));
				$firstNode->appendChild($dom->createElement('fideid',$m->idfide));
				$firstNode->appendChild($dom->createElement('fideelo',$m->elo));
				$firstNode->appendChild($dom->createElement('fidetitel',$m->fideTitle));
				// CSV-Ausgabe
				$this->ausgabe['csv'] .= $m->pid;
				$this->ausgabe['csv'] .= '|'.$m->surname;
				$this->ausgabe['csv'] .= '|'.$m->firstname;
				$this->ausgabe['csv'] .= '|'.$m->title;
				$this->ausgabe['csv'] .= '|'.$daten['result']->union->vkz;
				$this->ausgabe['csv'] .= '|'.$m->membership;
				$this->ausgabe['csv'] .= '|'.$m->state;
				$this->ausgabe['csv'] .= '|'.$m->rating;
				$this->ausgabe['csv'] .= '|'.$m->ratingIndex;
				$this->ausgabe['csv'] .= '|'.$m->tcode;
				$this->ausgabe['csv'] .= '|'.$m->finishedOn;
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['csv'] .= '|'.$m->gender;
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['csv'] .= '|'.$m->yearOfBirth;
				$this->ausgabe['csv'] .= '|'.$m->idfide;
				$this->ausgabe['csv'] .= '|'.$m->elo;
				$this->ausgabe['csv'] .= '|'.$m->fideTitle."\r\n";
				$zaehler++;
			}
		}
		$this->ausgabe['xml'] = $dom->saveXML();
		$this->ausgabe['json'] = json_encode($this->ausgabe['array']);
		$this->ausgabe['array'] = serialize($this->ausgabe['array']);
	}

	public function Verband()
	{
		// Verbandsliste anzeigen
		$param = array
		(
			'funktion' => 'Verbandsliste',
			'zps'      => $this->value,
			'limit'    => $this->limit ? $this->limit : 100,
		);
		$daten = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
		if($daten['result'])
		{
			//echo "<pre>";
			//print_r($daten);
			//echo "</pre>";

			// Daten umwandeln und ausliefern
			$zaehler = 0;
			// XML-Kopf
			$dom = new \DOMDocument('1.0','utf-8');
			$root = $dom->createElement('Liste');
			$dom->appendChild($root);
			// CSV-Kopf
			$this->ausgabe['csv'] = 'id|nachname|vorname|titel|verein|mglnr|status|dwz|dwzindex|turniercode|turnierende';
			if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['csv'] .= '|geschlecht';
			if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['csv'] .= '|geburtsjahr';
			$this->ausgabe['csv'] .= '|fideid|fideelo|fidetitel\r\n';
			
			foreach($daten['result']->members as $m) 
			{
				// Ausgabe für serialisiertes Array
				$this->ausgabe['array'][$zaehler]['id'] = $m->pid;
				$this->ausgabe['array'][$zaehler]['nachname'] = $m->surname;
				$this->ausgabe['array'][$zaehler]['vorname'] = $m->firstname;
				$this->ausgabe['array'][$zaehler]['titel'] = $m->title;
				$this->ausgabe['array'][$zaehler]['verein'] = $m->vkz;
				$this->ausgabe['array'][$zaehler]['mglnr'] = $m->membership;
				$this->ausgabe['array'][$zaehler]['status'] = $m->state;
				$this->ausgabe['array'][$zaehler]['dwz'] = $m->rating;
				$this->ausgabe['array'][$zaehler]['dwzindex'] = $m->ratingIndex;
				$this->ausgabe['array'][$zaehler]['turniercode'] = $m->tcode;
				$this->ausgabe['array'][$zaehler]['turnierende'] = $m->finishedOn;
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['array'][$zaehler]['geschlecht'] = $m->gender;
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['array'][$zaehler]['geburtsjahr'] = $m->yearOfBirth;
				$this->ausgabe['array'][$zaehler]['fideid'] = $m->idfide ? $m->idfide : '';
				$this->ausgabe['array'][$zaehler]['fideelo'] = $m->elo ? $m->elo : '';
				$this->ausgabe['array'][$zaehler]['fidetitel'] = $m->fideTitle ? $m->fideTitle : '';
				// XML-Ausgabe
				$root->appendChild($firstNode = $dom->createElement('Spieler'));
				//$firstNode->setAttribute('Index',$zaehler);
				$firstNode->appendChild($dom->createElement('id',$m->pid));
				$firstNode->appendChild($dom->createElement('nachname',$m->surname));
				$firstNode->appendChild($dom->createElement('vorname',$m->firstname));
				$firstNode->appendChild($dom->createElement('titel',$m->title));
				$firstNode->appendChild($dom->createElement('verein',$m->vkz));
				$firstNode->appendChild($dom->createElement('mglnr',$m->membership));
				$firstNode->appendChild($dom->createElement('status',$m->state));
				$firstNode->appendChild($dom->createElement('dwz',$m->rating));
				$firstNode->appendChild($dom->createElement('dwzindex',$m->ratingIndex));
				$firstNode->appendChild($dom->createElement('turniercode',$m->tcode));
				$firstNode->appendChild($dom->createElement('turnierende',$m->finishedOn));
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $firstNode->appendChild($dom->createElement('geschlecht',$m->gender));
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $firstNode->appendChild($dom->createElement('geburtsjahr',$m->yearOfBirth));
				$firstNode->appendChild($dom->createElement('fideid',$m->idfide));
				$firstNode->appendChild($dom->createElement('fideelo',$m->elo));
				$firstNode->appendChild($dom->createElement('fidetitel',$m->fideTitle));
				// CSV-Ausgabe
				$this->ausgabe['csv'] .= $m->pid;
				$this->ausgabe['csv'] .= '|'.$m->surname;
				$this->ausgabe['csv'] .= '|'.$m->firstname;
				$this->ausgabe['csv'] .= '|'.$m->title;
				$this->ausgabe['csv'] .= '|'.$m->vkz;
				$this->ausgabe['csv'] .= '|'.$m->membership;
				$this->ausgabe['csv'] .= '|'.$m->state;
				$this->ausgabe['csv'] .= '|'.$m->rating;
				$this->ausgabe['csv'] .= '|'.$m->ratingIndex;
				$this->ausgabe['csv'] .= '|'.$m->tcode;
				$this->ausgabe['csv'] .= '|'.$m->finishedOn;
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['csv'] .= '|'.$m->gender;
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['csv'] .= '|'.$m->yearOfBirth;
				$this->ausgabe['csv'] .= '|'.$m->idfide;
				$this->ausgabe['csv'] .= '|'.$m->elo;
				$this->ausgabe['csv'] .= '|'.$m->fideTitle.'\r\n';
				$zaehler++;
			}
		}
		$this->ausgabe['xml'] = $dom->saveXML();
		$this->ausgabe['json'] = json_encode($this->ausgabe['array']);
		$this->ausgabe['array'] = serialize($this->ausgabe['array']);
	}

	public function Spieler()
	{
		// Spielerkartei anzeigen
		$param = array
		(
			'funktion' => 'Karteikarte',
			'id'       => $this->value,
		);
		$daten = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
		//echo "<pre>";
		//print_r($daten);
		//echo "</pre>";
		if($daten['result'])
		{

			// Daten umwandeln und ausliefern
			$zaehler = 0;
			// XML-Kopf
			$dom = new \DOMDocument("1.0","utf-8");
			$root = $dom->createElement("Liste");
			$dom->appendChild($root);
			// CSV-Kopf
			$this->ausgabe['csv'] = 'id|nachname|vorname|titel|dwz|dwzindex';
			if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['csv'] .= '|geschlecht';
			if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['csv'] .= '|geburtsjahr';
			$this->ausgabe['csv'] .= '|fideid|fideelo|fidetitel|fidenation\r\n';
			
			// CSV
			$this->ausgabe['csv'] .= $daten['result']->member->pid.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->surname.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->firstname.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->title.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->rating.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->ratingIndex.'|';
			if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['csv'] .= $daten['result']->member->gender.'|';
			if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['csv'] .= $daten['result']->member->yearOfBirth.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->idfide.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->elo.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->fideTitle.'|';
			$this->ausgabe['csv'] .= $daten['result']->member->fideNation.'\r\n';
			
			// Array
			$this->ausgabe['array']['spieler']['id'] = $daten['result']->member->pid;
			$this->ausgabe['array']['spieler']['nachname'] = $daten['result']->member->surname;
			$this->ausgabe['array']['spieler']['vorname'] = $daten['result']->member->firstname;
			$this->ausgabe['array']['spieler']['titel'] = $daten['result']->member->title;
			$this->ausgabe['array']['spieler']['dwz'] = $daten['result']->member->rating;
			$this->ausgabe['array']['spieler']['dwzindex'] = $daten['result']->member->ratingIndex;
			if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->ausgabe['array']['spieler']['geschlecht'] = $daten['result']->member->gender;
			if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->ausgabe['array']['spieler']['geburtstag'] = $daten['result']->member->yearOfBirth;
			$this->ausgabe['array']['spieler']['fideid'] = $daten['result']->member->idfide;
			$this->ausgabe['array']['spieler']['fideelo'] = $daten['result']->member->elo;
			$this->ausgabe['array']['spieler']['fidetitel'] = $daten['result']->member->fideTitle;
			$this->ausgabe['array']['spieler']['fidenation'] = $daten['result']->member->fideNation;
			
			// Ranglistenplazierungen
			$this->ausgabe['csv'] .= 'zpsver|organisation|rang|auswerter\r\n';
			$x = 0;
			foreach($daten['result']->ranking[1] as $r)
			{
				$this->ausgabe['csv'] .= $r->vkz.'|';
				$this->ausgabe['csv'] .= $r->organization.'|';
				$this->ausgabe['csv'] .= $r->rank.'|';
				$this->ausgabe['csv'] .= $r->assessor.'\r\n';
				$this->ausgabe['array']['rang'][$x]['zpsver'] = $r->vkz;
				$this->ausgabe['array']['rang'][$x]['organisation'] = $r->organization;
				$this->ausgabe['array']['rang'][$x]['rang'] = $r->rank;
				$this->ausgabe['array']['rang'][$x]['auswerter'] = $r->assessor;
				$x++;
			}
			
			// Mitgliedschaften
			$this->ausgabe['csv'] .= 'zpsver|vereinsname|zpsmgl|status\r\n';
			$x = 0;
			foreach ($daten['result']->memberships as $r)
			{
				$this->ausgabe['csv'] .= $r->vkz.'|';
				$this->ausgabe['csv'] .= $r->club.'|';
				$this->ausgabe['csv'] .= $r->membership.'|';
				$this->ausgabe['csv'] .= $r->state.'\r\n';
				$this->ausgabe['array']['mitgliedschaft'][$x]['zpsver'] = $r->vkz;
				$this->ausgabe['array']['mitgliedschaft'][$x]['vereinsname'] = $r->club;
				$this->ausgabe['array']['mitgliedschaft'][$x]['zpsmgl'] = $r->membership;
				$this->ausgabe['array']['mitgliedschaft'][$x]['status'] = $r->state;
				$x++;
			}
			
			// Turniere
			$this->ausgabe['csv'] .= 'turniercode|turniername|dwzalt|dwzaltindex|punkte|partien|nichtgewertet|erwartungswert|gegner|koeffizient|dwzneu|dwzneuindex|leistung\r\n';
			$x = 0;
			foreach($daten['result']->tournaments as $r)
			{
				$this->ausgabe['csv'] .= $r->tcode.'|';
				$this->ausgabe['csv'] .= $r->tname.'|';
				$this->ausgabe['csv'] .= $r->ratingOld.'|';
				$this->ausgabe['csv'] .= $r->ratingOldIndex.'|';
				$this->ausgabe['csv'] .= $r->points.'|';
				$this->ausgabe['csv'] .= $r->games.'|';
				$this->ausgabe['csv'] .= $r->unratedGames.'|';
				$this->ausgabe['csv'] .= $r->we.'|';
				$this->ausgabe['csv'] .= $r->level.'|';
				$this->ausgabe['csv'] .= $r->eCoefficient.'|';
				$this->ausgabe['csv'] .= $r->ratingNew.'|';
				$this->ausgabe['csv'] .= $r->ratingNewIndex.'|';
				$this->ausgabe['csv'] .= $r->achievement.'\r\n';
				$this->ausgabe['array']['turnier'][$x]['turniercode'] = $r->tcode;
				$this->ausgabe['array']['turnier'][$x]['turniername'] = $r->tname;
				$this->ausgabe['array']['turnier'][$x]['dwzalt'] = $r->ratingOld;
				$this->ausgabe['array']['turnier'][$x]['dwzaltindex'] = $r->ratingOldIndex;
				$this->ausgabe['array']['turnier'][$x]['punkte'] = $r->points;
				$this->ausgabe['array']['turnier'][$x]['partien'] = $r->games;
				$this->ausgabe['array']['turnier'][$x]['ungewertet'] = $r->unratedGames;
				$this->ausgabe['array']['turnier'][$x]['erwartungswert'] = $r->we;
				$this->ausgabe['array']['turnier'][$x]['gegner'] = $r->level;
				$this->ausgabe['array']['turnier'][$x]['koeffizient'] = $r->eCoefficient;
				$this->ausgabe['array']['turnier'][$x]['dwzneu'] = $r->ratingNew;
				$this->ausgabe['array']['turnier'][$x]['dwzneuindex'] = $r->ratingNewIndex;
				$this->ausgabe['array']['turnier'][$x]['leistung'] = $r->achievement;
				$x++;
			}
		}
		//$this->ausgabe['xml'] = $dom->saveXML();
		$this->ausgabe['json'] = json_encode($this->ausgabe['array']);
		$this->ausgabe['array'] = serialize($this->ausgabe['array']);
	}

	public function Fehler()
	{
		// Spielersuche
		echo 'Ungültige Parameter';
		exit();
	}
}

/**
 * Controller instanzieren
 */
$objApi = new api();
