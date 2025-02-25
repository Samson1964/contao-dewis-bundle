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
	var $modul = '';
	var $value = '';
	var $limit = '';
	var $json = array();
	var $id = 0; // Datensatz aus API-Tabelle

	public function __construct()
	{
		// URL-Parameter speichern
		$this->token = (string)\Input::get('token');
		$this->modul = (string)\Input::get('modul');
		$this->value = (string)\Input::get('value');
		$this->limit = (string)\Input::get('limit');

		// Ausgabe-Array vorbereiten
		$this->json = array(
			'error'  => false,
			'status' => '',
			'data'   => array()
		);

		// Format der Ausgabe
		header("Access-Control-Allow-Origin: *");
		header("Content-type: application/json; charset=utf-8");

		// Token überprüfen
		if(self::checkToken())
		{
			// Token i.O, Modul aufrufen
			switch($this->modul)
			{
				case 'verein': self::Verein(); break;
				case 'verband': self::Verband(); break;
				case 'spieler': self::Spieler(); break;
				default:
					$this->json['status'] = 'Parameter modul fehlt/falsch';
					$this->json['error'] = true;
			}
		}
		else
		{
			$this->json['error'] = true;
		}

		self::setStatistik();
		// Ausgabe
		echo json_encode($this->json);
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
		$dewis = new \Schachbulle\ContaoDewisBundle\Helper\DeWIS();
		$daten = $dewis->autoQuery($param); // Abfrage ausführen

		if($daten['result'])
		{
			// Daten umwandeln und ausliefern
			$zaehler = 0;

			foreach($daten['result']->members as $m)
			{
				// Ausgabe für serialisiertes Array
				$this->json['data'][$zaehler]['id'] = $m->pid;
				$this->json['data'][$zaehler]['nachname'] = $m->surname;
				$this->json['data'][$zaehler]['vorname'] = $m->firstname;
				$this->json['data'][$zaehler]['titel'] = $m->title;
				$this->json['data'][$zaehler]['verein'] = $daten['result']->union->vkz;
				$this->json['data'][$zaehler]['mglnr'] = $m->membership;
				$this->json['data'][$zaehler]['status'] = $m->state;
				$this->json['data'][$zaehler]['dwz'] = $m->rating;
				$this->json['data'][$zaehler]['dwzindex'] = $m->ratingIndex;
				$this->json['data'][$zaehler]['turniercode'] = $m->tcode;
				$this->json['data'][$zaehler]['turnierende'] = $m->finishedOn;
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->json['data'][$zaehler]['geschlecht'] = $m->gender;
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->json['data'][$zaehler]['geburtsjahr'] = $m->yearOfBirth;
				$this->json['data'][$zaehler]['fideid'] = $m->idfide ? $m->idfide : '';
				$this->json['data'][$zaehler]['fideelo'] = $m->elo ? $m->elo : '';
				$this->json['data'][$zaehler]['fidetitel'] = $m->fideTitle ? $m->fideTitle : '';
				$zaehler++;
			}
		}
		return;
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
		$dewis = new \Schachbulle\ContaoDewisBundle\Helper\DeWIS();
		$daten = $dewis->autoQuery($param); // Abfrage ausführen

		if($daten['result'])
		{
			// Daten umwandeln und ausliefern
			$zaehler = 0;

			foreach($daten['result']->members as $m)
			{
				// Ausgabe für serialisiertes Array
				$this->json['data'][$zaehler]['id'] = $m->pid;
				$this->json['data'][$zaehler]['nachname'] = $m->surname;
				$this->json['data'][$zaehler]['vorname'] = $m->firstname;
				$this->json['data'][$zaehler]['titel'] = $m->title;
				$this->json['data'][$zaehler]['verein'] = $m->vkz;
				$this->json['data'][$zaehler]['mglnr'] = $m->membership;
				$this->json['data'][$zaehler]['status'] = $m->state;
				$this->json['data'][$zaehler]['dwz'] = $m->rating;
				$this->json['data'][$zaehler]['dwzindex'] = $m->ratingIndex;
				$this->json['data'][$zaehler]['turniercode'] = $m->tcode;
				$this->json['data'][$zaehler]['turnierende'] = $m->finishedOn;
				if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->json['data'][$zaehler]['geschlecht'] = $m->gender;
				if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->json['data'][$zaehler]['geburtsjahr'] = $m->yearOfBirth;
				$this->json['data'][$zaehler]['fideid'] = $m->idfide ? $m->idfide : '';
				$this->json['data'][$zaehler]['fideelo'] = $m->elo ? $m->elo : '';
				$this->json['data'][$zaehler]['fidetitel'] = $m->fideTitle ? $m->fideTitle : '';
				$zaehler++;
			}
		}
		return;
	}

	public function Spieler()
	{
		// Spielerkartei anzeigen
		$param = array
		(
			'funktion' => 'Karteikarte',
			'id'       => $this->value,
		);
		$dewis = new \Schachbulle\ContaoDewisBundle\Helper\DeWIS();
		$daten = $dewis->autoQuery($param); // Abfrage ausführen

		if($daten['result'])
		{

			// Daten umwandeln und ausliefern
			$zaehler = 0;

			// Array
			$this->json['data']['spieler']['id'] = $daten['result']->member->pid;
			$this->json['data']['spieler']['nachname'] = $daten['result']->member->surname;
			$this->json['data']['spieler']['vorname'] = $daten['result']->member->firstname;
			$this->json['data']['spieler']['titel'] = $daten['result']->member->title;
			$this->json['data']['spieler']['dwz'] = $daten['result']->member->rating;
			$this->json['data']['spieler']['dwzindex'] = $daten['result']->member->ratingIndex;
			if(!$GLOBALS['TL_CONFIG']['dewis_geschlecht_ausblenden']) $this->json['data']['spieler']['geschlecht'] = $daten['result']->member->gender;
			if(!$GLOBALS['TL_CONFIG']['dewis_geburtsjahr_ausblenden']) $this->json['data']['spieler']['geburtstag'] = $daten['result']->member->yearOfBirth;
			$this->json['data']['spieler']['fideid'] = $daten['result']->member->idfide;
			$this->json['data']['spieler']['fideelo'] = $daten['result']->member->elo;
			$this->json['data']['spieler']['fidetitel'] = $daten['result']->member->fideTitle;
			$this->json['data']['spieler']['fidenation'] = $daten['result']->member->fideNation;

			// Ranglistenplazierungen
			$x = 0;
			foreach($daten['result']->ranking[1] as $r)
			{
				$this->json['data']['rang'][$x]['zpsver'] = $r->vkz;
				$this->json['data']['rang'][$x]['organisation'] = $r->organization;
				$this->json['data']['rang'][$x]['rang'] = $r->rank;
				$this->json['data']['rang'][$x]['auswerter'] = $r->assessor;
				$x++;
			}

			// Mitgliedschaften
			$x = 0;
			foreach ($daten['result']->memberships as $r)
			{
				$this->json['data']['mitgliedschaft'][$x]['zpsver'] = $r->vkz;
				$this->json['data']['mitgliedschaft'][$x]['vereinsname'] = $r->club;
				$this->json['data']['mitgliedschaft'][$x]['zpsmgl'] = $r->membership;
				$this->json['data']['mitgliedschaft'][$x]['status'] = $r->state;
				$x++;
			}

			// Turniere
			$x = 0;
			foreach($daten['result']->tournaments as $r)
			{
				$this->json['data']['turnier'][$x]['turniercode'] = $r->tcode;
				$this->json['data']['turnier'][$x]['turniername'] = $r->tname;
				$this->json['data']['turnier'][$x]['dwzalt'] = $r->ratingOld;
				$this->json['data']['turnier'][$x]['dwzaltindex'] = $r->ratingOldIndex;
				$this->json['data']['turnier'][$x]['punkte'] = $r->points;
				$this->json['data']['turnier'][$x]['partien'] = $r->games;
				$this->json['data']['turnier'][$x]['ungewertet'] = $r->unratedGames;
				$this->json['data']['turnier'][$x]['erwartungswert'] = $r->we;
				$this->json['data']['turnier'][$x]['gegner'] = $r->level;
				$this->json['data']['turnier'][$x]['koeffizient'] = $r->eCoefficient;
				$this->json['data']['turnier'][$x]['dwzneu'] = $r->ratingNew;
				$this->json['data']['turnier'][$x]['dwzneuindex'] = $r->ratingNewIndex;
				$this->json['data']['turnier'][$x]['leistung'] = $r->achievement;
				$x++;
			}
		}
		return;
	}

	/**
	 * Statistik aktualisieren
	 */
	public function setStatistik()
	{
		// Statistik laden
		$objRecord = \Database::getInstance()->prepare('SELECT * FROM tl_dwz_api WHERE id=?')
		                                     ->execute($this->id);
		if($objRecord->numRows > 0)
		{
			// Statistik aktualisieren
			$statistik = @unserialize($objRecord->hits);
			$statistik[] = array(
				'datum'  => time(),
				'fehler' => $this->json['error'],
				'status' => $this->json['status'],
				'modul'  => $this->modul,
				'ip'     => $_SERVER['REMOTE_ADDR'],
			);
			$set = serialize($statistik);
			$objSave = \Database::getInstance()->prepare('UPDATE tl_dwz_api SET hits=? WHERE id=?')
			                                   ->execute($set, $this->id);
		}
	}

	/**
	 * Überprüfung des Tokens
	 * @return:          true/false
	 */
	public function checkToken()
	{
		// In API-Tabelle nach Token suchen
		$objRecord = \Database::getInstance()->prepare('SELECT * FROM tl_dwz_api WHERE `key`=?')
		                                     ->execute($this->token);
		if($objRecord->numRows > 0)
		{
			$this->id = $objRecord->id;
			$zeit = time();
			
			// Zugriff in diesem Zeitraum erlaubt?
			if($objRecord->published)
			{
				if($objRecord->start && $objRecord->start > $zeit)
				{
					// API-Schlüssel noch nicht gestartet
					$this->json['status'] = 'API-Schlüssel noch nicht gültig';
					return false;
				}
				if($objRecord->stop && $objRecord->stop < $zeit)
				{
					// API-Schlüssel wurde beendet
					$this->json['status'] = 'API-Schlüssel nicht mehr gültig';
					return false;
				}
			}
			else
			{
				// API-Schlüssel wurde nicht veröffentlicht
				$this->json['status'] = 'API-Schlüssel nicht veröffentlicht';
				return false;
			}

			// Token vorhanden, jetzt IP-Adresse prüfen
			$ip = $_SERVER['REMOTE_ADDR'];
			if($objRecord->ip == $ip)
			{
				// Modul prüfen
				$module = @unserialize($objRecord->modules);
				if(is_array($module))
				{
					if($this->modul)
					{
						if(in_array($this->modul, $module))
						{
							return true;
						}
						else
						{
							$this->json['status'] = 'Modul '.$this->modul.' nicht erlaubt';
							return false;
						}
					}
					else
					{
						$this->json['status'] = 'Modul nicht gefunden';
						return false;
					}
				}
				else
				{
					$this->json['status'] = 'Kein Modul erlaubt';
					return false;
				}
			}
			else
			{
				$this->json['status'] = 'Ungültige IP-Adresse: '.$ip;
				return false;
			}
		}
		else
		{
			// Token nicht gefunden
			$this->json['status'] = 'Ungültiger API-Schlüssel (Token)';
			return false;
		}
	}

}

/**
 * Controller instanzieren
 */
$objApi = new api();
