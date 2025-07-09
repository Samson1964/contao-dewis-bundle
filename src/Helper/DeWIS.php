<?php

namespace Schachbulle\ContaoDewisBundle\Helper;

class DeWIS
{

	/**
	 * Current object instance
	 * @var object
	 */
	protected static $instance = null;

	var $Fragmente;
	static $answer;
	static $answertime;
	static $error;
	static $errorcode;

	/**
	 * Klasse initialisieren
	 */
	public function __construct()
	{
		self::$answertime = false; // Antwortzeit des Servers
		$this->Fragmente = '';
	}


	/**
	 * Return the current object instance (Singleton)
	 * @return BannerCheckHelper
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new \Schachbulle\ContaoDewisBundle\Helper\DeWIS();
		}

		return self::$instance;
	}


	/*********************************************************
	 * autoQuery
	 * =========
	 * Vollautomatisierte Abfrage von DeWIS inkl. Cachenutzung
	 *
	 * @param       Array mit den Parametern
	 * $param = array
	 * (
	 * 	"funktion" => "Spielerliste", // DeWIS-Funktion/Cachename
	 * 	"cachekey" => "Cacheschlüssel", // Name des Datensatzes im Cache
	 * 	"vorname"  => $vorname, // definierbar anhand DeWIS-Funktion
	 * );
	 * @return      Array mit den Rückgabewerten
	*/
	public static function autoQuery($params)
	{
		// Cache nur berücksichtigen, wenn nocache-Parameter nicht true ist
		if($GLOBALS['TL_CONFIG']['dewis_cache'] || $params['cachetime'])
		{
			// Cache initialisieren
			$cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache(array('name' => $params['funktion'], 'extension' => '.cache'));
			$cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen

			// Cache laden
			if($cache->isCached($params['cachekey']) && !isset($params['nocache']))
			{
				$result = $cache->retrieve($params['cachekey']);
			}
			// Cachezeiten modifizieren
			switch($params['funktion'])
			{
				case 'Verbaende':
					$cachetime = 3600 * $GLOBALS['TL_CONFIG']['dewis_cache_verband'];
					break;
				case 'Wertungsreferent':
					$cachetime = 3600 * $GLOBALS['TL_CONFIG']['dewis_cache_referent'];
					break;
				default:
					$cachetime = 3600;
			}
			if(isset($params['cachetime'])) $cachetime = $params['cachetime'];
		}

		// DeWIS-Abfrage, wenn Cache leer
		if(!isset($result))
		{
			// Abfrage DeWIS
			$tstart = microtime(true);
			$result = self::Abfrage($params);
			$tende = microtime(true) - $tstart;
			$querytime = sprintf("%1.3f", $tende);
			$cachemode = false;

			// Elo optional aus lokaler Quelle laden
			$result = self::ModifiziereElo($result, $params);
			// DeWIS-Daten in Contao-Datenbank aktualisieren
			self::AktualisiereDWZTabellen($result, $params);

			// im Cache speichern
			if(isset($GLOBALS['TL_CONFIG']['dewis_cache']) == true || $params['cachetime']) $cache->store($params['cachekey'], $result, $cachetime);
			if(isset($GLOBALS['DeWIS-Cache']['dewis-queries'])) $GLOBALS['DeWIS-Cache']['dewis-queries']++;
			else $GLOBALS['DeWIS-Cache']['dewis-queries'] = 1;
			if(isset($GLOBALS['DeWIS-Cache']['dewis-queriestimes'])) $GLOBALS['DeWIS-Cache']['dewis-queriestimes'] += $querytime;
			else $GLOBALS['DeWIS-Cache']['dewis-queriestimes'] = $querytime;
			$GLOBALS['DeWIS-Cache']['cache-queries'] = 0;
			//echo $params['funktion'];
		}
		else
		{
			// Cache-Modus
			$querytime = false;
			$cachemode = true;
			if(isset($GLOBALS['DeWIS-Cache']['cache-queries'])) $GLOBALS['DeWIS-Cache']['cache-queries']++;
			else $GLOBALS['DeWIS-Cache']['cache-queries'] = 1;
			$GLOBALS['DeWIS-Cache']['dewis-queries'] = 0;
			$GLOBALS['DeWIS-Cache']['dewis-queriestimes'] = 0;
		}

		return array
		(
			'result'     => $result,
			'querytime'  => $querytime,
			'cachemode'  => $cachemode
		);
	}


	/*
	* Status der Geburtsjahranzeige setzen
	*
	* @param boolean $status         1 = anzeigen, 0 = nicht anzeigen
	*/
	public static function Abfrage($parameter)
	{
		try
		{
			// Gustaf Mossakowski am 06.05.2018:
			// svw.info ist generell aus vielen Browsern nicht mehr erreichbar.
			// Die Symantec-Zerfikate werden nicht mehr als sicher angesehen.
			// https://blog.qualys.com/ssllabs/2017/09/26/google-and-mozilla-deprecating-existing-symantec-certificates
			// https://www.ssllabs.com/ssltest/analyze.html?d=dwz.svw.info
			// Ein temporärer Ausweg bis zur Installation neuer Zertifikate auf svw.info könnte sein (Parameter stream_context):
			$context = stream_context_create([
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
				]
			]);

			$time_start = microtime(true);
			$client = new \SOAPClient(
				NULL,
				array(
					'location'           => 'https://dwz.svw.info/services/soap/index.php',
					'uri'                => 'https://soap',
					'style'              => SOAP_RPC,
					'use'                => SOAP_ENCODED,
					'connection_timeout' => 15,
					'stream_context'     => $context // Entfernt am 21.02.2019 da svw.info meldete: Error Fetching http body, No Content-Length, connection closed or chunked data
					// Wieder aktiviert am 23.03.2021 weil die Schnittstelle meldete: Could not connect to host
				)
			);

			switch($parameter["funktion"])
			{
				case "Spielerliste": // Spielerliste einer Suche
					// vorname = Vorname des Spielers, default = leer
					// nachname = Nachname des Spielers
					// limit = Anzahl der Ergebnisse
					$tstart = microtime(true);
					self::$answer = $client->searchByName($parameter['nachname'],$parameter['vorname'],0,$parameter['limit']);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Karteikarte": // Karteikarte nach ID
					// id = ID des Spielers
					$tstart = microtime(true);
					self::$answer = $client->tournamentCardForId($parameter["id"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "KarteikarteZPS": // Karteikarte nach ZPS
					// zps = Mitgliedsnummer des Spielers
					$tstart = microtime(true);
					self::$answer = $client->tournamentCardForZps($parameter["zps"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Wertungsreferent": // Adresse zur ID eines Wertungsreferenten
					$tstart = microtime(true);
					self::$answer = $client->ratingOfficer($parameter["id"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Vereinsliste": // Spielerliste eines Vereins
					// zps = fünfstellig
					$tstart = microtime(true);
					self::$answer = $client->unionRatingList($parameter["zps"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Verbandsliste": // Bestenliste eines Verbands
					// zps = ein- bis fünfstellig
					// limit = Anzahl der Plätze (<=1000)
					// alter_von = Alter von (>=0)
					// alter_bis = Alter bis (>=0 && <=140)
					// geschlecht ('m', 'f', '')
					if($parameter["zps"] == '000') $parameter["zps"] = '00000';
					$tstart = microtime(true);
					self::$answer = $client->bestOfFederation($parameter["zps"],$parameter["limit"],$parameter["alter_von"],$parameter["alter_bis"],$parameter["geschlecht"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Turnierliste": // Turnierliste
					// von = Datum von als Unixzeit
					// bis = Datum bis als Unixzeit
					// zps = ein- bis dreistellig
					// suche = Suchbegriff (Turniername)
					// von = Datum im Format JJJJ-MM-TT
					// bis = Datum im Format JJJJ-MM-TT
					// von/bis muß im gleichen Jahr liegen!
					$tstart = microtime(true);
					self::$answer = $client->tournamentsByPeriod($parameter["von"],$parameter["bis"],$parameter["zps"],true,"",$parameter["suche"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Turnierauswertung": // Auswertung eines Turniers
					// code = Turniercode, z.B. B148-C12-SLG
					$tstart = microtime(true);
					self::$answer = $client->tournament($parameter["code"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Turnierergebnisse": // Ergebnisse eines Turniers
					// code = Turniercode, z.B. B148-C12-SLG
					$tstart = microtime(true);
					self::$answer = $client->tournamentPairings($parameter["code"]);
					self::$answertime = microtime(true) - $tstart;
					break;
				case "Verbaende": // Verbände einer ZPS-Struktur laden
					// zps = fünfstellig
					$tstart = microtime(true);
					self::$answer = $client->organizations($parameter["zps"]);
					self::$answer = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::AddWuerttemberg(self::$answer); // Vereine vom SV Württemberg hinzufügen (Patch bis das in MIVIS behoben ist)
					self::$answertime = microtime(true) - $tstart;
					break;
				default:
			}

/*
			echo "<pre>";
			print_r(self::$answer);
			echo "</pre>";
*/

			// Abfrage erfolgreich
			return self::$answer;

		}

		catch(\SOAPFault $f)
		{
			$time_request = (microtime(true)-$time_start);
			if(ini_get('default_socket_timeout') < $time_request)
			{
				// Timeout Fehler!
				self::$error = "Die DeWIS-Datenbank unter svw.info ist nicht erreichbar.";
			}
			else
			{
				switch($f->faultstring)
				{
					case "that is not a valid federation id":
						self::$error = "Ungültiger Verbandscode [1]";
						break;
					case "that federation does not exists":
						self::$error = "Ungültiger Verbandscode [2]";
						break;
					case "that is not a valid union id":
						break;
					case "that union does not exists":
						//self::$error = "Ungültiger Vereinscode";
						//self::$errorcode = 410; // Gone (Die angeforderte Ressource wird nicht länger bereitgestellt und wurde dauerhaft entfernt.) - Vorschlag Mossakowski
						//\System::log('ZPS-Vereinscode '.$parameter["zps"].' ist ungültig ('.$f->faultstring.')', 'DeWIS-Abfrage', TL_ERROR);
						// Abbruch nicht möglich, da auch gültige Anfragen kommen: http://www.schachbund.de/verein/A0800.html
						//header('HTTP/1.1 410 Gone');
						//die('ZPS-Vereinscode '.$parameter["zps"].' ist ungueltig ('.$f->faultstring.')');
						break;
					case "Could not connect to host":
						self::$error = "Die DeWIS-Datenbank unter svw.info ist nicht erreichbar.";
						break;
					case "that is not a member":
						self::$error = "Der Spieler ist kein Mitglied des DSB.";
						break;
					case "that is not a valid surname":
						self::$error = "Ungültiger Nachname";
						break;
					case "that is not a valid tournament":
						self::$error = "Ungültiges Turnier";
						break;
					case "tournament level not valid":
						self::$error = "Ungültige ZPS für Verband";
						break;
					case "surname too short":
						self::$error = "Nachname zu kurz";
						break;
					case "that is not a valid tournament codeno valid id givenno valid id given":
						self::$error = "Ungültiger Turniercode";
						break;
					default:
						self::$error = $f->faultstring;
				}
			}
		}

		// Fehler bei der Abfrage
		return FALSE;
	}

	/*
	* Wandelt Unixzeit in JJJJ-MM-TT um
	*/
	public static function SOAP_Datum($zeit)
	{
		return date("Y-m-d",$zeit);
	}

	/*
	* Gibt die Antwortzeit des Servers zurück
	*/
	public static function Antwortzeit()
	{
		return self::$answertime;
	}

	/*
	* Fehler der SOAP-Abfrage zurückgeben
	*/
	public static function ZeigeFehler()
	{
		return self::$error;
	}

	/*
	* Fehlercode der SOAP-Abfrage zurückgeben
	*/
	public static function ZeigeFehlercode()
	{
		return self::$errorcode;
	}

	/*
	* Status der Geburtsjahranzeige setzen
	*
	* @param boolean $status         1 = anzeigen, 0 = nicht anzeigen
	*/
	public static function ZeigeGeburtsjahr($status)
	{
		if($status) $this->viewyear = TRUE;
		else $this->viewyear = FALSE;
	}

	public static function DWZ($rating, $ratingIndex)
	{
		return ($rating == 0 && $ratingIndex == 0) ? '' : sprintf("%s -%s", str_replace(' ', '&nbsp;&nbsp;', sprintf("%4d", $rating)), str_replace(' ', '&nbsp;&nbsp;', sprintf("%3d", $ratingIndex)));
	}

	public static function Punkte($points)
	{
		return ($points == 0.5) ? '½' : str_replace('.5', '½', $points * 1);
	}

	public static function Kalenderwoche($string)
	{
		return $string ? substr($string, 2, 2) . '/' . (substr($string, 0, 1) > '9' ? '20' . (ord(substr($string, 0, 1)) - 65) : '19' . substr($string, 0, 1)) . substr($string, 1, 1) : '&nbsp;';
	}

	public static function AlteDatenbank($id)
	{
		# --------------------------------------------------------
		# Sucht in der alten Datenbank nach dem Spieler mit der ID
		# --------------------------------------------------------

		if(isset($GLOBALS['TL_CONFIG']['dewis_elobase']))
		{
			// Mit MySQL-Server verbinden
			$mysqli = new \mysqli($GLOBALS['TL_CONFIG']['dewis_elobase_host'],$GLOBALS['TL_CONFIG']['dewis_elobase_user'],$GLOBALS['TL_CONFIG']['dewis_elobase_pass'],$GLOBALS['TL_CONFIG']['dewis_elobase_db']);
			if ($mysqli->connect_errno)
			{
				// Keine Antwort von der Datenbank
				return false;
			}
			else
			{
				$sql = "SELECT pkz_alt FROM pkz WHERE pkz_neu = '$id'";
				$ergebnis = $mysqli->prepare($sql);
				$ergebnis->execute();
				$result = $ergebnis->get_result();
				if($row = $result->fetch_object())
				{
					// Alte PKZ gefunden, dann nach einer ZPS suchen
					$pkz = $row->pkz_alt;
					$sql = "SELECT zpsver,szpsmgl,sstatus FROM dwz_spi WHERE pkz = '$pkz'";
					$ergebnis = $mysqli->prepare($sql);
					$ergebnis->execute();
					$result = $ergebnis->get_result();
					while($row = $result->fetch_object())
					{
						// mind. eine ZPS gefunden
						return array("zps" => $row->zpsver."-".$row->szpsmgl,"status" => $row->sstatus);
					}
				}
			}
		}
		else return false;
	}

	public static function Verbandsliste($zps)
	{

		// Abfrageparameter einstellen
		$param = array
		(
			'funktion' => 'Verbaende',
			'cachekey' => $zps,
			'zps'      => $zps
		);

		$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen
		//echo "<pre>";
		//print_r($resultArr);
		//echo "</pre>";

		// Verbände und Vereine ordnen
		list($verbaende, $vereine) = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::org($resultArr['result']);

		return array('verbaende' => $verbaende, 'vereine' => $vereine);
	}

	protected static function org($result)
	{

		\Schachbulle\ContaoDewisBundle\Helper\DeWIS::sub_org($result, $liste);

		$verbaende = array();
		$vereine = array();
		reset($liste);

		foreach ($liste as $l)
		{
			if($l['childs'] or $l['parent'] == '000')
			{
				$l['childs'] = array();
				$verbaende['' . $l['zps']] = $l;
				if(isset($l['ZPS']) != '000')
				{
					$verbaende['' . $l['parent']]['childs'][] = $l['zps'];
				}
			}
			else
			{
				unset($l['childs']);
				$vereine['' . $l['zps']] = $l;
			}
		}

		// Verband K hinzufügen und DSB modifizieren
		$verbaende['K00'] = array('zps' => 'K00', 'name' => 'Ausländer', 'order' => 'auslaender', 'parent' => '000', 'childs' => array());
		$verbaende['000']['childs'] = array_merge($verbaende['000']["childs"],array('K00'));

		return array($verbaende, $vereine);
	}

	protected static function sub_org($a, &$liste)
	{
		$c = (is_array($a->children) && count($a->children) > 0) ? true : false; // Kindelemente (LV, Bezirke, Vereine)? true/false
		$p = (isset($a->p) && isset($liste[$a->p]['zps'])) ? $liste[$a->p]['zps'] : $a->vkz; // Elternelement speichern
		$n = $a->club;
		$liste[$a->id] = array
		(
			'zps'           => $a->vkz, # sprintf("%-05s", $a->vkz),
			'name'          => str_replace("'", "\'", $n),
			'order'         => str_replace("'", "\'", \StringUtil::generateAlias($n)),
			'parent'        => $p,
			'childs'        => $c
		);

		// Gibt es auf der aktuellen Ebene Kindelemente?
		if ($c)
		{
			// Kindelemente der Reihe nach rekursiv abarbeiten
			foreach ($a->children as $b)
			{
				\Schachbulle\ContaoDewisBundle\Helper\DeWIS::sub_org($b, $liste);
			}
		}
	}

	/**
	 * Hook-Funktion:
	 * Wertet das URL-Parameter-Array aus und modifiziert es, wenn das Array für DeWIS bestimmt ist
	 *
	 * @return array
	 */
	public static function getParamsFromUrl($arrFragments)
	{
		//echo "<!--";
		//print_r($arrFragments);
		$args = count($arrFragments); // Anzahl Argumente

		if($args == 1)
		{
			// In $args[0] steht das Seitenalias, jetzt prüfen auf URL-Parameter und ggfs. auf neue URL weiterleiten
			switch($arrFragments[0])
			{

				case \Schachbulle\ContaoDewisBundle\Helper\Helper::getSpielerseite():
					if(\Input::get('zps'))
					{
						header('Location:'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getSpielerseite().'/'.\Input::get('zps').'.html');
					}
					elseif(\Input::get('pkz'))
					{
						header('Location:'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getSpielerseite().'/'.\Input::get('pkz').'.html');
					}
					break;

				case \Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite():
					if(\Input::get('zps'))
					{
						header('Location:'.\Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite().'/'.\Input::get('zps').'.html');
					}
					break;

				default:
			}
		}
		elseif($args > 1)
		{
			// In $args[0] steht das Seitenalias, ab $args[1] die Parameter
			switch($arrFragments[0])
			{

				case \Schachbulle\ContaoDewisBundle\Helper\Helper::getSpielerseite():
					if($arrFragments[1] == 'auto_item') $arrFragments[1] = 'id';
					// ZPS-Angabe ggfs. anpassen (4-stellige Mitgliedsnummer!)
					$zps = explode('-', $arrFragments[2]);
					$arrFragments[2] = count($zps) == 2 ? $zps[0].'-'.substr('0000'.$zps[1], -4) : $arrFragments[2];
					break;

				case \Schachbulle\ContaoDewisBundle\Helper\Helper::getVereinseite():
					if($arrFragments[1] == 'auto_item') $arrFragments[1] = 'zps';
					break;

				case \Schachbulle\ContaoDewisBundle\Helper\Helper::getVerbandseite():
					if($arrFragments[1] == 'auto_item') $arrFragments[1] = 'zps';
					break;

				case \Schachbulle\ContaoDewisBundle\Helper\Helper::getTurnierseite():
					if($arrFragments[1] == 'auto_item')
					{
						$arrFragments[1] = 'code';
					}
					else
					{
						$newArray = array($arrFragments[0]);
						// 1. Wert ist offensichtlich ein Turniercode
						$newArray[1] = 'code';
						$newArray[2] = $arrFragments[1];
						if($arrFragments[2] == 'Ergebnisse')
						{
							// Ein weiterer Wert wartet: Ergebnisse des Turniers anzeigen
							$newArray[3] = 'view';
							$newArray[4] = 'results';
						}
						elseif($arrFragments[2])
						{
							// Ein weiterer Wert wartet: ID des Spielers
							$newArray[3] = 'id';
							$newArray[4] = $arrFragments[2];
							$newArray[5] = 'view';
							$newArray[6] = 'results';
						}
						$arrFragments = $newArray;
					}
					break;

				default:
			}
		}

		//echo "<br>";
		//print_r($arrFragments);
		//echo "-->";

		return $arrFragments;
	}


	/**
	 * PurgeJob-Funktion:
	 * Berechnet die Cache-Größe
	 */
	public static function calcCache()
	{
		$speicher = array
		(
			'Verbaende',
			'Wertungsreferent',
			'Spielerliste',
			'Karteikarte',
			'KarteikarteZPS',
			'Vereinsliste',
			'Verbandsliste',
			'Turnierliste',
			'Turnierauswertung',
			'Turnierergebnisse'
		);

		$string = '</label>';
		foreach($speicher as $item)
		{
			$cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache(array('name' => $item, 'extension' => '.cache'));
			$anzahl = count($cache->retrieveAll()); // Anzahl der Cache-Einträge
			$text = ($anzahl == 1) ? 'Eintrag' : 'Einträge';
			$string .= '<br><span style="font-weight:normal"><span style="color:black">'.$item.':</span> '.$anzahl.' '.$text.'</span>';
		}
		$string .= '<label>';

		//log_message(count($daten),'dewis-cache.log');
		return $string;
	}

	/**
	 * PurgeJob-Funktion:
	 * Stellt im BE unter Systemwartung die Cache-Löschung zur Verfügung
	 */
	public static function purgeCache()
	{
		$speicher = array
		(
			'Verbaende',
			'Wertungsreferent',
			'Spielerliste',
			'Karteikarte',
			'KarteikarteZPS',
			'Vereinsliste',
			'Verbandsliste',
			'Turnierliste',
			'Turnierauswertung',
			'Turnierergebnisse'
		);

		foreach($speicher as $item)
		{
			$cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache(array('name' => $item, 'extension' => '.cache'));
			$cache->eraseAll(); // Cache löschen
		}

		log_message('Cache deleted','dewis-cache.log');
		return;
	}



	/**
	 * Hilfsfunktion:
	 * Formatierte Ausgabe einer Variable
	 *
	 * @return array
	 */
	public static function debug($value)
	{
		echo '<pre>';
		print_r($value);
		echo '</pre>';
	}

	/**
	 * Hilfsfunktion:
	 * Kürzt den Vereinsnamen auf 34 Zeichen, entfernt vorher unnötige Zeichenfolgen
	 *
	 * @return string
	 */
	public static function Vereinskurzname($value)
	{
		$ersetzen = array
		(
			'' => '',
		);
		$value = str_ireplace(array_keys($ersetzen),array_values($ersetzen),$value);
		return (strlen($value) > 30) ? substr($value,0,30).' [...]' : $value;
	}


	/**
	 * Hilfsfunktion:
	 * Kürzt den Turniernamen auf 38 Zeichen
	 *
	 * @return string
	 */
	public static function Turnierkurzname($value)
	{
		if(mb_detect_encoding($value,'UTF-8, ISO-8859-1') === 'UTF-8')
		{
			# Der Turniername ist in UTF-8 kodiert und muß vor der Kürzung umgewandelt werden
			$value = utf8_decode($value);
		}

		// Gekürzten Turniernamen generieren und wieder in UTF-8 umwandeln
		$neu = (strlen($value) > 38) ? substr($value,0,38).' [...]' : $value;
		return utf8_encode($neu);

	}


	/**
	 * Liefert zu einer ID die kompletten Daten oder den Namen des Wertungsreferenten
	 *
	 * @return string
	 */
	public static function Wertungsreferent($id, $address = true)
	{

		// Abfrageparameter einstellen
		$param = array
		(
			'funktion' => 'Wertungsreferent',
			'cachekey' => $id,
			'id'       => $id
		);

		$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

		if($address)
		{
			// Name und Adresse ausgeben
			$strasse = ($resultArr['result']->street && $resultArr['result']->street != '-') ? $resultArr['result']->street : '';
			$ort = ($resultArr['result']->zip && $resultArr['result']->city) ? $resultArr['result']->zip .' '. $resultArr['result']->city : '';
			$adresse = ($strasse && $ort) ? '<br>'.$strasse.', '.$ort : '';

			$email = ($resultArr['result']->email && $resultArr['result']->email != '-') ? $resultArr['result']->email : '';

			return $resultArr['result'] ? $resultArr['result']->firstname." ".$resultArr['result']->surname.$adresse.($email ? '<br>{{email::'.$email.'}}' : '') : '';
		}
		else
		{
			// Name ausgeben
			return $resultArr['result'] ? $resultArr['result']->firstname." ".$resultArr['result']->surname : '('.$id.')';
		}

	}


	/**
	 * Liefert eine Turnierauswertung
	 *
	 * @return string
	 */
	public static function Turnierauswertung($code)
	{

		// Abfrageparameter
		$param = array
		(
			'funktion'  => 'Turnierauswertung',
			'cachekey'  => $code,
			'code'      => $code
		);

		$resultArr = \Schachbulle\ContaoDewisBundle\Helper\DeWIS::autoQuery($param); // Abfrage ausführen

		return $resultArr['result'];
	}


	/**
	 * Liefert die Gewinnerwartung
	 *
	 * @return float
	 */
	public static function Gewinnerwartung($dwz, $gegnerdwz)
	{
		// Umwandeln in Integer, falls ein String übergeben wurde
		$dwz = (int)$dwz;
		$gegnerdwz = (int)$gegnerdwz;
		if($dwz == 0 || $gegnerdwz == 0) return false;
		return (sprintf ("%5.3f", 1/(1+pow(10,($gegnerdwz-$dwz)/400))));
	}

	/**
	 * Schätzt die Turnierleistung, wenn zu wenig Partien
	 *
	 */
	public static function LeistungSchaetzen($niveau = 0, $punkte, $partien, $dwz, $pd = '')
	{

		if($pd == '')
			$pd = 0.5 * $partien;

		if($partien) $ppp = $punkte / $partien;
		if($niveau == 0 OR $niveau == '')
		{
			if($partien && (($punkte - $pd) / $partien > 0.01))
				$leistung = $dwz + 100;
			elseif($partien && ((abs($punkte - $pd)) / $partien <= 0.01))
				$leistung = $dwz;
			else
				$leistung = $dwz - 100;
			return $leistung;
		}
		if(($partien != 5 AND $partien != 6) && ($ppp == 1 OR $ppp == 0))
		{
			$diff = 677 / (5 - $partien);
			$leistung = round(($dwz + ($dwz - ($diff + $niveau)) / (6 - $partien)), 0);
		}
		elseif(round($ppp, 0) == 0.5)
		{
			$leistung = $niveau;
		}
		elseif ($punkte != 0)
		{
			$leistung = round(-400 * log10($partien / $punkte - 1) + $niveau, 0);
		}

		return $leistung;
	}

	/**
	 * Liefert die Blacklist zurück
	 *
	 */
	public static function Blacklist()
	{
		// Gesperrte ID's einlesen
		//$result = \Database::getInstance()->prepare("SELECT dewis_id FROM tl_dewis_blacklist WHERE published = '1'")
		//								  ->execute();
		$result = \Database::getInstance()->prepare("SELECT dewisID FROM tl_dwz_spi WHERE blocked = '1'")
		                                  ->execute();

		$blacklist = array();
		// Übernehmen
		if($result->numRows)
		{
			while($result->next())
			{
				// Frage: Was ist schneller? Dieser Indexzugriff oder später in_array?
				$blacklist[$result->dewis_id] = true;
			}
		}

		return $blacklist;
	}


	/**
	 * Liefert den Status der Sperre der alten Karteikarte zurück
	 *
	 */
	public static function Karteisperre($id)
	{
		$result = \Database::getInstance()->prepare("SELECT link_altkartei FROM tl_dwz_spi WHERE dewisID = ?")
		                                  ->execute($id);

		// Gefunden
		if($result->numRows)
		{
			return $result->link_altkartei;
		}

		return false;
	}

	/**
	 * Liefert die FIDE-Nation eines Spielers
	 * ======================================
	 * @param       id      DeWIS-ID des Spielers
	 * @return      string  FIDE-Nation, z.B. GER bzw. leer
	 *
	 */
	public static function Nation($id)
	{
		$result = \Database::getInstance()->prepare("SELECT fideNation FROM tl_dwz_spi WHERE dewisID = ?")
		                                  ->execute($id);

		if($result->numRows)
		{
			// DeWIS-ID gefunden, Nation zurückgeben
			return $result->fideNation;
		}
		else
		{
			// DeWIS-ID nicht gefunden, Abfrage bei DeWIS-API machen
			// Spielerkartei laden
			$param = array
			(
				'funktion'  => 'Karteikarte',
				'cachekey'  => $id,
				'id'        => $id
			);
			$karteikarte = self::autoQuery($param); // Abfrage ausführen
			return $karteikarte['result']->member->fideNation;
		}

	}

	/**

	 * Liefert den Hinweistext zwecks Anmeldung zurück
	 *
	 */
	public static function Registrierungshinweis()
	{
		return '<div class="hinweis noprint">Aus datenschutzrechtlichen Gründen können Spielerdetails seit dem <a href="http://www.schachbund.de/news/aenderungen-beim-zugriff-auf-die-dwz.html">3. Juni 2016</a> nur noch von registrierten Nutzern angesehen werden. <a href="http://www.schachbund.de/registrierung.html">Hier geht es zur kostenlosen Registrierung</a>.</div>';
	}

	/**
	 * Lädt die FIDE-Daten Elo, Titel, Nation aus der lokalen Quelle
	 */
	public static function getFIDE($fideid)
	{
		$fide = array();
		if($fideid)
		{
			// FIDE-ID in lokaler Datenbank suchen
			$objPlayer = \Database::getInstance()->prepare("SELECT * FROM elo WHERE fideid = ?")
			                                     ->execute($fideid);
			if($objPlayer->numRows)
			{
				$fide = array
				(
					'land'  => $objPlayer->country,
					'elo'   => $objPlayer->rating,
					'titel' => $objPlayer->title
				);
			}
		}
		return $fide;
	}

	/**
	 * Aktualisiert die Elo anhand der lokalen Quelle
	 *
	 * @param object $result           Abfrageergebnis DeWIS
	 * @param array $parameter         Abfrageparameter die an DeWIS geliefert wurden
	 *
	 */
	public static function ModifiziereElo($result, $parameter)
	{
		// Lokale Elo verwenden aktiviert?
		if(isset($GLOBALS['TL_CONFIG']['dewis_eloLocal']) == true)
		{
			//echo "<pre>";
			//print_r($result);
			//echo "</pre>";
			switch($parameter["funktion"])
			{
				case "Spielerliste": // Spielerliste einer Suche
				case "Vereinsliste": // Vereinsliste
				case "Verbandsliste": // Bestenliste eines Verbands
					if($result->members)
					{
						foreach($result->members as $key => $value)
						{
							$fide = self::getFIDE($result->members[$key]->idfide);
							$result->members[$key]->elo = $fide['elo'];
							$result->members[$key]->fideTitle = $fide['titel'];
							$result->members[$key]->fideNation = $fide['land'];
						}
					}
					break;
				case "Karteikarte": // Karteikarte nach ID
				case "KarteikarteZPS": // Karteikarte nach ZPS
					if($result->member)
					{
						$fide = self::getFIDE($result->member->idfide);
						$result->member->elo = $fide['elo'];
						$result->member->fideTitle = $fide['titel'];
						$result->member->fideNation = $fide['land'];
					}
					break;

				default:
			}
		}
		return $result;
	}

	/**
	 * Aktualisiert die Tabellen tl_dwz_xxx mit den Daten aus DeWIS
	 *
	 * @param object $result           Abfrageergebnis DeWIS
	 * @param array $parameter         Abfrageparameter die an DeWIS geliefert wurden
	 *
	 */
	public static function AktualisiereDWZTabellen($result, $parameter)
	{
		switch($parameter["funktion"])
		{
			case "Spielerliste": // Spielerliste einer Suche

				// Vereine und Spieler aktualisieren
				\Schachbulle\ContaoDewisBundle\Helper\AktualisiereVereine::fromSpielerliste($result);
				\Schachbulle\ContaoDewisBundle\Helper\AktualisiereSpieler::fromSpielerliste($result);
				break;

			case "Vereinsliste": // Vereinsliste

				// Vereine und Spieler aktualisieren
				\Schachbulle\ContaoDewisBundle\Helper\AktualisiereVereine::fromVereinsliste($result);
				\Schachbulle\ContaoDewisBundle\Helper\AktualisiereSpieler::fromVereinsliste($result);
				break;

			default:
		}
	}

	public static function AddWuerttemberg($result)
	{
		// Landesverbände durchlaufen
		for($index_lv = 0; $index_lv < count($result->children); $index_lv++)
		{
			// Bezirke durchlaufen
			for($index_bezirk = 0; $index_bezirk < count($result->children[$index_lv]->children); $index_bezirk++)
			{
				switch($result->children[$index_lv]->children[$index_bezirk]->vkz)
				{
					case 'C01':
						// Vereine C01 definieren
						$vkz_found = true;
						$vereine = array
						(
							(object) array('id' => '2549', 'club' => 'Post-SV Ulm','vkz' =>'C0101','p' =>'180','assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2546', 'club' => 'SF Vöhringen','vkz' =>'C0104','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2545', 'club' => 'TSV Langenau','vkz' =>'C0105','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2543', 'club' => 'SF Blaustein','vkz' =>'C0107','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2541', 'club' => 'SC Obersulmetingen','vkz' =>'C0109','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '4287', 'club' => 'TSG Ehingen 1848','vkz' =>'C010A','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '4721', 'club' => 'VfL Leipheim 1898','vkz' =>'C010D','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2540', 'club' => 'TSV Berghülen','vkz' =>'C0110','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2537', 'club' => 'TSV Laichingen','vkz' =>'C0113','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2534', 'club' => 'TG Biberach','vkz' =>'C0116','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2532', 'club' => 'TSV 1880 Neu-Ulm','vkz' =>'C0118','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2526', 'club' => 'SC Laupheim 1962','vkz' =>'C0124','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2525', 'club' => 'SF Riedlingen','vkz' =>'C0125','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2523', 'club' => 'TSV Seissen','vkz' =>'C0127','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2519', 'club' => 'SC Weiße Dame Ulm','vkz' =>'C0131','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2513', 'club' => 'SV Jedesheim 1921','vkz' =>'C0137','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2512', 'club' => 'TV Wiblingen','vkz' =>'C0138','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2511', 'club' => 'SV Steinhausen','vkz' =>'C0139','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2510', 'club' => 'TSV Reute','vkz' =>'C0140','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2508', 'club' => 'TSV Westerstetten','vkz' =>'C0142','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2507', 'club' => 'SV Thalfingen','vkz' =>'C0143','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2548', 'club' => 'SK Markdorf','vkz' =>'C0102','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2544', 'club' => 'SC Lindau','vkz' =>'C0106','p' =>'180','assessor' => '10033089', 'children' => array()),
							(object) array('id' => '4521', 'club' => 'TG Bad Waldsee 1848','vkz' =>'C010C','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2539', 'club' => 'SC Tettnang','vkz' =>'C0111','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2538', 'club' => 'SC Wangen','vkz' =>'C0112','p' =>'180','assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2536', 'club' => 'SV Friedrichshafen','vkz' =>'C0114','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2535', 'club' => 'SF Ravensburg','vkz' =>'C0115','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2533', 'club' => 'SF Wetzisreute','vkz' =>'C0117','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2530', 'club' => 'SF Mengen','vkz' =>'C0120','p' =>'180','assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2529', 'club' => 'SV Weingarten','vkz' =>'C0121','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2528', 'club' => 'SK Leutkirch','vkz' =>'C0122','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2520', 'club' => 'SC Bad Schussenried','vkz' =>'C0130', 'p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2518', 'club' => 'SC Bad Saulgau','vkz' =>'C0132','p' =>'180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2517', 'club' => 'SC Weiler im Allgäu','vkz' =>'C0133','p' => '180', 'assessor' => '10033089', 'children' => array()),
							(object) array('id' => '2509', 'club' => 'SF Ertingen','vkz' =>'C0141','p' =>'180', 'assessor' => '10033089', 'children' => array())
						);
						break;
					case 'C02':
						// Vereine C02 definieren
						$vkz_found = true;
						$vereine = array
						(
							(object) array('id' => '2493', 'club' => 'SC Möhringen 1961','vkz' =>'C0215','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2492', 'club' => 'SG Donautal Tuttlingen','vkz' =>'C0216','p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2486', 'club' => 'SV Rottweil','vkz' =>'C0222','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2482', 'club' => 'SR Spaichingen','vkz' =>'C0226','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2478', 'club' => 'SR Heuberg-Gosheim','vkz' =>'C0230','p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2476', 'club' => 'SV Trossingen','vkz' =>'C0232','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2496', 'club' => 'SK Horb','vkz' =>'C0211','p' =>'179','assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2495', 'club' => 'SC Klosterreichenbach','vkz' =>'C0212','p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2489', 'club' => 'SC Oberndorf','vkz' =>'C0219','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2472', 'club' => 'SF Dornstetten-Pfalzgrafenweiler','vkz' => 'C0238', 'p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2468', 'club' => 'SG Schramberg-Lauterbach','vkz' =>'C0242', 'p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2505', 'club' => 'SV Balingen','vkz' =>'C0202','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2504', 'club' => 'SC Bisingen-Steinhofen','vkz' =>'C0203', 'p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2502', 'club' => 'SF Geislingen 1990','vkz' =>'C0205','p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2501', 'club' => 'SG Turm Albstadt 1902','vkz' =>'C0206', 'p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2498', 'club' => 'SC Hechingen','vkz' =>'C0209','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2497', 'club' => 'SC Heinstetten','vkz' =>'C0210','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2490', 'club' => 'SC Nusplingen','vkz' =>'C0218','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2488', 'club' => 'SV Rangendingen','vkz' =>'C0220','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2480', 'club' => 'SV Stockenhausen-Frommern','vkz' =>'C0228', 'p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2474', 'club' => 'Sfr. Winterlingen 1966','vkz' =>'C0235', 'p' => '179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2473', 'club' => 'SV Schömberg','vkz' =>'C0237','p' =>'179', 'assessor' => '10028978', 'children' => array()),
							(object) array('id' => '2469', 'club' => 'SG Dotternhausen','vkz' =>'C0241','p' =>'179', 'assessor' => '10028978', 'children' => array())
						);
						break;
					case 'C03':
						// Vereine C03 definieren
						$vkz_found = true;
						$vereine = array
						(
							(object) array('id' => '2467', 'club' => 'SV Altbach','vkz' =>'C0301','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2466', 'club' => 'Schachgemeinschaft Filder','vkz' =>'C0302', 'p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2465', 'club' => 'SF Deizisau','vkz' =>'C0303','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2464', 'club' => 'TSV Denkendorf','vkz' =>'C0304','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2463', 'club' => 'SV Dicker Turm Esslingen','vkz' =>'C0305', 'p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2462', 'club' => 'TSV/RSK Esslingen','vkz' =>'C0306','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2461', 'club' => 'TSG Esslingen','vkz' =>'C0307','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2460', 'club' => 'TSV Grafenberg','vkz' =>'C0308','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '4519', 'club' => 'Schachritter Kirchheim/Teck','vkz' =>'C030B', 'p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '4901', 'club' => 'Schachklub Freibauer Esslingen','vkz' =>'C030D', 'p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2457', 'club' => 'SF 47 Neckartenzlingen','vkz' =>'C0311','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2456', 'club' => 'SC Ostfildern 1952','vkz' =>'C0312','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2455', 'club' => 'SV Nürtingen 1920','vkz' =>'C0313','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2454', 'club' => 'SF Plochingen','vkz' =>'C0314','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2452', 'club' => 'SV 1947 Wendlingen','vkz' =>'C0316','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2450', 'club' => 'SK Wernau','vkz' =>'C0318','p' =>'178','assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2446', 'club' => 'SV Ebersbach','vkz' =>'C0322','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2444', 'club' => 'SV Faurndau','vkz' =>'C0324','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2443', 'club' => 'SC Geislingen 1881','vkz' =>'C0325','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2441', 'club' => 'SF 1876 Göppingen','vkz' =>'C0327','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2440', 'club' => 'SC Kirchheim/Teck','vkz' =>'C0328','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2438', 'club' => 'TSG Salach','vkz' =>'C0330','p' =>'178','assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2420', 'club' => 'TSG Zell u.A.','vkz' =>'C0351','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2412', 'club' => 'Ssg Fils-Lauter','vkz' =>'C0359','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '4699', 'club' => 'TSV Undingen','vkz' =>'C030C','p' =>'178','assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2434', 'club' => 'SV Urach','vkz' =>'C0334','p' =>'178','assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2433', 'club' => 'SF Ammerbuch','vkz' =>'C0335','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2432', 'club' => 'SV Dettingen Erms','vkz' =>'C0336','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2430', 'club' => 'SC BW Kirchentellinsfurt','vkz' =>'C0338', 'p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2429', 'club' => 'Rochade Metzingen','vkz' =>'C0339','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2426', 'club' => 'SF Pfullingen','vkz' =>'C0344','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2425', 'club' => 'SV Pliezhausen','vkz' =>'C0345','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2424', 'club' => 'SV Reutlingen','vkz' =>'C0346','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2422', 'club' => 'SC Steinlach','vkz' =>'C0349','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2421', 'club' => 'SV Tübingen 1870','vkz' =>'C0350','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2417', 'club' => 'SF Springer Rottenburg','vkz' =>'C0354','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2416', 'club' => 'SF Lichtenstein','vkz' =>'C0355','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2415', 'club' => 'SG Schönbuch','vkz' =>'C0356','p' =>'178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2414', 'club' => 'SK Bebenhausen 1992','vkz' =>'C0357','p' => '178', 'assessor' => '10114588', 'children' => array()),
							(object) array('id' => '2410', 'club' => 'SG Königskinder Hohentübingen','vkz' => 'C0361', 'p' => '178', 'assessor' => '10114588', 'children' => array())
						);
						break;
					case 'C04':
						// Vereine C04 definieren
						$vkz_found = true;
						$vereine = array
						(
							(object) array('id' => '2409', 'club' => 'SV Aalen-Ellwangen','vkz' =>'C0401', 'p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2408', 'club' => 'SC Tannhausen 1986','vkz' =>'C0402','p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2407', 'club' => 'SV Unterkochen','vkz' =>'C0403','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2405', 'club' => 'SV Crailsheim','vkz' =>'C0405','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2404', 'club' => 'SC 1875 Ellwangen','vkz' =>'C0406','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2391', 'club' => 'SV Oberkochen','vkz' =>'C0419','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2379', 'club' => 'SC Rainau','vkz' =>'C0431','p' =>'177','assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2374', 'club' => 'SC Bopfingen','vkz' =>'C0436','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2372', 'club' => 'SV Königsspringer Stödtlen','vkz' =>'C0438', 'p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2402', 'club' => 'SV Giengen','vkz' =>'C0408','p' =>'177','assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2400', 'club' => 'SK Heidenheim','vkz' =>'C0410','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2398', 'club' => 'SC Heidenheim - Schnaitheim','vkz' =>'C0412', 'p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2396', 'club' => 'RSV Heuchlingen','vkz' =>'C0414','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2395', 'club' => 'SF Königsbronn','vkz' =>'C0415','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2384', 'club' => 'SK Sontheim/Brenz','vkz' =>'C0426','p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2401', 'club' => 'SC Grunbach','vkz' =>'C0409','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2397', 'club' => 'SF Heubach','vkz' =>'C0413','p' =>'177','assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2394', 'club' => 'SC Leinzell','vkz' =>'C0416','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2392', 'club' => 'SF 90 Spraitbach','vkz' =>'C0418','p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2390', 'club' => 'SC Plüderhausen','vkz' =>'C0420','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2389', 'club' => 'Schachunion Schorndorf','vkz' =>'C0421', 'p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2388', 'club' => 'SG Schwäbisch Gmünd 1872','vkz' =>'C0422', 'p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2386', 'club' => 'SG Bettringen','vkz' =>'C0424','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2383', 'club' => 'TSF Welzheim','vkz' =>'C0427','p' =>'177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2377', 'club' => 'SF Waldstetten 1982','vkz' =>'C0433','p' => '177', 'assessor' => '10207830', 'children' => array()),
							(object) array('id' => '2371', 'club' => 'TSV Alfdorf','vkz' =>'C0439','p' =>'177', 'assessor' => '10207830', 'children' => array())
						);
						break;
					case 'C05':
						// Vereine C05 definieren
						$vkz_found = true;
						$vereine = array
						(
							(object) array('id' => '2365', 'club' => 'TSF Ditzingen','vkz' =>'C0506','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '4601', 'club' => 'Zentrumsbauer Stuttgart','vkz' =>'C050B', 'p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '4749', 'club' => 'Schachclub Strateg Stuttgart','vkz' =>'C050C', 'p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2350', 'club' => 'Stuttgarter SF 1879','vkz' =>'C0521','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2348', 'club' => 'DJK Stuttgart-Süd','vkz' =>'C0523','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2343', 'club' => 'SG Fasanenhof','vkz' =>'C0528','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2339', 'club' => 'SC Sillenbuch','vkz' =>'C0532','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2333', 'club' => 'SV Stuttgart-Wolfbusch 1956','vkz' => 'C0538', 'p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2332', 'club' => 'SSV Zuffenhausen','vkz' =>'C0539','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2323', 'club' => 'SK e4 Gerlingen','vkz' =>'C0548','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2319', 'club' => 'SC Schachmatt Botnang','vkz' =>'C0552','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2317', 'club' => 'GSV Hemmingen','vkz' =>'C0554','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2313', 'club' => 'TSV Heumaden','vkz' =>'C0560','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2310', 'club' => 'SC Feuerbach','vkz' =>'C0563','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '4179', 'club' => 'TV Zazenhausen','vkz' =>'C0566','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2370', 'club' => 'SC Affalterbach','vkz' =>'C0501','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2369', 'club' => 'SV Backnang','vkz' =>'C0502','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2364', 'club' => 'SV Fellbach','vkz' =>'C0507','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2361', 'club' => 'SK Korb 1948','vkz' =>'C0510','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2357', 'club' => 'SC Murrhardt 1948','vkz' =>'C0514','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2356', 'club' => 'SF Oeffingen','vkz' =>'C0515','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2354', 'club' => 'SK Schmiden/Cannstatt','vkz' =>'C0517','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2353', 'club' => 'SV Schwaikheim','vkz' =>'C0518','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2341', 'club' => 'Mönchfelder SV 1967','vkz' =>'C0530','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2331', 'club' => 'SC Waiblingen 1921','vkz' =>'C0540','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2327', 'club' => 'SC Winnenden','vkz' =>'C0544','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2320', 'club' => 'SpVgg Rommelshausen','vkz' =>'C0551','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2316', 'club' => 'SF Hohenacker','vkz' =>'C0555','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2308', 'club' => 'Schach-Pinguine Sulzbach','vkz' =>'C0565', 'p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2367', 'club' => 'Spvgg Böblingen','vkz' =>'C0504','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2366', 'club' => 'SC Böblingen 1975','vkz' =>'C0505','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2362', 'club' => 'SV Herrenberg','vkz' =>'C0509','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '4417', 'club' => 'Schach-Kids Bernhausen','vkz' =>'C050A', 'p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2359', 'club' => 'SC Leinfelden','vkz' =>'C0512','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2358', 'club' => 'SV Leonberg 1978','vkz' =>'C0513','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2352', 'club' => 'VfL Sindelfingen','vkz' =>'C0519','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2351', 'club' => 'SC Stetten a.d.F.','vkz' =>'C0520','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2349', 'club' => 'TSV Schönaich','vkz' =>'C0522','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2346', 'club' => 'SC Aidlingen','vkz' =>'C0525','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2340', 'club' => 'SV Weil der Stadt','vkz' =>'C0531','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2337', 'club' => 'SV Nagold','vkz' =>'C0534','p' =>'176','assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2335', 'club' => 'SGem Vaihingen-Rohr','vkz' =>'C0536','p' => '176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2326', 'club' => 'Spvgg Renningen','vkz' =>'C0545','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2324', 'club' => 'SC Magstadt','vkz' =>'C0547','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2315', 'club' => 'TSV Heimsheim','vkz' =>'C0558','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '2309', 'club' => 'TSV Simmozheim','vkz' =>'C0564','p' =>'176', 'assessor' => '10033785', 'children' => array()),
							(object) array('id' => '4211', 'club' => 'SSV Turm Holzgerlingen','vkz' =>'C0567','p' => '176', 'assessor' => '10033785', 'children' => array())
						);
						break;
					case 'C06':
						// Vereine C06 definieren
						$vkz_found = true;
						$vereine = array
						(
							(object) array('id' => '2307', 'club' => 'SV Bad Friedrichshall','vkz' =>'C0601','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2306', 'club' => 'SV Bad Rappenau','vkz' =>'C0602','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2305', 'club' => 'SC Blauer Turm Bad Wimpfen','vkz' =>'C0603', 'p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2303', 'club' => 'VfL Eberstadt','vkz' =>'C0605','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2302', 'club' => 'TG Forchtenberg','vkz' =>'C0606','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '4581', 'club' => 'Post-SG Schwäbisch Hall','vkz' => 'C060A', 'p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2298', 'club' => 'TSG Heilbronn 1845','vkz' =>'C0610','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2297', 'club' => 'Heilbronner SV','vkz' =>'C0611','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2295', 'club' => 'SV 23 Böckingen','vkz' =>'C0613','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2291', 'club' => 'SC Künzelsau','vkz' =>'C0617','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2290', 'club' => 'SK Lauffen','vkz' =>'C0618','p' =>'175','assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2289', 'club' => 'SV Leingarten','vkz' =>'C0619','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2285', 'club' => 'SG Meimsheim-Güglingen','vkz' =>'C0623', 'p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2281', 'club' => 'TSG Öhringen','vkz' =>'C0627','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2279', 'club' => 'SK Schwäbisch Hall','vkz' =>'C0629','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2276', 'club' => 'TSV Untergruppenbach','vkz' =>'C0632','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2273', 'club' => 'SC Widdern','vkz' =>'C0635','p' =>'175','assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2272', 'club' => 'TSV Willsbach','vkz' =>'C0636','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2269', 'club' => 'SC Neckarsulm','vkz' =>'C0639','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2266', 'club' => 'SV Gaildorf/Fichtenberg','vkz' =>'C0642', 'p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2259', 'club' => 'TSV Gerabronn','vkz' =>'C0649','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2258', 'club' => 'SV Rochade Neuenstadt','vkz' =>'C0650','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2256', 'club' => 'SF HN-Biberach 1978','vkz' =>'C0652', 'p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2250', 'club' => 'TSV Schwaigern','vkz' =>'C0658','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '4243', 'club' => 'SF Schwaigern','vkz' =>'C0664','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '4267', 'club' => 'udk SV Ivanchuk Hn Vu Ter','vkz' =>'C0665','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2304', 'club' => 'SV Besigheim','vkz' =>'C0604','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2301', 'club' => 'SV Gemmrigheim','vkz' =>'C0607','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2300', 'club' => 'SK Sachsenheim','vkz' =>'C0608','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '4655', 'club' => 'SF Pattonville','vkz' =>'C060B','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2284', 'club' => 'SF Möglingen 1976','vkz' =>'C0624','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2283', 'club' => 'TSV Münchingen','vkz' =>'C0625','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2282', 'club' => 'SG Ludwigsburg 1919','vkz' =>'C0626','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2278', 'club' => 'TSG Steinheim','vkz' =>'C0630','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2277', 'club' => 'SC Tamm 74','vkz' =>'C0631','p' =>'175','assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2275', 'club' => 'SVG Vaihingen/Enz','vkz' =>'C0633','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2271', 'club' => 'SC Erdmannhausen','vkz' =>'C0637','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2270', 'club' => 'SV Markgröningen','vkz' =>'C0638','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2268', 'club' => 'SV Marbach','vkz' =>'C0640','p' =>'175','assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2263', 'club' => 'SF 59 Kornwestheim','vkz' =>'C0645','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2261', 'club' => 'SC Asperg','vkz' =>'C0647','p' =>'175','assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2260', 'club' => 'SK Bietigheim-Bissingen','vkz' =>'C0648', 'p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2254', 'club' => 'SC Ingersheim','vkz' =>'C0654','p' => '175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2252', 'club' => 'SF Freiberg','vkz' =>'C0656','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2251', 'club' => 'SV Oberstenfeld','vkz' =>'C0657','p' =>'175', 'assessor' => '10252948', 'children' => array()),
							(object) array('id' => '2249', 'club' => 'SV Mundelsheim','vkz' =>'C0659','p' =>'175', 'assessor' => '10252948', 'children' => array())
						);
						break;
					default:
						$vkz_found = false;
						break;
				}

				if($vkz_found)
				{
					// Vereine einhängen
					$result->children[$index_lv]->children[$index_bezirk]->children = $vereine;
				}
			}
		}
		return $result;
	}

}
