<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableCoupon extends FOFTable
{
	public function check() {
		$result = true;
		
		// Check for title
		if(empty($this->title)) {
			$this->setError(JText::_('COM_AKEEBASUBS_COUPON_ERR_TITLE'));
			$result = false;
		}
		
		// Check for coupon code
		if(empty($this->coupon)) {
			$this->setError(JText::_('COM_AKEEBASUBS_COUPON_ERR_COUPON'));
			$result = false;
		}
		// Normalize coupon code to uppercase
		$this->coupon = strtoupper($this->coupon);
		
		// Assign sensible publish_up and publish_down settings
		JLoader::import('joomla.utilities.date');
		if(empty($this->publish_up) || ($this->publish_up == '0000-00-00 00:00:00')) {
			$jUp = new JDate();
			$this->publish_up = $jUp->toSql();
		} else {
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $this->publish_up)) {
				$this->publish_up = '2001-01-01';
			}
			$jUp = new JDate($this->publish_up);
		}
		
		if(empty($this->publish_down) || ($this->publish_down == '0000-00-00 00:00:00')) {
			$jDown = new JDate('2030-01-01 00:00:00');
			$this->publish_down = $jDown->toSql();
		} else {
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $this->publish_down)) {
				$this->publish_down = '2037-01-01';
			}
			$jDown = new JDate($this->publish_down);
		}
		
		if($jDown->toUnix() < $jUp->toUnix()) {
			$temp = $this->publish_up;
			$this->publish_up = $this->publish_down;
			$this->publish_down = $temp;
		} elseif($jDown->toUnix() == $jUp->toUnix()) {
			$jDown = new JDate('2030-01-01 00:00:00');
			$this->publish_down = $jDown->toSql();
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
		
		// Make sure the specified user (if any) exists
		if(!empty($this->user)) {
			$userObject = JFactory::getUser($this->user);
			$this->user = null;
			if(is_object($userObject)) {
				if($userObject->id > 0) {
					$this->user = $userObject->id;
				}
			}
		}
		
		// Make sure assigned usergroups is a string
		if (!empty($this->usergroups)) {
			if (is_array($this->usergroups)) {
				$this->usergroups = implode(',', $this->usergroups);
			}
		}

		// Check the hits limit
		if($this->hitslimit <= 0) {
			$this->hitslimit = 0;
		}
		
		// Check the type
		if(!in_array($this->type, array('value','percent'))) {
			$this->type = 'value';
		}
		
		// Check value
		if($this->value < 0) {
			$this->setError(JText::_('COM_AKEEBASUBS_COUPON_ERR_VALUE'));
			$result = false;
		} elseif( ($this->value > 100) && ($this->type == 'percent') ) {
			$this->value = 100;
		}
		
		return $result;
	}
	
	function delete( $oid=null )
	{
		$joins = array(
			array(
				'label'		=> 'subscriptions',			// Used to construct the error text
				'name'		=> '#__akeebasubs_subscriptions', // Foreign table
				'idfield'	=> 'akeebasubs_coupon_id',	// Field name on this table
				'joinfield'	=> 'akeebasubs_coupon_id',	// Foreign table field
				'idalias'	=> 'coupon_id',				// Used in the query
			)
		);
		if($this->canDelete($oid, $joins))
		{
			return parent::delete($oid);
		}
		else
		{
			return false;
		}
	}
}