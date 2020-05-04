<?php

/**
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'dewis_cache';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{dewis_legend:hide},dewis_karteisperre_gaeste,dewis_passive_ausblenden,dewis_geburtsjahr_ausblenden,dewis_geschlecht_ausblenden,dewis_seite_spieler,dewis_seite_turnier,dewis_seite_verein,dewis_seite_verband,dewis_cache';
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['dewis_cache'] = 'dewis_cache_default,dewis_cache_verband,dewis_cache_referent';

/**
 * fields
 */

// Cache ein- oder ausschalten
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_cache'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_cache'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'clr',
		'submitOnChange'      => true
	)
);

// Anzahl Stunden, die der Standardcache gültig ist
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_cache_default'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_cache_default'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
		'rgxp'                =>'natural'
	)
);

// Anzahl Stunden, die der Verbandcache gültig ist
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_cache_verband'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_cache_verband'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50 clr',
		'rgxp'                =>'natural'
	)
);

// Anzahl Stunden, die der Referentcache gültig ist
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_cache_referent'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_cache_referent'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
		'rgxp'                =>'natural'
	)
);

// Karteikarte für Gäste sperren
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_karteisperre_gaeste'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_karteisperre_gaeste'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50 clr'
	)
);

// Karteikarte für Gäste sperren
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_passive_ausblenden'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_passive_ausblenden'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50'
	)
);

// Anzeige des Geburtsjahres ausblenden
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_geburtsjahr_ausblenden'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_geburtsjahr_ausblenden'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50'
	)
);

// Anzeige des Geschlechts ausblenden
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_geschlecht_ausblenden'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_geschlecht_ausblenden'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50'
	)
);

// Seite für das Spieler-Modul
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_seite_spieler'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_seite_spieler'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array
	(
		'mandatory'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50 clr'
	),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array
	(
		'type'                => 'hasOne',
		'load'                => 'lazy'
	)
); 

// Seite für das Turnier-Modul
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_seite_turnier'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_seite_turnier'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array
	(
		'mandatory'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array
	(
		'type'                => 'hasOne',
		'load'                => 'lazy'
	)
); 

// Seite für das Verein-Modul
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_seite_verein'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_seite_verein'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array
	(
		'mandatory'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array
	(
		'type'                => 'hasOne',
		'load'                => 'lazy'
	)
); 

// Seite für das Verband-Modul
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_seite_verband'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_seite_verband'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'foreignKey'              => 'tl_page.title',
	'eval'                    => array
	(
		'mandatory'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array
	(
		'type'                => 'hasOne',
		'load'                => 'lazy'
	)
); 
