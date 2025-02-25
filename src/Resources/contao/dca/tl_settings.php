<?php

/**
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'dewis_switchedOff';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'dewis_cache';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'dewis_elobase';
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{dewis_legend:hide},dewis_switchedOff,dewis_karteisperre_gaeste,dewis_passive_ausblenden,dewis_geburtsjahr_ausblenden,dewis_geschlecht_ausblenden,dewis_seite_spieler,dewis_seite_turnier,dewis_seite_verein,dewis_seite_verband,dewis_cache,dewis_elobase,dewis_playerDefaultImage,dewis_playerImageSize,dewis_clubDefaultImage,dewis_clubImageSize,dewis_eloLocal,dewis_adminName,dewis_adminMail,dewis_apiSubject';
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['dewis_switchedOff'] = 'dewis_switchedOffText';
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['dewis_cache'] = 'dewis_cache_default,dewis_cache_verband,dewis_cache_referent';
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['dewis_elobase'] = 'dewis_elobase_host,dewis_elobase_db,dewis_elobase_user,dewis_elobase_pass,dewis_elobase_url';

/**
 * fields
 */

// DeWIS-Abfrage ausschalten
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_switchedOff'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_switchedOff'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
		'submitOnChange'      => true
	)
);

// Text im Frontend bei aktiver Abschaltung
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_switchedOffText'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_switchedOffText'],
	'inputType'               => 'textarea',
	'eval'                    => array
	(
		'tl_class'            => 'long',
		'rte'                 => 'tinyMCE',
		'helpwizard'          => true
	),
	'explanation'             => 'insertTags',
);

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
		'rgxp'                => 'natural'
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
		'rgxp'                => 'natural'
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
		'allowHtml'           => true
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

// Alte Elobase-Datenbank aktivieren
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_elobase'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_elobase'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'clr',
		'submitOnChange'      => true
	)
);

// Alte Elobase-Datenbank Host
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_elobase_host'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_elobase_host'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank Datenbank
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_elobase_db'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_elobase_db'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank Benutzer
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_elobase_user'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_elobase_user'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank Passwort
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_elobase_pass'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_elobase_pass'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'w50',
	)
);

// Alte Elobase-Datenbank URL
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_elobase_url'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_elobase_url'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'tl_class'            => 'long clr',
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_playerDefaultImage'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_playerDefaultImage'],
	'inputType'               => 'fileTree',
	'eval'                    => array
	(
		'filesOnly'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50'
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_playerImageSize'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_playerImageSize'],
	'exclude'                 => true,
	'inputType'               => 'imageSize',
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array(
		'rgxp'                => 'natural', 
		'includeBlankOption'  => true, 
		'nospace'             => true, 
		'helpwizard'          => true, 
		'tl_class'            => 'w50'
	),
	'options_callback' => static function ()
	{
		return System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance());
	},
); 

$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_clubDefaultImage'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_clubDefaultImage'],
	'inputType'               => 'fileTree',
	'eval'                    => array
	(
		'filesOnly'           => true,
		'fieldType'           => 'radio',
		'tl_class'            => 'w50 clr'
	)
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_clubImageSize'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_clubImageSize'],
	'exclude'                 => true,
	'inputType'               => 'imageSize',
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array(
		'rgxp'                => 'natural', 
		'includeBlankOption'  => true, 
		'nospace'             => true, 
		'helpwizard'          => true, 
		'tl_class'            => 'w50'
	),
	'options_callback' => static function ()
	{
		return System::getContainer()->get('contao.image.image_sizes')->getOptionsForUser(BackendUser::getInstance());
	},
); 

// Elo von lokaler Quelle laden
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_eloLocal'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_eloLocal'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'clr w50',
	)
);

// Globaler E-Mail-Absendername
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_adminName'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_adminName'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'mandatory'           => true, 
		'tl_class'            => 'w50 clr', 
	),
);

// Globale E-Mail-Absenderadresse
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_adminMail'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_adminMail'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'rgxp'                => 'email', 
		'mandatory'           => true, 
		'tl_class'            => 'w50', 
	),
);

// Betreff für API-Mails
$GLOBALS['TL_DCA']['tl_settings']['fields']['dewis_apiSubject'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['dewis_apiSubject'],
	'inputType'               => 'text',
	'eval'                    => array
	(
		'mandatory'           => false, 
		'tl_class'            => 'clr long', 
	),
);
