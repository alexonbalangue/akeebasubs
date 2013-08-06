<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableCouponsapi extends FOFTable
{
	public function __construct($table, $key, &$db, $config = array())
	{
		// I need to manually set it since the inflector creates a wrong string
		parent::__construct('#__akeebasubs_couponsapis', 'akeebasubs_couponsapi_id', $db, $config);
	}

	public function check()
	{
		$result = true;

		if(!$this->title)
		{
			$this->setError(JText::_('COM_AKEEBASUBS_COUPONSAPIS_ERR_TITLE'));
			$result = false;
		}

		if(!$this->key)
		{
			$this->key = md5(microtime());
		}

		if(!$this->password)
		{
			$this->password = md5(microtime());
		}

		return parent::check() && $result;
	}
}
