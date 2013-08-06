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

		// Make sure assigned subscriptions really do exist and normalize the list
		if(!empty($this->subscriptions)) {
			if(is_array($this->subscriptions)) {
				$subs = $this->subscriptions;
			} else {
				$subs = explode(',', $this->subscriptions);
			}
			if(empty($subs)) {
				$this->subscriptions = '';
			} else {
				$subscriptions = array();
				foreach($subs as $id) {
					$subObject = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
						->setId($id)
						->getItem();
					$id = null;
					if(is_object($subObject)) {
						if($subObject->akeebasubs_level_id > 0) {
							$id = $subObject->akeebasubs_level_id;
						}
					}
					if(!is_null($id)) $subscriptions[] = $id;
				}
				$this->subscriptions = implode(',', $subscriptions);
			}
		}

		// Check the type
		if(!in_array($this->type, array('value','percent')))
		{
			$this->type = 'value';
		}

		// Check value
		if($this->value < 0)
		{
			$this->setError(JText::_('COM_AKEEBASUBS_COUPON_ERR_VALUE'));
			$result = false;
		}
		elseif( ($this->value > 100) && ($this->type == 'percent') )
		{
			$this->value = 100;
		}

		return parent::check() && $result;
	}
}
