<?php

namespace Schachbulle\ContaoDewisBundle\Modules;

/*
 */

class ApiForm extends \Module
{

	protected $strTemplate = 'mod_apiform';

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### API-ANTRAGSFORMULAR ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/*********************************************************
		** Ausgabe des Formular für ein neues Thema
		*/

		$this->Template = new \FrontendTemplate($this->strTemplate);

		$objForm = new \Haste\Form\Form('newapiform', 'POST', function($objHaste)
		{
			return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
		});

		$objForm->addFormField('verein', array(
			'label'         => 'Dein Vereinsname',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('name', array(
			'label'         => 'Dein Vor- und Nachname',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('email', array(
			'label'         => 'Deine E-Mail-Adresse',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'rgxp'=>'email', 'class'=>'form-control')
		));
		$objForm->addFormField('ip', array(
			'label'         => 'IP-Adresse der anfragenden Website',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('module', array(
			'label'         => 'Module auswählen',
			'inputType'     => 'checkbox',
			'options'       => array('verein', 'verband', 'spieler'),
			'reference'     => array('verein' => 'Verein', 'verband' => 'Verband', 'spieler' => 'Spieler'), 
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('info', array(
			'label'         => 'Bemerkungen',
			'inputType'     => 'textarea',
			'eval'          => array('mandatory'=>false, 'rte'=>'tinyMCE', 'class'=>'form-control')
		));
		// Submit-Button hinzufügen
		$objForm->addFormField('submit', array(
			'label'         => 'Absenden',
			'inputType'     => 'submit',
			'eval'          => array('class'=>'btn btn-primary')
		));
		$objForm->addCaptchaFormField('captcha');

		// validate() prüft auch, ob das Formular gesendet wurde
		if($objForm->validate())
		{
			// Alle gesendeten und analysierten Daten holen (funktioniert nur mit POST)
			$arrData = $objForm->fetchAll();
			$return = self::saveApiform($arrData); // Daten sichern
			// Seite neu laden
			if($return) 
			{
				$this->Template->form = 'versendet';
				\Controller::addToUrl('send=1'); // Hat keine Auswirkung, verhindert aber das das Formular ausgefüllt ist
			}
			else
			{
				$this->Template->fehler = 'Der Host existiert bereits!';
				$this->Template->form = $objForm->generate();
			}
			//\Controller::reload();
		}
		else
		{
			// Formular als String zurückgeben
			$this->Template->form = $objForm->generate();
		}
	}

	protected function saveApiform($data)
	{
		$zeit = time();
		$data['info'] = html_entity_decode($data['info']);

		// API-Tabelle aktualisieren, wenn Host noch nicht vorhanden ist
		$objRecord = \Database::getInstance()->prepare('SELECT * FROM tl_dwz_api WHERE ip=?')
		                                     ->execute($data['ip']);
		if($objRecord->numRows > 0)
		{
			// Host bereits vorhanden
			return false;
		}
		else
		{
			$history = array(array(
				'history_date' => $zeit,
				'history_info' => 'Antragstellung durch '.$data['name'],
			));
			$set = array
			(
				'tstamp'         => $zeit,
				'antragsdatum'   => $zeit,
				'verein'         => $data['verein'],
				'name'           => $data['name'],
				'email'          => $data['email'],
				'modules'        => @serialize($data['module']),
				'vermerk'        => $data['info'],
				'key'            => implode('-', str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 28), 4)),
				'ip'             => $data['ip'],
				'history'        => serialize($history),
				'published'      => '1',
			);
			$objRecord = \Database::getInstance()->prepare('INSERT INTO tl_dwz_api %s')
			                                     ->set($set)
			                                     ->execute();

			// Email verschicken
			$objEmail = new \Email();
			$objEmail->from = $GLOBALS['TL_CONFIG']['dewis_adminMail'];
			$objEmail->fromName = $GLOBALS['TL_CONFIG']['dewis_adminName'];
			$objEmail->sendBcc($GLOBALS['TL_CONFIG']['dewis_adminName'].' <'.$GLOBALS['TL_CONFIG']['dewis_adminMail'].'>');
			$objEmail->subject = $GLOBALS['TL_CONFIG']['dewis_apiSubject'];
			// Kommentar zusammenbauen
			$objEmail->html = '<p>Ihr Name: <b>'.$set['name'].'</b><br>';
			$objEmail->html .= 'Ihr Verein: <b>'.$set['verein'].'</b><br>';
			$objEmail->html .= 'Ihre Bemerkungen: <b>'.$set['vermerk'].'</b></p>';
			$objEmail->html .= '<p>IP-Adresse: <b>'.$set['ip'].'</b><br>';
			$objEmail->html .= 'API-Schlüssel: <b>'.$set['key'].'</b><br>';
			$objEmail->html .= 'Module: <b>'.implode(', ', $data['module']).'</b></p>';
			$objEmail->html .= '<p>Der API-Schlüssel ist nur für die angegebenen IP-Adresse gültig!</p>';
			$objEmail->html .= '<p>Wir behalten uns vor, den Zugang jederzeit zu sperren, sollten die uns übermittelten Daten nicht stimmen oder der Service von uns unterbrochen oder eingestellt werden.</p>';
			$objEmail->html .= '<p>Ihr Deutscher Schachbund</p><p><i>Diese E-Mail wurde automatisch erstellt.</i></p>';
			$objEmail->sendTo($data['email']);
			return true;
		}
	}

}
