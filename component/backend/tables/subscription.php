<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableSubscription extends FOFTable
{
	public function check() {
		$result = true;
		
		if(empty($this->user_id)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_USER_ID'));
			$result = false;
		}
		
		if(empty($this->akeebasubs_level_id)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_LEVEL_ID'));
			$result = false;
		}
		
		if(empty($this->publish_up)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_UP'));
			$result = false;
		} else {
			jimport('joomla.utilities.date');
			$test = new JDate($this->publish_up);
			if($test->toMySQL() == '0000-00-00 00:00:00') {
				$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_UP'));
				$result = false;
			} else {
				$this->publish_up = $test->toMySQL();
			}
		}

		if(empty($this->publish_down)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN'));
			$result = false;
		} else {
			jimport('joomla.utilities.date');
			$test = new JDate($this->publish_down);
			if($test->toMySQL() == '0000-00-00 00:00:00') {
				$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN'));
				$result = false;
			}  else {
				$this->publish_down = $test->toMySQL();
			}
		}
		
		if(empty($this->processor)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR'));
			$result = false;
		}
		
		if(empty($this->processor_key)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR_KEY'));
			$result = false;
		}
		
		if(!in_array($this->state, array('N','P','C','X'))) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_STATE'));
			$result = false;
		}
		
		return $result;
	}
}
