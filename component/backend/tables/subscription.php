<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableSubscription extends FOFTable
{
	/** @var object Caches the row data on load for future reference */
	private $_selfCache = null;
	
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
			JLoader::import('joomla.utilities.date');
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $this->publish_up)) {
				$this->publish_up = '2000-01-01';
			}
			$test = new JDate($this->publish_up);
			if($test->toSql() == '0000-00-00 00:00:00') {
				$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_UP'));
				$result = false;
			} else {
				$this->publish_up = $test->toSql();
			}
		}

		if(empty($this->publish_down)) {
			$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN'));
			$result = false;
		} else {
			JLoader::import('joomla.utilities.date');
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $this->publish_down)) {
				$this->publish_down = '2038-01-01';
			}
			$test = new JDate($this->publish_down);
			if($test->toSql() == '0000-00-00 00:00:00') {
				$this->setError(JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN'));
				$result = false;
			}  else {
				$this->publish_down = $test->toSql();
			}
		}
		
		// If the current date is outside the publish_up / publish_down range
		// then disable the subscription. Otherwise make sure it's enabled.
		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();
		$jDown = new JDate($this->publish_down);
		$jUp = new JDate($this->publish_up);
		
		if( ($uNow >= $jDown->toUnix()) ) {
			$this->enabled = 0;
		} elseif( ($uNow >= $jUp->toUnix()) && ($uNow < $jDown->toUnix())) {
			$this->enabled = ($this->state == 'C') ? 1 : 0;
		} else {
			$this->enabled = 0;
		}
		
		if(is_array($this->params)) {
			if(!empty($this->params)) {
				$this->params = json_encode($this->params);
			}
		}
		if(is_null($this->params) || empty($this->params)) {
			$this->params = '';
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
			// Check the confirmfree component parameter and subscription level's price
			// If it's a free subscription do not activate the user.
			if(!class_exists('AkeebasubsHelperCparams')) {
				require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
			}
			$confirmfree = AkeebasubsHelperCparams::getParam('confirmfree', 0);
			if($confirmfree) {
				$level = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
					->getItem($this->akeebasubs_level_id);
				if($level->price < 0.01) {
					// Do not activate free subscription
					return;
				}
			}
			
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
		$this->_selfCache = $result ? clone $this : null;
		
		// Convert params to an array
		if(!is_array($this->params)) {
			if(!empty($this->params)) {
				$this->params = json_decode($this->params, true);
			}
		}
		if(is_null($this->params) || empty($this->params)) {
			$this->params = array();
		}
		
		
		return parent::onAfterLoad($result);
	}
	
	/**
	 * Resets the cache when the table is reset
	 * @return bool
	 */
	public function onAfterReset() {
		$this->_selfCache = null;
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
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		
		// We don't care to trigger plugins when certain fields change
		$ignoredFields = array(
			'notes', 'processor', 'processor_key', 'net_amount', 'tax_amount',
			'gross_amount', 'tax_percent', 'params', 'akeebasubs_coupon_id',
			'akeebasubs_upgrade_id', 'akeebasubs_affiliate_id',
			'affiliate_comission', 'akeebasubs_invoice_id', 'prediscount_amount',
			'discount_amount', 'contact_flag', 'first_contact', 'second_contact'
		);
		
		$info = array(
			'status'	=>	'unmodified',
			'previous'	=> empty($this->_selfCache) ? null : $this->_selfCache,
			'current'	=> clone $this,
			'modified'	=> null
		);
		
		if(is_null($this->_selfCache) || !is_object($this->_selfCache)) {
			$info['status'] = 'new';
			$info['modified'] = clone $this;
		} else {
			$modified = array();
			foreach($this->_selfCache as $key => $value) {
				// Skip private fields
				if(substr($key,0,1) == '_') continue;
				// Skip ignored fileds
				if(in_array($key, $ignoredFields)) continue;
				// Check if the value has changed
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
		
		$this->_selfCache = clone $this;
		
		return true;
	}	
}
