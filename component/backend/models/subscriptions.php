<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelSubscriptions extends FOFModel
{
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled','','cmd');

		return (object)array(
			'search'		=> $this->getState('search',null,'string'),
			'title'			=> $this->getState('title',null,'string'),
			'enabled'		=> $enabled,
			'level'			=> $this->getState('level',null,'int'),
			'publish_up'	=> $this->getState('publish_up',null,'string'),
			'publish_down'	=> $this->getState('publish_down',null,'string'),
			'user_id'		=> $this->getState('user_id',null,'int'),
			'paystate'		=> $this->getState('paystate',null,'string'),
			'processor'		=> $this->getState('processor',null,'string'),
			'paykey'		=> $this->getState('paykey',null,'string'),
			'since'			=> $this->getState('since',null,'string'),
			'until'			=> $this->getState('until',null,'string'),
			'contact_flag'	=> $this->getState('contact_flag',null,'int'),
			'expires_from'	=> $this->getState('expires_from',null,'string'),
			'expires_to'	=> $this->getState('expires_to',null,'string'),
			'refresh'		=> $this->getState('refresh',null,'int'),
			'groupbydate'	=> $this->getState('groupbydate',null,'int'),
			'groupbylevel'	=> $this->getState('groupbylevel',null,'int'),
			'moneysum'		=> $this->getState('moneysum',null,'int'),
			'coupon_id'		=> $this->getState('coupon_id',null,'int'),
			'filter_discountmode' => $this->getState('filter_discountmode',null,'cmd'),
			'filter_discountcode' => $this->getState('filter_discountcode',null,'cmd'),
			'nozero'		=> $this->getState('nozero',null,'int'),
		);
	}

	public function buildCountQuery() {
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if($state->refresh == 1) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__akeebasubs_subscriptions').' AS '.$db->qn('tbl'));

			//$this->_buildQueryFrom($query);
			$this->_buildQueryJoins($query);
			$this->_buildQueryWhere($query);
			$this->_buildQueryGroup($query);

			// $query retruns X rows, where X is the number of users. We need the count of users, so...
			$query2 =  $db->getQuery(true)
					->select('COUNT(*)')
					->from( '('.(string)$query.') AS '.$db->qn('tbl'));

			return $query2;
		} elseif($state->moneysum == 1) {
			$query = $db->getQuery(true)
				->select('SUM('.$db->qn('net_amount').') AS '.$db->qn('x'))
				->from($db->qn('#__akeebasubs_subscriptions').' AS '.$db->qn('tbl'));

			//$this->_buildQueryFrom($query);
			$this->_buildQueryJoins($query);
			$this->_buildQueryWhere($query);
			$this->_buildQueryGroup($query);

			return $query;
		} else {
			return parent::buildCountQuery();
		}
	}

	protected function _buildQueryJoins($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if($state->groupbydate == 1) {
			return;
		} elseif($state->groupbylevel == 1) {
			$query
				->join('INNER', $db->qn('#__akeebasubs_levels').' AS '.$db->qn('l').' ON '.
						$db->qn('l').'.'.$db->qn('akeebasubs_level_id').' = '.
						$db->qn('tbl').'.'.$db->qn('akeebasubs_level_id'))
				;
		} else {
			$query
				->join('INNER', $db->qn('#__akeebasubs_levels').' AS '.$db->qn('l').' ON '.
						$db->qn('l').'.'.$db->qn('akeebasubs_level_id').' = '.
						$db->qn('tbl').'.'.$db->qn('akeebasubs_level_id'))
				->join('LEFT OUTER', $db->qn('#__users').' AS '.$db->qn('u').' ON '.
						$db->qn('u').'.'.$db->qn('id').' = '.
						$db->qn('tbl').'.'.$db->qn('user_id'))
				->join('LEFT OUTER', $db->qn('#__akeebasubs_users').' AS '.$db->qn('a').' ON '.
						$db->qn('a').'.'.$db->qn('user_id').' = '.
						$db->qn('tbl').'.'.$db->qn('user_id'))
			;
		}


	}

	protected function _buildQueryColumns($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if($state->refresh == 1) {
			$query->select(array(
				$db->qn('tbl').'.'.$db->qn('akeebasubs_subscription_id'),
				$db->qn('tbl').'.'.$db->qn('user_id')
			));
		} elseif($state->groupbydate == 1) {
			$query->select(array(
				'DATE('.$db->qn('created_on').') AS '.$db->qn('date'),
				'SUM('.$db->qn('net_amount').') AS '.$db->qn('net'),
				'COUNT('.$db->qn('akeebasubs_subscription_id').') AS '.$db->qn('subs')
			));
		} elseif($state->groupbylevel == 1) {
			$query->select(array(
				$db->qn('l').'.'.$db->qn('title'),
				'SUM('.$db->qn('tbl').'.'.$db->qn('net_amount').') AS '.$db->qn('net'),
				'COUNT('.$db->qn('tbl').'.'.$db->qn('akeebasubs_subscription_id').') AS '.$db->qn('subs'),
			));
		} else {
			$query->select(array(
				$db->qn('tbl').'.*',
				$db->qn('l').'.'.$db->qn('title'),
				$db->qn('l').'.'.$db->qn('image'),
				$db->qn('l').'.'.$db->qn('akeebasubs_levelgroup_id'),
				$db->qn('u').'.'.$db->qn('name'),
				$db->qn('u').'.'.$db->qn('username'),
				$db->qn('u').'.'.$db->qn('email'),
				$db->qn('u').'.'.$db->qn('block'),
				$db->qn('a').'.'.$db->qn('isbusiness'),
				$db->qn('a').'.'.$db->qn('businessname'),
				$db->qn('a').'.'.$db->qn('occupation'),
				$db->qn('a').'.'.$db->qn('vatnumber'),
				$db->qn('a').'.'.$db->qn('viesregistered'),
				$db->qn('a').'.'.$db->qn('taxauthority'),
				$db->qn('a').'.'.$db->qn('address1'),
				$db->qn('a').'.'.$db->qn('address2'),
				$db->qn('a').'.'.$db->qn('city'),
				$db->qn('a').'.'.$db->qn('state').' AS '.$db->qn('userstate'),
				$db->qn('a').'.'.$db->qn('zip'),
				$db->qn('a').'.'.$db->qn('country'),
				$db->qn('a').'.'.$db->qn('params').' AS '.$db->qn('userparams'),
				$db->qn('a').'.'.$db->qn('notes').' AS '.$db->qn('usernotes'),
			));

			$order = $this->getState('filter_order', 'akeebasubs_subscription_id', 'cmd');
			if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_subscription_id';
			$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
			$query->order($order.' '.$dir);
		}
	}

	protected function _buildQueryGroup($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if($state->refresh == 1) {
			$query->group(array(
				$db->qn('tbl').'.'.$db->qn('user_id')
			));
		} elseif($state->groupbydate == 1) {
			$query->group(array(
				'DATE('.$db->qn('tbl').'.'.$db->qn('created_on').')'
			));
		} elseif($state->groupbylevel == 1) {
			$query->group(array(
				$db->qn('tbl').'.'.$db->qn('akeebasubs_level_id')
			));
		}
	}

	protected function _buildQueryWhere($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if($state->refresh == 1) {
			return;
		}

		JLoader::import('joomla.utilities.date');

		if($state->paystate) {
			$states_temp = explode(',', $state->paystate);
			$states = array();
			foreach($states_temp as $s) {
				$s = strtoupper($s);
				if(!in_array($s, array('C','P','N','X'))) continue;
				$states[] = $db->q($s);
			}
			if(!empty($states)) {
				$query->where(
					$db->qn('tbl').'.'.$db->qn('state').' IN ('.
						implode(',',$states).')'
				);
			}
		}

		if($state->processor) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('processor').' = '.
					$db->q($state->processor)
			);
		}

		if($state->paykey) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('processor_key').' LIKE '.
					$db->q('%'.$state->paykey.'%')
			);
		}

		if(!$state->groupbydate && !$state->groupbylevel)
		{
			if(is_numeric($state->enabled)) {
				$query->where(
					$db->qn('tbl').'.'.$db->qn('enabled').' = '.
						$db->q($state->enabled)
				);
			}

			if($state->title) {
				$search = '%'.$state->title.'%';
				$query->where(
					$db->qn('tbl').'.'.$db->qn('title').' LIKE '.
						$db->q($search)
				);
			}

			if($state->search)
			{
				$search = '%'.$state->search.'%';
				// @todo Try to use JDatabase quoting functions on this beast without a strong urge to commit suicide
				$query->where(
					'CONCAT(IF(u.name IS NULL,"",u.name),IF(u.username IS NULL,"",u.username),IF(u.email IS NULL, "", u.email),IF(a.businessname IS NULL, "", a.businessname), IF(a.vatnumber IS NULL,"",a.vatnumber)) LIKE '.
						$db->q($search)
				);
			}

			if(is_numeric($state->level) && ($state->level > 0)) {
				$query->where(
					$db->qn('tbl').'.'.$db->qn('akeebasubs_level_id').' = '.
						$db->q($state->level)
				);
			}

			if(is_numeric($state->coupon_id) && ($state->coupon_id > 0)) {
				$query->where(
					$db->qn('tbl').'.'.$db->qn('akeebasubs_coupon_id').' = '.
						$db->q($state->coupon_id)
				);
			}

			if(is_numeric($state->user_id) && ($state->user_id > 0)) {
				$query->where(
					$db->qn('tbl').'.'.$db->qn('user_id').' = '.
						$db->q($state->user_id)
				);
			}

			if(is_numeric($state->contact_flag)) {
				$query->where(
					$db->qn('tbl').'.'.$db->qn('contact_flag').' = '.
						$db->q($state->contact_flag)
				);
			}

			// Filter the dates
			$from = trim($state->publish_up);
			if(empty($from)) {
				$from = '';
			} else {
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
				if(!preg_match($regex, $from)) {
					$from = '2001-01-01';
				}
				$jFrom = new JDate($from);
				$from = $jFrom->toUnix();
				if($from == 0) {
					$from = '';
				} else {
					$from = $jFrom->toSql();
				}
			}

			$to = trim($state->publish_down);
			if(empty($to) || ($to == '0000-00-00') || ($to == '0000-00-00 00:00:00')) {
				$to = '';
			} else {
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
				if(!preg_match($regex, $to)) {
					$to = '2037-01-01';
				}
				$jTo = new JDate($to);
				$to = $jTo->toUnix();
				if($to == 0) {
					$to = '';
				} else {
					$to = $jTo->toSql();
				}
			}

			if(!empty($from) && !empty($to)) {
				// Filter from-to dates
				$query->where(
					$db->qn('tbl').'.'.$db->qn('publish_up').' >= '.
						$db->q($from)
				);
				$query->where(
					$db->qn('tbl').'.'.$db->qn('publish_up').' <= '.
						$db->q($to)
				);
			} elseif(!empty($from) && empty($to)) {
				// Filter after date
				$query->where(
					$db->qn('tbl').'.'.$db->qn('publish_up').' >= '.
						$db->q($from)
				);
			} elseif(empty($from) && !empty($to)) {
				// Filter up to a date
				$query->where(
					$db->qn('tbl').'.'.$db->qn('publish_down').' <= '.
						$db->q($to)
				);
			}

			// Dicsount mode and code search
			$coupon_ids = array();
			$upgrade_ids = array();

			switch($state->filter_discountmode) {
				case 'none':
					$query->where(
						'('.
						'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_coupon_id').' = '.
						$db->q(0).')'
						.' AND '.
						'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_upgrade_id').' = '.
						$db->q(0).')'
						.')'
					);
					break;

				case 'coupon':
					$query->where(
						'('.
						'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_coupon_id').' > '.
						$db->q(0).')'
						.' AND '.
						'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_upgrade_id').' = '.
						$db->q(0).')'
						.')'
					);
					if($state->filter_discountcode) {
						$coupons = FOFModel::getTmpInstance('Coupons','AkeebasubsModel')
							->search($state->filter_discountcode)
							->getList();
						if(!empty($coupons)) foreach($coupons as $coupon) {
							$coupon_ids[] = $coupon->akeebasubs_coupon_id;
						}
						unset($coupons);
					}
					break;

				case 'upgrade':
					$query->where(
						'('.
						'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_coupon_id').' = '.
						$db->q(0).')'
						.' AND '.
						'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_upgrade_id').' > '.
						$db->q(0).')'
						.')'
					);
					if($state->filter_discountcode) {
						$upgrades = FOFModel::getTmpInstance('Upgrades','AkeebasubsModel')
							->search($state->filter_discountcode)
							->getList();
						if(!empty($upgrades)) foreach($upgrades as $upgrade) {
							$upgrade_ids[] = $upgrade->akeebasubs_upgrade_id;
						}
						unset($upgrades);
					}
					break;

				default:
					if($state->filter_discountcode) {
						$coupons = FOFModel::getTmpInstance('Coupons','AkeebasubsModel')
							->search($state->filter_discountcode)
							->getList();
						if(!empty($coupons)) foreach($coupons as $coupon) {
							$coupon_ids[] = $coupon->akeebasubs_coupon_id;
						}
						unset($coupons);
					}
					if($state->filter_discountcode) {
						$upgrades = FOFModel::getTmpInstance('Upgrades','AkeebasubsModel')
							->search($state->filter_discountcode)
							->getList();
						if(!empty($upgrades)) foreach($upgrades as $upgrade) {
							$upgrade_ids[] = $upgrade->akeebasubs_upgrade_id;
						}
						unset($upgrades);
					}
					break;
			}

			if(!empty($coupon_ids) && !empty($upgrade_ids)) {
				$query->where(
					'('.
					'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_coupon_id').' IN ('.
						$db->q(implode(',', $coupon_ids)).'))'
					.' OR '.
					'('.$db->qn('tbl').'.'.$db->qn('akeebasubs_upgrade_id').' IN ('.
						$db->q(implode(',', $upgrade_ids)).'))'
					.')'
				);
			} elseif(!empty($coupon_ids)) {
				$query->where($db->qn('tbl').'.'.$db->qn('akeebasubs_coupon_id').' IN ('.
					$db->q(implode(',', $coupon_ids)).')');
			} elseif(!empty($upgrade_ids)) {
				$query->where($db->qn('tbl').'.'.$db->qn('akeebasubs_upgrade_id').' IN ('.
					$db->q(implode(',', $upgrade_ids)).')');
			}
		}

		// "Since" queries
		$since = trim($state->since);
		if(empty($since) || ($since == '0000-00-00') || ($since == '0000-00-00 00:00:00')) {
			$since = '';
		} else {
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $since)) {
				$since = '2001-01-01';
			}
			$jFrom = new JDate($since);
			$since = $jFrom->toUnix();
			if($since == 0) {
				$since = '';
			} else {
				$since = $jFrom->toSql();
			}
			// Filter from-to dates
			$query->where(
				$db->qn('tbl').'.'.$db->qn('created_on').' >= '.
					$db->q($since)
			);
		}

		// "Until" queries
		$until = trim($state->until);
		if(empty($until) || ($until == '0000-00-00') || ($until == '0000-00-00 00:00:00')) {
			$until = '';
		} else {
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $until)) {
				$until = '2037-01-01';
			}
			$jFrom = new JDate($until);
			$until = $jFrom->toUnix();
			if($until == 0) {
				$until = '';
			} else {
				$until = $jFrom->toSql();
			}
			$query->where(
				$db->qn('tbl').'.'.$db->qn('created_on').' <= '.
					$db->q($until)
			);
		}

		// Expiration control queries
		JLoader::import('joomla.utilities.date');
		$from = trim($state->expires_from);
		if(empty($from)) {
			$from = '';
		} else {
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $from)) {
				$from = '2001-01-01';
			}
			$jFrom = new JDate($from);
			$from = $jFrom->toUnix();
			if($from == 0) {
				$from = '';
			} else {
				$from = $jFrom->toSql();
			}
		}

		$to = trim($state->expires_to);
		if(empty($to) || ($to == '0000-00-00') || ($to == '0000-00-00 00:00:00')) {
			$to = '';
		} else {
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $to)) {
				$to = '2037-01-01';
			}
			$jTo = new JDate($to);
			$to = $jTo->toUnix();
			if($to == 0) {
				$to = '';
			} else {
				$to = $jTo->toSql();
			}
		}

		if(!empty($from) && !empty($to)) {
			// Filter from-to dates
			$query->where(
				$db->qn('tbl').'.'.$db->qn('publish_down').' >= '.
					$db->q($from)
			);
			$query->where(
				$db->qn('tbl').'.'.$db->qn('publish_down').' <= '.
					$db->q($to)
			);
		} elseif(!empty($from) && empty($to)) {
			// Filter after date
			$query->where(
				$db->qn('tbl').'.'.$db->qn('publish_down').' >= '.
					$db->q($from)
			);
		} elseif(empty($from) && !empty($to)) {
			// Filter up to a date
			$query->where(
				$db->qn('tbl').'.'.$db->qn('publish_down').' <= '.
					$db->q($to)
			);
		}

		// No-zero toggle
		if(!empty($state->nozero)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('net_amount').' > '.
					$db->q('0')
			);
		}
	}

	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		$query = $db->getQuery(true)
				->from($db->qn('#__akeebasubs_subscriptions').' AS '.$db->qn('tbl'));

		$this->_buildQueryColumns($query);
		$this->_buildQueryJoins($query);
		$this->_buildQueryWhere($query);
		$this->_buildQueryGroup($query);

		return $query;
	}

	public function onProcessList(&$resultArray) {
		// Implement the subscription automatic expiration
		if(empty($resultArray)) return;

		if($this->getState('skipOnProcessList',0)) return;

		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();

		$table = $this->getTable($this->table);
		$k = $table->getKeyName();

		foreach($resultArray as $index => &$row) {
			if(!property_exists($row, 'params')) continue;

			if(!is_array($row->params)) {
				if(!empty($row->params)) {
					$row->params = json_decode($row->params, true);
				}
			}
			if(is_null($row->params) || empty($row->params)) {
				$row->params = array();
			}

			$triggered = false;

			if(!property_exists($row, 'publish_down')) continue;
			if(!property_exists($row, 'publish_up')) continue;

			if($row->state != 'C') {
				if($row->enabled) {
					$row->enabled = false;
					$table->reset();
					$table->load($row->$k);
					$table->save($row);
				}
				continue;
			}

			if($row->publish_down && ($row->publish_down != '0000-00-00 00:00:00')) {
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
				if(!preg_match($regex, $row->publish_down)) {
					$row->publish_down = '2037-01-01';
				}
				if(!preg_match($regex, $row->publish_up)) {
					$row->publish_up = '2001-01-01';
				}
				$jDown = new JDate($row->publish_down);
				$jUp = new JDate($row->publish_up);
				if( ($uNow >= $jDown->toUnix()) && $row->enabled ) {
					$row->enabled = 0;
					$triggered = true;
				} elseif(($uNow >= $jUp->toUnix()) && !$row->enabled && ($uNow < $jDown->toUnix())) {
					$row->enabled = 1;
					$triggered = true;
				}
			}

			if($triggered) {
				$table->reset();
				$table->load($row->$k);
				$table->save($row);
			}
		}
	}

	public function getActiveSubscribers()
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array('COUNT(DISTINCT('.$db->qn('user_id').'))'))
			->from($db->qn('#__akeebasubs_subscriptions'))
			->where($db->qn('enabled').' = '.$db->q('1'));
		$db->setQuery($query);
		return $db->loadResult();
	}

	protected function onBeforeSave(&$data, &$table)
	{
		if(array_key_exists('params', $data)) {
			if(is_array($data['params'])) {
				$params = json_encode($data['params']);
				$data['params'] = json_encode($data['params']);
			}
		}

		return true;
	}
}