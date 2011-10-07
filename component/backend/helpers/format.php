<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsHelperFormat
{
	public static function date($date, $format = null)
	{
		jimport('joomla.utilities.date');
		$jDate = new JDate($date);
		
		if(empty($format)) {
			$format = '%Y-%m-%d %H:%M';
		}
		
		return $jDate->toFormat($format);
	}
}