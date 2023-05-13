<?php
namespace Schachbulle\ContaoDewisBundle\Models;

use Contao\Model;

/**
 * add properties for IDE support
 * 
 * @property string $hash
 */
class DewisSpielerModel extends \Model
{
	protected static $strTable = 'tl_dwz_spi';
	
	// if you have logic you need more often, you can implement it here
	public function setHash()
	{
		$this->hash = md5($this->id);
	}
}
