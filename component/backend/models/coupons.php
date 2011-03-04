<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsModelCoupons extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('ordering'	, 'int')
			->insert('enabled'	, 'int')
			->insert('type'		, 'cmd');
	}

	protected function _buildQueryWhere(KDatabaseQuery $query)
	{
		$state = $this->_state;

		if(is_numeric($state->ordering)) {
			$query->where('tbl.ordering','=', $state->ordering);
		}
		
		if(is_numeric($state->enabled)) {
			$query->where('tbl.enabled','=', $state->enabled);
		}
		
		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query->where('title', 'LIKE',  $search, 'OR');
			$query->where('coupon', 'LIKE',  $search, 'OR');
		}
		
		if($state->type)
		{
			$query->where('type', '=', $state->type);
		}
		
		parent::_buildQueryWhere($query);
	}
	
	public function validate($data)
	{
		$ret = array();
		
		// Do not fire if it's not in visual editor mode
		if($data->_visual != 1) return $ret;
		
		// Check for title
		if(empty($data->title)) {
			$ret[] = JText::_('COM_AKEEBASUBS_COUPON_ERR_TITLE');
		}
		
		// Check for coupon code
		if(empty($data->coupon)) {
			$ret[] = JText::_('COM_AKEEBASUBS_COUPON_ERR_COUPON');
		}
		// Normalize coupon code to uppercase
		$data->coupon = strtoupper($data->coupon);
		
		// Assign sensible publish_up and publish_down settings
		jimport('joomla.utilities.date');
		if(empty($data->publish_up) || ($data->publish_up == '0000-00-00 00:00:00')) {
			$jUp = new JDate();
			$data->publish_up = $jUp->toMySQL();
		} else {
			$jUp = new JDate($data->publish_up);
		}
		
		if(empty($data->publish_down) || ($data->publish_down == '0000-00-00 00:00:00')) {
			$jDown = new JDate('2030-01-01 00:00:00');
			$data->publish_down = $jDown->toMySQL();
		} else {
			$jDown = new JDate($data->publish_down);
		}
		
		if($jDown->toUnix() < $jUp->toUnix()) {
			$temp = $this->publish_up;
			$data->publish_up = $this->publish_down;
			$data->publish_down = $temp;
		} elseif($jDown->toUnix() == $jUp->toUnix()) {
			$jDown = new JDate('2030-01-01 00:00:00');
			$data->publish_down = $jDown->toMySQL();
		}
		
		// Make sure assigned subscriptions really do exist and normalize the list
		if(!empty($data->subscriptions)) {
			$subs = explode(',', $data->subscriptions);
			if(empty($subs)) {
				$data->subscriptions = '';
			} else {
				$subscriptions = array();
				foreach($subs as $id) {
					$subObject = KFactory::tmp('admin::com.akeebasubs.model.levels')->id($id)->getItem();
					$id = null;
					if(is_object($subObject)) {
						if($subObject->id > 0) {
							$id = $subObject->id;
						}
					}
					if(!is_null($id)) $subscriptions[] = $id;
				}
				$data->subscriptions = implode(',', $subscriptions);
			}
		}
		
		// Make sure the specified user (if any) exists
		if(!empty($data->user)) {
			$userObject = JFactory::getUser($data->user);
			$data->user = null;
			if(is_object($userObject)) {
				if($userObject->id > 0) {
					$data->user = $userObject->id;
				}
			}
		}
		
		// Check the hits limit
		if($data->hitslimit <= 0) {
			$data->hitslimit = 0;
		}
		
		// Check the type
		if(!in_array($data->type, array('value','percent'))) {
			$data->type = 'value';
		}
		
		// Check value
		if(!$data->value || ($data->value <= 0)) {
			$ret[] = JText::_('COM_AKEEBASUBS_COUPON_ERR_VALUE');
		} elseif( ($data->value > 100) && ($data->type == 'percent') ) {
			$data->value = 100;
		}
		
		// Automatic ordering
		if($data->ordering == 0) {
			$lastEntry = KFactory::tmp('admin::com.akeebasubs.model.coupons')
			->sort('ordering')->direction('DESC')->limit(1)->getList();
			$rawList = $lastEntry->getData();
			if(!empty($rawList)) {
				$rawItem = array_pop($rawList);
				$data->ordering = $rawItem['ordering'] + 1;
			} else {
				$data->ordering = 1;
			}
		}
		
		// NOTE: Created, modified, locked and hits are handled by Koowa's table
		// behaviours, therefore no code is necessary here ;)
		
		return $ret;
	}
	
}