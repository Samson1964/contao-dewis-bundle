<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package News
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Table tl_dwz_api
 */
$GLOBALS['TL_DCA']['tl_dwz_api'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'                  => 'primary',
				'ip'                  => 'index',
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('antragsdatum'),
			'flag'                    => 6,
			'panelLayout'             => 'filter;sort,search,limit',
		),
		'label' => array
		(
			'fields'                  => array('antragsdatum', 'verein', 'name', 'ip'),
			'format'                  => '%s %s %s %s',
			'showColumns'             => true,
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_dwz_api']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_dwz_api']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_dwz_api']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_dwz_api']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_dwz_api']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{name_legend},antragsdatum,verein,name,email,vermerk;{key_legend},key,ip,modules;{info_legend:hide},history,info;{statistik_legend:hide},statistik;{publish_legend},start,stop,published'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'hits' => array
		(
			'sql'                     => 'blob NULL'
		),
		'statistik' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['statistik'],
			'input_field_callback'    => array('tl_dwz_api', 'getStatistik'),
		),
		'antragsdatum' => array
		(
			'exclude'                 => true,
			'flag'                    => 6,
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['antragsdatum'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'verein' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['verein'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 40,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(40) NOT NULL default ''"
		),
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['name'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 40,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(40) NOT NULL default ''"
		),
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['email'],
			'exclude'                 => true,
			'search'                  => false,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'maxlength'           => 40,
				'tl_class'            => 'w50',
				'rgxp'                => 'email'
			),
			'sql'                     => "varchar(40) NOT NULL default ''"
		),
		'vermerk' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['vermerk'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array
			(
				'tl_class'            => 'clr long',
			),
			'sql'                     => "text NULL"
		),
		'key' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['key'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 128,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'ip' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['ip'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'maxlength'           => 40,
				'unique'              => true,
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(40) NOT NULL default ''"
		),
		'modules' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['modules'],
			'inputType'               => 'checkbox',
			'options'                 => array('verein', 'verband', 'spieler'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_dwz_api']['modules_options'],
			'eval'                    => array('multiple'=>true),
			'sql'                     => 'blob NULL'
		),
		'history' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['history'],
			'exclude'                 => true,
			'inputType'               => 'multiColumnWizard',
			'eval'                    => array
			(
				'tl_class'            => 'clr',
				'buttonPos'           => 'top',
				'columnFields'        => array
				(
					'history_date' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_dwz_api']['history_date'],
						'exclude'               => true,
						'inputType'             => 'text',
						'eval'                  => array
						(
							'rgxp'              => 'date',
							'mandatory'         => false,
							'doNotCopy'         => true,
							'datepicker'        => true,
							'tl_class'          => 'wizard',
							'style'             => 'width:150px;'
						),
						'load_callback' => array
						(
							array('tl_dwz_api', 'loadDate')
						),
					),
					'history_info' => array
					(
						'label'                 => &$GLOBALS['TL_LANG']['tl_dwz_api']['history_info'],
						'exclude'               => true,
						'inputType'             => 'textarea',
						'eval'                  => array
						(
							'style'             => 'width:90%;'
						)
					),
				)
			),
			'sql'                   => "blob NULL"
		),
		'info' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['info'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE'),
			'explanation'             => 'insertTags',
			'sql'                     => "text NULL"
		),
		'start' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['start'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'stop' => array
		(
			'exclude'                 => true,
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['stop'],
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "varchar(10) NOT NULL default ''"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_dwz_api']['published'],
			'inputType'               => 'checkbox',
			'exclude'                 => true,
			'filter'                  => true,
			'default'                 => 1,
			'eval'                    => array
			(
				'tl_class'            => 'w50',
				'isBoolean'           => true
			),
			'sql'                     => "char(1) NOT NULL default ''"
		),
	)
);


/**
 * Class tl_dwz_api
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    News
 */
class tl_dwz_api extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Set the timestamp to 00:00:00 (see #26)
	 *
	 * @param integer $value
	 *
	 * @return integer
	 */
	public function loadDate($value)
	{
		if($value) return strtotime(date('Y-m-d', (int)$value) . ' 00:00:00');
		return '';
	}

	public function getStatistik(\DataContainer $dc)
	{
		// Statistik laden
		$statistik = @unserialize($dc->activeRecord->hits);
		$max = 10;
		$zaehler = 0;
		if(is_array($statistik))
		{
			$ausgabe = '<table class="tl_listing showColumns" style="margin-top:15px;"><tbody>';
			$ausgabe .= '<tr>';
			$ausgabe .= '<th class="tl_folder_tlist">Datum</th>';
			$ausgabe .= '<th class="tl_folder_tlist">Adresse</th>';
			$ausgabe .= '<th class="tl_folder_tlist">Modul</th>';
			$ausgabe .= '<th class="tl_folder_tlist">Fehler</th>';
			$ausgabe .= '<th class="tl_folder_tlist">Status</th>';
			$ausgabe .= '</tr>';
			$oddeven = 'odd';
			foreach(array_reverse($statistik) as $item)
			{
				$zaehler++;
				$oddeven = $oddeven == 'odd' ? 'even' : 'odd';
				$ausgabe .= '<tr class="'.$oddeven.'" onmouseover="Theme.hoverRow(this,1)" onmouseout="Theme.hoverRow(this,0)">';
				$ausgabe .= '<td class="tl_file_list">'.date('d.m.Y H:i', $item['datum']).'</td>';
				$ausgabe .= '<td class="tl_file_list">'.(isset($item['ip']) ? $item['ip'] : '').'</td>';
				$ausgabe .= '<td class="tl_file_list">'.(isset($item['modul']) ? $item['modul'] : '').'</td>';
				$ausgabe .= '<td class="tl_file_list">'.($item['fehler'] ? 'Ja' : 'Nein').'</td>';
				$ausgabe .= '<td class="tl_file_list">'.$item['status'].'</td>';
				$ausgabe .= '</tr>';
				if($zaehler == $max) break;
			}
			$ausgabe .= '</tbody></table>';
			$ausgabe .= '<p style="margin: 18px 0 10px 5px;">Es werden '.$zaehler.' von '.count($statistik).' Datensätzen insgesamt angezeigt.</p>';
		}
		else
		{
			$ausgabe = 'Noch keine Daten';
		}
		
		$text =
		'<div class="long widget" style="margin-top:10px;">
		<h3><label for="ctrl_statistik">'.$GLOBALS['TL_LANG']['tl_dwz_api']['statistik'][0].'</label></h3>
		'.$ausgabe.'
		<p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_dwz_api']['statistik'][1].'</p>
		</div>';

		return $text;
	}

}
