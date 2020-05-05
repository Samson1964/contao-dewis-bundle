<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * @link http://www.contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 * 
 * Modul Banner - Check Helper 
 * 
 * PHP version 5
 * @copyright  Glen Langer 2007..2015
 * @author     Glen Langer
 * @package    Banner
 * @license    LGPL
 */


/**
 * Class BannerCheckHelper
 *
 * @copyright  Glen Langer 2015
 * @author     Glen Langer
 * @package    Banner
 */

namespace Schachbulle\ContaoDewisBundle\Helper;

class Helper extends \Frontend
{
	/**
	 * Current object instance
	 * @var object
	 */
	protected static $instance = null;

	var $user;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Benutzerdaten laden
		if(FE_USER_LOGGED_IN)
		{
			// Frontenduser eingeloggt
			$this->user = \FrontendUser::getInstance();
		}
		parent::__construct();
	}


	/**
	 * Return the current object instance (Singleton)
	 * @return BannerCheckHelper
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new \Schachbulle\ContaoDewisBundle\Helper\Helper();
		}
	
		return self::$instance;
	}

	/**
	 * Liefert den Alias der Spielerseite zurück
	 * @return         Alias
	 */
	public function getSpielerseite()
	{
		if($GLOBALS['TL_CONFIG']['dewis_seite_spieler'])
		{
			$pageModel = \PageModel::findByPK($GLOBALS['TL_CONFIG']['dewis_seite_spieler']);
		
			if($pageModel)
			{
				$url = \Controller::generateFrontendUrl($pageModel->row());
				return $url;
			}
		}

		return '';

	}

	/**
	 * Liefert den Alias der Turnierseite zurück
	 * @return         Alias
	 */
	public function getTurnierseite()
	{
		if($GLOBALS['TL_CONFIG']['dewis_seite_turnier'])
		{
			$pageModel = \PageModel::findByPK($GLOBALS['TL_CONFIG']['dewis_seite_turnier']);
		
			if($pageModel)
			{
				$url = \Controller::generateFrontendUrl($pageModel->row());
				return $url;
			}
		}

		return '';

	}

	/**
	 * Liefert den Alias der Vereinseite zurück
	 * @return         Alias
	 */
	public function getVereinseite()
	{
		if($GLOBALS['TL_CONFIG']['dewis_seite_verein'])
		{
			$pageModel = \PageModel::findByPK($GLOBALS['TL_CONFIG']['dewis_seite_verein']);
		
			if($pageModel)
			{
				$url = \Controller::generateFrontendUrl($pageModel->row());
				return $url;
			}
		}

		return '';

	}

	/**
	 * Liefert den Alias der Verbandseite zurück
	 * @return         Alias
	 */
	public function getVerbandseite()
	{
		if($GLOBALS['TL_CONFIG']['dewis_seite_verband'])
		{
			$pageModel = \PageModel::findByPK($GLOBALS['TL_CONFIG']['dewis_seite_verband']);
		
			if($pageModel)
			{
				$url = \Controller::generateFrontendUrl($pageModel->row());
				return $url;
			}
		}

		return '';

	}

	public function getMitglied()
	{
		//\Schachbulle\ContaoDewisBundle\Helper\DeWIS::debug(\FrontendUser::getInstance());
		return \FrontendUser::getInstance(); //$this->user;
	}

	/**
	 * Leitet auf die im System definierte 404-Seite weiter
	 */
	public function get404()
	{
		throw new \CoreBundle\Exception\PageNotFoundException('Page not found: '.\Environment::get('uri'));
	}

	/**
	 * Gibt die ID des Contao-Mitgliedes zurück, dem eine bestimmte DeWIS-ID zugewiesen ist
	 * @param id	ID in DeWIS
	 * @return		ID des Contao-Mitgliedes
	 */
	public function Karteizuweisung($id)
	{
		$objSpieler = \Database::getInstance()->prepare('SELECT contaoMemberID FROM tl_dwz_spi WHERE dewisID = ?')
		                                      ->limit(1) 
		                                      ->execute($id); 
		return $objSpieler->contaoMemberID;
	}

	/**
	 * Gibt den Status der Karteikartensperre für eine DeWIS-ID zurück
	 * @param id	ID in DeWIS
	 * @return		Karteikarte gesperrt true/false
	 */
	public function Karteisperre($id)
	{
		$objCheckUser = \Database::getInstance()->prepare('SELECT dewisCard FROM tl_member WHERE id=?')
		                                        ->execute($id); 
		return $objCheckUser->dewisCard;
	}

	/**
	 * Gibt die Navigation zurück
	 * @param 		-
	 * @return		Array mit den Links
	 */
	public function Navigation()
	{
		return array
		(
			'<li class="first"><a href="'.self::getSpielerseite().'">Spieler</a></li>',
			'<li class=""><a href="'.self::getVereinseite().'">Vereine</a></li>',
			'<li class=""><a href="'.self::getVerbandseite().'">Verbände</a></li>',
			'<li class="last"><a href="'.self::getTurnierseite().'">Turniere</a></li>',
		);
	}

	/**
	 * Prüft ob ein Jahr ein Schaltjahr ist und gibt entsprechend die Monatslängen zurück
	 * @param 		-
	 * @return		Array mit Anzahl Tage je Monat
	 */
	function Monatstage($jahr)
	{
		$monate = array
		(
			1 => 31,
			2 => 28,
			3 => 31,
			4 => 30,
			5 => 31,
			6 => 30,
			7 => 31,
			8 => 31,
			9 => 30,
			10 => 31,
			11 => 30,
			12 => 31
		);

		if(($jahr % 400) == 0 || (($jahr % 4) == 0 && ($jahr % 100) != 0))
		{
			// Schaltjahr
			$monate[2] = 29;
			return $monate;
		}
		else
		{
			// Kein Schaltjahr
			return $monate;
		}
	}

	public function datum_mysql2php($datum) 
	{
		return $datum ? substr($datum, 8, 2) . '.' . substr($datum, 5, 2) . '.' . substr($datum, 0, 4) : '';
	}

	/**
	 * Ersetzt in einem numerischen Array 0-Werte durch einen Mittelwert der Nachbarwerte
	 * @param 		Array
	 * @return		Array
	 */
	public function Mittelwerte($array)
	{
		
		$value = 0;
		$key = -1;
		for($x = 0; $x < count($array); $x++)
		{
			if($array[$x] > 0)
			{
				// Wert ungleich 0 gefunden, das ist der Nachfolgerwert
				// Anzahl 0-Werte davor ermitteln
				$teiler = $x - $key;
				if($teiler > 1)
				{
					// Nullwerte gefunden, ersetzen durch Mittelwerte
					// Mittelwertedifferenz ermitteln
					$mittelwert_differenz = sprintf('%d', ($value - $array[$x]) / $teiler);
					if($key == -1)
					{
						// Nullwerte am Anfang mit aktuellem Wert befüllen
						for($y = 0; $y < $x; $y++)
						{
							$array[$y] = $array[$x];
						}
					}
					else
					{
						// Nullwerte in der Arraymitte mit Differenz füllen
						for($y = $key + 1; $y < $x; $y++)
						{
							$array[$y] = $array[$y-1] - $mittelwert_differenz;
						}
					}
				}
				// Neue Vorgängerwerte setzen, aktuellen Wert benutzen
				$key = $x;
				$value = $array[$x];
			}
		}
		
		// Nullwerte am Arrayende auffüllen
		for($y = $key + 1; $y < count($array); $y++)
		{
			$array[$y] = $value;
		}

		return $array;
				
	}

	/**
	 * Setzt den Spielernamen zusammen mit einem Link zur Karteikarte
	 * @param 		Array
	 * @return		Array
	 */
	public function Spielername($t, $gesperrt)
	{
		return ($gesperrt) ? sprintf("%s,%s%s", $t->surname, $t->firstname, $t->title ? ',' . $t->title : '') : sprintf("<a href=\"".ALIAS_SPIELER."/%s.html\">%s</a>", $t->pid, sprintf("%s,%s%s", $t->surname, $t->firstname, $t->title ? ',' . $t->title : ''));
	}

	/**
	 * Schreibt Werte aus einem Array vom Array in ein neues Array
	 * @param 		Array
	 * 				Beispiel:
	 *				array(array('item'=>1,'val'=>2),array('item'=>3,'val'=>6))
	 * @param		String ($extract = 'item' oder 'val')
	 * @return		Array
	 */
	public function ArrayExtract($array, $extract)
	{
		//echo "<pre>";
		//echo count($array);
		//echo "</pre>";
		$newArr = array();
		foreach($array as $key => $value)
		{
			$newArr[] = $value[$extract];
		}
		return $newArr;
	}


	/**
	 * Überprüft den Suchbegriff für eine Spielersuche
	 * @param $search      Suchbegriff
	 * @return array       Array mit Typ und Vorname+Nachname und Vorname+Nachname gedreht
	 */
	public function checkSearchstringPlayer($search)
	{
		if (is_numeric($search)) $typ = 'pkz'; // Eine PKZ wurde übergeben
		elseif (strlen($search) == 10 && substr($search,5,1) == '-') $typ = 'zps'; // Eine ZPS wurde übergeben
		else
		{
			// Ein Name wurde übergeben, zuerst akademische Titel entfernen
			$search = str_replace(',Prof. Dr.','',$search);
			$search = str_replace(',Prof.Dr.','',$search);
			$search = str_replace(',Prof.','',$search);
			$search = str_replace(',Dr.','',$search);
			$search = str_replace('Prof. Dr. ','',$search);
			$search = str_replace('Prof.Dr. ','',$search);
			$search = str_replace('Prof. ','',$search);
			$search = str_replace('Dr. ','',$search);
			
			// Name am Komma trennen
			$typ = 'name';
			$strKomma = explode(',', $search);
			if ($strKomma[1])
			{
				// Suchbegriff entspricht Nachname, Vorname
				$nachname = trim($strKomma[0]);
				$vorname = trim($strKomma[1]);
			}
			else
			{
				$nachname = $search;
				$vorname = '';
				// Auf Leerzeichen als Trennzeichen überprüfen
				$strLeer = explode(' ', $search);
				if ($strLeer[1])
				{
					// Suchbegriff entspricht Vorname Nachname (wahrscheinlich)
					$nachname2 = trim($strLeer[1]);
					$vorname2 = trim($strLeer[0]);
				}
				else
				{
					// Suchbegriff entspricht Nachname (wahrscheinlich)
					$nachname2 = trim($search);
					$vorname2 = '';
				}
			}
		}

		return array
		(
			'typ'       => $typ,
			'vorname'   => $vorname,
			'vorname2'  => $vorname2,
			'nachname'  => $nachname,
			'nachname2' => $nachname2,
		);
	}

}