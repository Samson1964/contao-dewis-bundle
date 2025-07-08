<?php

namespace Schachbulle\ContaoDewisBundle\Helper;

class AktualisiereSpieler
{
	/**
	 * Klasse initialisieren
	 */
	public function __construct()
	{
	}

	public static function fromSpielerliste($result)
	{
		//echo '<pre>';
		//print_r($result);
		//echo '</pre>';

		if($result->members)
		{
			foreach($result->members as $m)
			{
				// Spieler in lokaler Datenbank suchen, nach dem Kriterium pid
				$objPlayer = \Database::getInstance()->prepare("SELECT * FROM tl_dwz_spi WHERE dewisID = ?")
				                                     ->execute($m->pid);

				if($objPlayer->numRows)
				{
					while($objPlayer->next())
					{
						// Spieler aktualisieren mit den Daten aus DeWIS
						$set = array
						(
							'tstamp'     => time(),
							'nachname'   => $m->surname,
							'vorname'    => $m->firstname,
							'titel'      => ($m->title) ? $m->title : '',
							'geschlecht' => strtoupper($m->gender),
							'geburtstag' => ($m->yearOfBirth > $objPlayer->geburtstag) ? $m->yearOfBirth : $objPlayer->geburtstag,
							'zpsmgl'     => $m->membership,
							'zpsver'     => $m->vkz ? $m->vkz : '',
							'status'     => $m->state ? $m->state : 'A',
							'dwz'        => $m->rating ? $m->rating : 0,
							'dwzindex'   => $m->ratingIndex ? $m->ratingIndex : 0,
							'dwzwoche'   => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
							'fideID'     => $m->idfide ? $m->idfide : '',
							'fideNation' => $m->nationfide ? $m->nationfide : '',
							'fideElo'    => $m->elo ? $m->elo : 0,
							'fideTitel'  => $m->fideTitle ? $m->fideTitle : '',
						);
						$objUpdate = \Database::getInstance()->prepare("UPDATE tl_dwz_spi %s WHERE id=?")
						                                     ->set($set)
						                                     ->execute($objPlayer->id);
						//$arr = (array)$objPlayer;
						//print_r($arr);
					}
				}
				else
				{
					// Spieler in lokaler Datenbank nicht gefunden, deshalb neu anlegen
					$set = array
					(
						'tstamp'     => time(),
						'dewisID'    => $m->pid,
						'nachname'   => $m->surname,
						'vorname'    => $m->firstname,
						'titel'      => ($m->title) ? $m->title : '',
						'geschlecht' => strtoupper($m->gender),
						'geburtstag' => $m->yearOfBirth,
						'zpsmgl'     => $m->membership,
						'zpsver'     => $m->vkz ? $m->vkz : '',
						'status'     => $m->state ? $m->state : 'A',
						'dwz'        => $m->rating ? $m->rating : 0,
						'dwzindex'   => $m->ratingIndex ? $m->ratingIndex : 0,
						'dwzwoche'   => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
						'fideID'     => $m->idfide ? $m->idfide : '',
						'fideNation' => $m->nationfide ? $m->nationfide : '',
						'fideElo'    => $m->elo ? $m->elo : 0,
						'fideTitel'  => $m->fideTitle ? $m->fideTitle : '',
						'published'  => 1,
					);
					$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_dwz_spi %s")
					                                     ->set($set)
					                                     ->execute();
				}
			}
		}
	}

	public static function fromVereinsliste($result)
	{
		//echo '<pre>';
		//print_r($result);
		//echo '</pre>';

		if($result->members)
		{
			foreach($result->members as $m)
			{
				// Spieler in lokaler Datenbank suchen, nach dem Kriterium pid
				$objPlayer = \Database::getInstance()->prepare("SELECT * FROM tl_dwz_spi WHERE dewisID = ?")
				                                     ->execute($m->pid);
				if($objPlayer->numRows)
				{
					while($objPlayer->next())
					{
						// Spieler aktualisieren mit den Daten aus DeWIS
						$set = array
						(
							'tstamp'     => time(),
							'nachname'   => $m->surname,
							'vorname'    => $m->firstname,
							'titel'      => ($m->title) ? $m->title : '',
							'geschlecht' => strtoupper($m->gender),
							'geburtstag' => ($m->yearOfBirth > $objPlayer->geburtstag) ? $m->yearOfBirth : $objPlayer->geburtstag,
							'zpsmgl'     => $m->membership,
							'zpsver'     => $result->union->vkz ? $result->union->vkz : '',
							'status'     => $m->state ? $m->state : 'A',
							'dwz'        => $m->rating ? $m->rating : 0,
							'dwzindex'   => $m->ratingIndex ? $m->ratingIndex : 0,
							'dwzwoche'   => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
							'fideID'     => $m->idfide ? $m->idfide : '',
							'fideElo'    => $m->elo ? $m->elo : 0,
							'fideTitel'  => $m->fideTitle ? $m->fideTitle : '',
						);
						$objUpdate = \Database::getInstance()->prepare("UPDATE tl_dwz_spi %s WHERE id=?")
						                                     ->set($set)
						                                     ->execute($objPlayer->id);
						//$arr = (array)$objPlayer;
						//print_r($arr);
					}
				}
				else
				{
					// Spieler in lokaler Datenbank nicht gefunden, deshalb neu anlegen
					$set = array
					(
						'tstamp'     => time(),
						'dewisID'    => $m->pid,
						'nachname'   => $m->surname,
						'vorname'    => $m->firstname,
						'titel'      => ($m->title) ? $m->title : '',
						'geschlecht' => strtoupper($m->gender),
						'geburtstag' => $m->yearOfBirth,
						'zpsmgl'     => $m->membership,
						'zpsver'     => $result->union->vkz ? $result->union->vkz : '',
						'status'     => $m->state ? $m->state : 'A',
						'dwz'        => $m->rating ? $m->rating : 0,
						'dwzindex'   => $m->ratingIndex ? $m->ratingIndex : 0,
						'dwzwoche'   => \Schachbulle\ContaoDewisBundle\Helper\DeWIS::Kalenderwoche($m->tcode),
						'fideID'     => $m->idfide ? $m->idfide : '',
						'fideElo'    => $m->elo ? $m->elo : 0,
						'fideTitel'  => $m->fideTitle ? $m->fideTitle : '',
						'published'  => 1,
					);
					$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_dwz_spi %s")
					                                     ->set($set)
					                                     ->execute();
					//print_r($m);
				}
			}
		}
	}
}
