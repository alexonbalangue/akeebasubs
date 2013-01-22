<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class FOFFormHeaderAkeebasubsemailtemplatekey extends FOFFormHeaderFieldselectable
{
	protected function getOptions()
	{
		static $options = null;
		
		if (is_null($options))
		{
			if (!class_exists('AkeebasubsHelperEmail'))
			{
				require_once JPATH_ROOT.'/components/com_akeebasubs/helpers/email.php';
			}
			
			$options = AkeebasubsHelperEmail::getEmailKeys(1);
		}
		
		reset($options);
		
		return $options;
	}
}
