<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('');

/**
 * AEC (Account Expiration Control) to Akeeba Subscriptions converter
 * 
 * Since AEC's database structure is such a convoluted mess, this converter
 * doesn't really work. Oh, well, I'm not going to waste any more time on it.
 * 
 * @author Nicholas K. Dionysopoulos
 */
class AkeebasubsConverterAec extends AkeebasubsConverterAbstract
{
	/**
	 * This converter is able to run in steps
	 *
	 * @var boolean
	 */
	public $splittable = true;
	
	public function __construct($properties = null) {
		parent::__construct($properties);
		$this->convertername = 'aec';
	}
	
	public function convert()
	{
		$db = JFactory::getDbo();
		
		$subquery = $db->getQuery(true)
			->select(array(
				'MAX('.$db->qn('id').') AS '.$db->qn('invid'),
				$db->qn('subscr_id')
			))->from($db->qn('#__acctexp_invoices'))
			->group(array(
				$db->qn('subscr_id')
			));
		$subquery = (string)$subquery;
		
		$tables = array(
			array(
				'name'		=>	'levels',
				'foreign'	=>	'#__acctexp_plans',
				'foreignkey'=>	'akeebasubs_level_id',
				'query'		=> $db->getQuery(true)
					->select(array(
						$db->qn('id'),
						$db->qn('id').' AS '.$db->qn('akeebasubs_level_id'),
						$db->qn('name').' AS '.$db->qn('title'),
						$db->qn('desc').' AS '.$db->qn('description'),
						$db->qn('params'),
						$db->qn('active').' AS '.$db->qn('enabled'),
						$db->qn('ordering'),
					))
			),
			array(
				'name'		=>	'subscriptions',
				'foreign'	=>	'#__acctexp_subscr',
				'foreignkey'=>	'akeebasubs_subscription_id',
				'query'		=> $db->getQuery(true)
					->select(array(
						$db->qn('tbl').'.'.$db->qn('id'),
						$db->qn('tbl').'.'.$db->qn('id').' AS '.$db->qn('akeebasubs_subscription_id'),
						$db->qn('tbl').'.'.$db->qn('userid').' AS '.$db->qn('user_id'),
						$db->qn('tbl').'.'.$db->qn('plan').' AS '.$db->qn('akeebasubs_level_id'),
						$db->qn('tbl').'.'.$db->qn('signup_date').' AS '.$db->qn('publish_up'),
						$db->qn('tbl').'.'.$db->qn('expiration').' AS '.$db->qn('publish_down'),
						$db->qn('tbl').'.'.$db->qn('type').' AS '.$db->qn('processor'),
						$db->qn('inv').'.'.$db->qn('invoice_number').' AS '.$db->qn('processor_key'),
						$db->qn('inv').'.'.$db->qn('amount').' AS '.$db->qn('net_amount'),
						$db->q('0').' AS '.$db->qn('tax_amount'),
						$db->qn('inv').'.'.$db->qn('amount').' AS '.$db->qn('gross_amount'),
						$db->qn('tbl').'.'.$db->qn('signup_date').' AS '.$db->qn('created_on'),
						$db->qn('inv').'.'.$db->qn('amount').' AS '.$db->qn('prediscount_amount'),
						$db->q('0.0').' AS '.$db->qn('discount_amount'),
						$db->qn('tbl').'.'.$db->qn('status').' AS '.$db->qn('aec_status'),
						$db->qn('tbl').'.'.$db->qn('lifetime').' AS '.$db->qn('aec_lifetime'),
					))->join('INNER', "($subquery) AS ".$db->qn('glue').' ON('.
							$db->qn('glue').'.'.$db->qn('subscr_id').'='.$db->qn('tbl').'.'.$db->qn('id')
						.')')
					->join('INNER', $db->qn('#__acctexp_invoices').' AS '.$db->qn('inv').' ON ('.
							$db->qn('inv').'.'.$db->qn('id').' = '.$db->qn('glue').'.'.$db->qn('invid')
						.')')
			)
		);
		
		// Import data
		$this->result = $this->importData($tables);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
		
		// Post-processing
		if(isset($this->data['levels'])) {
			$defaultImagePath = 'images/';
			if(!empty($this->data['levels'])) foreach($this->data['levels'] as $id => $level) {
				$title = $level['title'];
				$slug = $id.'-'.AkeebasubsHelperFilter::toSlug($title);
				$description = $level['description'];
				$ordering = $level['ordering']; // Get from data
				$params = unserialize(base64_decode($data['params']));
				
				$full_price = $params['full_free'] ? 0 : $params['full_amount'] * 1.0;
				$trial_price = $params['trial_free'] ? 0 : $params['trial_amount'] * 1.0;
				$recurring = 0;

				if(($params['full_period'] < 1) && ($params['trial_period'] > 0)) {
					$period = $params['trial_period'];
					$periodunit = $params['trial_periodunit'];
					$only_once = 1;
					$price = $trial_price;
				} else {
					$period = $params['full_period'];
					$periodunit = $params['full_periodunit'];
					$only_once = 0;
					$price = $full_price;

					if($params['trial_period'] > 0) {
						$recurring = 1;
					}
				}

				switch($periodunit) {
					case 'Y':
						$modifier = 365;
						break;

					case 'M':
						$modifier = 30;
						break;

					case 'W':
						$modifier = 7;
						break;

					default:
						$modifier = 1;
						break;
				}

				$days = $period * $modifier;
				if($days < 3) {
					$notify1 = 0;
					$notify2 = 0;
				} elseif($days <= 14) {
					$notify1 = 0;
					$notify2 = 3;
				} elseif($days <= 30) {
					$notify1 = 0;
					$notify2 = 7;
				} elseif($days <= 90) {
					$notify1 = 14;
					$notify2 = 7;
				} else {
					$notify1 = 30;
					$notify2 = 15;
				} 

				$newLevel = array(
					'title'			=> $title,
					'slug'			=> $slug,
					'image'			=> '',
					'description'	=> $description,
					'duration'		=> $period * $modifier,
					'price'			=> $price,
					'ordertext'		=> $params['customtext_thanks'],
					'canceltext'	=> '',
					'only_once'		=> $only_once,
					'recurring'		=> $recurring,
					'enabled'		=> 1,
					'ordering'		=> $ordering,
					'created_on'	=> '',
					'created_by'	=> '',
					'modified_on'	=> '0000-00-00 00:00:00',
					'modified_by'	=> '0',
					'locked_on'		=> '0000-00-00 00:00:00',
					'locked_by'		=> '0',
					'notify1'		=> '0',
					'notify2'		=> '0',
				);

				
				$this->data['levels'][$id] = $newLevel;
				
				$img = $level['img'];
				$img = ltrim($img,'/');
				if(substr($img,0,strlen($defaultImagePath)) == $defaultImagePath) {
					$this->data['levels'][$id]['img'] = substr($img,strlen($defaultImagePath));
				}
			}
		}
		
		if(isset($this->data['subscriptions'])) {
			JLoader::import('joomla.utilities.date');
			$jNow = new JDate();
			$jForever = new JDate('2038-01-01 00:00:00');
			$tsNow = $jNow->toUnix();
			if(!empty($this->data['subscriptions'])) foreach($this->data['subscriptions'] as $id => $subscription) {
				if($subscription['lifetime']) {
					$subscription['publish_down'] = $jForever->toSql();
					$this->data['subscriptions'][$id]['publish_down'] = $jForever->toSql();
				}
				
				if($subscription['aec_status'] == 'Active') {
					$this->data['subscriptions'][$id]['enabled'] = 1;
					$this->data['subscriptions'][$id]['state'] = 'C';
				} else {
					$this->data['subscriptions'][$id]['enabled'] = 0;
					$this->data['subscriptions'][$id]['state'] = 'X';
				}
				// Fixes subscriptions w/out an expiration date, or with an expiration date set after UNIX' End Of Time.
				$year = empty($subscription['publish_down']) ? 1970 : substr($subscription['publish_down'],0,4);
				if(($year < 2000) || ($year>2038)) {
					$this->data['subscriptions'][$id]['publish_down'] = '2038-01-01 00:00:00';
				}
				// If the subscription is expired, mark it as if the user is already contacted
				$jExp = new JDate($this->data['subscriptions'][$id]['publish_down']);
				if($jExp->toUnix() < $tsNow) {
					$this->data['subscriptions'][$id]['contact_flag'] = 2;
					$this->data['subscriptions'][$id]['first_contact'] = $jNow->toSql();
					$this->data['subscriptions'][$id]['second_contact'] = $jNow->toSql();
				} else {
					$this->data['subscriptions'][$id]['contact_flag'] = 0;
					$this->data['subscriptions'][$id]['first_contact'] = '0000-00-00 00:00:00';
					$this->data['subscriptions'][$id]['second_contact'] = '0000-00-00 00:00:00';
				}
				
				unset($this->data['subscriptions'][$id]['aec_status']);
				unset($this->data['subscriptions'][$id]['aec_lifetime']);
			}
		}
		
		parent::convert();
		
		return $this;
	}
	
	public function canConvert()
	{
		// Can I find the tables I need?
		$db = JFactory::getDbo();
		$tables = $db->getTableList();
		
		$prefix = $db->getPrefix();
		
		if(!in_array($prefix.'acctexp_plans',$tables)) return false;
		if(!in_array($prefix.'acctexp_subscr',$tables)) return false;
		if(!in_array($prefix.'acctexp_invoices',$tables)) return false;
		
		return true;
	}
}
