<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   DeWIS
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

//define(CACHE_AKTIV, false); // Cachestatus
//define(CACHE_TIME, 36000); // Cachezeit (36000 = 10h)
//define(CACHE_TIME_FAKTOR_VERBAND, 20); // CACHE_TIME * Faktor = gesamte Cachelebenszeit
//define(CACHE_TIME_FAKTOR_REFERENT, 5); // CACHE_TIME * Faktor = gesamte Cachelebenszeit

define('KARTEISPERRE_GAESTE', false); // Anzeige von Karteikarten für nichtangemeldete Besucher gesperrt
define('PASSIVE_AUSBLENDEN', false); // Anzeige passiver Spieler
define('GEBURTSJAHR_AUSBLENDEN', true); // Anzeige des Geburtsjahres
define('GESCHLECHT_AUSBLENDEN', true); // Anzeige des Geschlechtes

define('ALIAS_SPIELER', 'spieler'); // Spielerseite
define('ALIAS_VEREIN', 'verein'); // Vereineseite
define('ALIAS_VERBAND', 'verband'); // Verbändeseite
define('ALIAS_TURNIER', 'turnier'); // Turniereseite

/**
 * Backend-Module
 */

$GLOBALS['BE_MOD']['dewis'] = array
(
	'dwz-spieler'    => array
	(
		'tables'         => array
		(
			'tl_dwz_spi', 
			'tl_dwz_spiver',
			'tl_dwz_kar',
			'tl_dwz_inf',
			'tl_dwz_fid',
		),
		'icon'           => 'bundles/contaodewis/images/icon_spieler.png',
	),
	'dwz-vereine'    => array
	(
		'tables'         => array
		(
			'tl_dwz_ver', 
		),
		'icon'           => 'bundles/contaodewis/images/icon_vereine.png',
	),
	'dwz-turniere'    => array
	(
		'tables'         => array
		(
			'tl_dwz_tur', 
		),
		'icon'           => 'bundles/contaodewis/images/icon_turniere.png',
	),
	'dwz-bearbeiter'    => array
	(
		'tables'         => array
		(
			'tl_dwz_bea', 
		),
		'icon'           => 'bundles/contaodewis/images/icon_bearbeiter.png',
	),
);

/**
 * Frontend-Module
 */

$GLOBALS['FE_MOD']['dewis'] = array
(
	'dewis_spieler'         => 'Schachbulle\ContaoDewisBundle\Classes\Spieler',
	'dewis_verein'          => 'Schachbulle\ContaoDewisBundle\Classes\Verein',
	'dewis_verband'         => 'Schachbulle\ContaoDewisBundle\Classes\Verband',
	'dewis_turnier'         => 'Schachbulle\ContaoDewisBundle\Classes\Turnier',
	'dewis_suche'           => 'Schachbulle\ContaoDewisBundle\Classes\Suche',
);

// http://de.contaowiki.org/Strukturierte_URLs
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][] = array('Schachbulle\ContaoDewisBundle\Helper\DeWIS', 'getParamsFromUrl');

if (TL_MODE == 'BE') 
{
	//echo "<pre>";
	//print_r(get_defined_constants());
	//echo "</pre>";
}

/**
 * Purge jobs / Reinigungsarbeiten
 */
$GLOBALS['TL_PURGE']['custom']['dewis'] = array
(
	'callback' => array('Schachbulle\ContaoDewisBundle\Helper\DeWIS', 'purgeCache')
);

/**
 * -------------------------------------------------------------------------
 * Voreinstellungen Contao-BE System -> Einstellungen
 * -------------------------------------------------------------------------
 */

$GLOBALS['TL_CONFIG']['dewis_cache'] = 1;
$GLOBALS['TL_CONFIG']['dewis_cache_default'] = 4;
$GLOBALS['TL_CONFIG']['dewis_cache_referent'] = 24;
$GLOBALS['TL_CONFIG']['dewis_cache_verband'] = 48;
