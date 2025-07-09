<?php

namespace Schachbulle\ContaoDewisBundle\Helper;

class AktualisiereVereine
{
	/**
	 * Klasse initialisieren
	 */
	public function __construct()
	{
	}

	public static function fromVereinsliste($result)
	{
		//echo '<pre>';
		//print_r($result);
		//echo '</pre>';

		if($result->union)
		{
			// Verein in lokaler Datenbank suchen
			$objVerein = \Database::getInstance()->prepare("SELECT * FROM tl_dwz_ver WHERE zpsver = ?")
			                                     ->execute($result->union->vkz);

			// Datensatz füllen
			$set = array
			(
				'tstamp'     => time(),
				'zpsver'     => $result->union->vkz ? $result->union->vkz : '',
				'name'       => $result->union->name,
				'elobase'    => '',
				'published'  => 1,
			);

			// Datensatz eintragen
			if($objVerein->numRows)
			{
				// Vereinsname aktualisieren (einziges Feld neben vkz, was die Spielersuche liefert)
				$objUpdate = \Database::getInstance()->prepare("UPDATE tl_dwz_ver %s WHERE id = ?")
				                                     ->set($set)
				                                     ->execute($objVerein->id);
			}
			else
			{
				// Verein in lokaler Datenbank nicht gefunden, deshalb neu anlegen
				$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_dwz_ver %s")
				                                     ->set($set)
				                                     ->execute();
			}
		}
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
				// Verein in lokaler Datenbank suchen
				$objVerein = \Database::getInstance()->prepare("SELECT * FROM tl_dwz_ver WHERE zpsver = ?")
				                                     ->execute($m->vkz);

				// Datensatz füllen
				$set = array
				(
					'tstamp'     => time(),
					'zpsver'     => $m->vkz ? $m->vkz : '',
					'status'     => $m->state ? $m->state : 'A',
					'name'       => $m->club,
					'elobase'    => '',
					'published'  => 1,
				);

				// Datensatz eintragen
				if($objVerein->numRows)
				{
					// Vereinsname aktualisieren (einziges Feld neben vkz, was die Spielersuche liefert)
					$objUpdate = \Database::getInstance()->prepare("UPDATE tl_dwz_ver %s WHERE id = ?")
					                                     ->set($set)
					                                     ->execute($objVerein->id);
				}
				else
				{
					// Verein in lokaler Datenbank nicht gefunden, deshalb neu anlegen
					$objInsert = \Database::getInstance()->prepare("INSERT INTO tl_dwz_ver %s")
					                                     ->set($set)
					                                     ->execute();
				}
			} 
		}
	}
}
