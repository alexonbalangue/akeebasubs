<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableSubscription extends FOFTable
{
	/** @var object Caches the row data on load for future reference */
	private $selfCache = null;
	
	public $_dontCheckPaymentID = false;
	
	/**
	 * Validates the subscription row
	 * @return boolean True if the row validates
	 */
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
		
		if(empty($this->processor_key) && !$this->_dontCheckPaymentID) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR_KEY'));
			$result = false;
		}
		
		if(!in_array($this->state, array('N','P','C','X'))) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_STATE'));
			$result = false;
		}
		
		return $result;
	}
	
	/**
	 * Automatically run some actions after a subscription row is saved
	 */
	protected function onAfterStore()
	{
		// Unblock users when their payment is Complete
		$this->userUnblock();
		
		// Run the plugins on subscription modification
		$this->subNotifiable();
		
		return parent::onAfterStore();
	}
	
	/**
	 * Automatically unblock a user whose subscription is paid (status = C) and
	 * enabled, if he's not already enabled.
	 */
	private function userUnblock()
	{
		// Make sure the payment is complete
		if($this->state != 'C') return;
		
		// Make sure the subscription is enabled
		if(!$this->enabled) return;
		
		// Paid and enabled subscription; enable the user if he's not already enabled
		$user = JFactory::getUser($this->user_id);
		if($user->block) {
			$updates = array(
				'block'			=> 0,
				'activation'	=> ''
			);
			$user->bind($updates);
			$user->save($updates);
		}
	}
	
	/**
	 * Caches the loaded data so that we can check them for modifications upon
	 * saving the row.
	 */
	public function onAfterLoad(&$result) {
		$this->selfCache = $result ? clone $this : null;
		return parent::onAfterLoad($result);
	}
	
	/**
	 * Resets the cache when the table is reset
	 * @return bool
	 */
	public function onAfterReset() {
		$this->selfCache = null;
		return parent::onAfterReset();
	}
	
	/**
	 * Notifies the plugins if a subscription has changed
	 * 
	 * @return bool
	 */
	private function subNotifiable()
	{
		// Load the "akeebasubs" plugins
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		
		$info = array(
			'status'	=>	'unmodified',
			'previous'	=> empty($this->selfCache) ? null : $this->selfCache,
			'current'	=> clone $this,
			'modified'	=> null
		);
		
		if(is_null($this->selfCache) || !is_object($this->selfCache)) {
			$info['status'] = 'new';
			$info['modified'] = clone $this;
		} else {
			$modified = array();
			foreach($this->selfCache as $key => $value) {
				if($this->$key != $value) {
					$info['status'] = 'modified';
					$modified[$key] = $value;
				}
			}
			$info['modified'] = (object)$modified;
		}
		
		if($info['status'] != 'unmodified') {
			// Fire plugins (onAKSubscriptionChange) passing ourselves as a parameter
			$jResponse = $app->triggerEvent('onAKSubscriptionChange', array($this, $info));
		}
		
		$this->selfCache = clone $this;
		
		return true;
	}	
}
