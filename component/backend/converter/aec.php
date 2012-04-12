<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('');

/**
 * AEC (Account Expiration Control) to Akeeba Subscriptions converter
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
		
		$subquery = FOFQueryAbstract::getNew($db)
			->select(array(
				'MAX('.$db->nameQuote('id').') AS '.$db->nameQuote('invid'),
				$db->nameQuote('subscr_id')
			))->from($db->nameQuote('#__acctexp_invoices'))
			->group(array(
				$db->nameQuote('subscr_id')
			));
		$subquery = (string)$subquery;
		
		$tables = array(
			array(
				'name'		=>	'levels',
				'foreign'	=>	'#__acctexp_plans',
				'foreignkey'=>	'akeebasubs_level_id',
				'query'		=> FOFQueryAbstract::getNew($db)
					->select(array(
						$db->nameQuote('id'),
						$db->nameQuote('id').' AS '.$db->nameQuote('akeebasubs_level_id'),
						$db->nameQuote('name').' AS '.$db->nameQuote('title'),
						$db->nameQuote('desc').' AS '.$db->nameQuote('description'),
						$db->nameQuote('params'),
						$db->nameQuote('active').' AS '.$db->nameQuote('enabled'),
						$db->nameQuote('ordering'),
					))
			),
			array(
				'name'		=>	'subscriptions',
				'foreign'	=>	'#__acctexp_subscr',
				'foreignkey'=>	'akeebasubs_subscription_id',
				'query'		=> FOFQueryAbstract::getNew($db)
					->select(array(
						$db->nameQuote('tbl').'.'.$db->nameQuote('id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('id').' AS '.$db->nameQuote('akeebasubs_subscription_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('userid').' AS '.$db->nameQuote('user_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('plan').' AS '.$db->nameQuote('akeebasubs_level_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('signup_date').' AS '.$db->nameQuote('publish_up'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('expiration').' AS '.$db->nameQuote('publish_down'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('type').' AS '.$db->nameQuote('processor'),
						$db->nameQuote('inv').'.'.$db->nameQuote('invoice_number').' AS '.$db->nameQuote('processor_key'),
						$db->nameQuote('inv').'.'.$db->nameQuote('amount').' AS '.$db->nameQuote('net_amount'),
						$db->quote('0').' AS '.$db->nameQuote('tax_amount'),
						$db->nameQuote('inv').'.'.$db->nameQuote('amount').' AS '.$db->nameQuote('gross_amount'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('signup_date').' AS '.$db->nameQuote('created_on'),
						$db->nameQuote('inv').'.'.$db->nameQuote('amount').' AS '.$db->nameQuote('prediscount_amount'),
						$db->quote('0.0').' AS '.$db->nameQuote('discount_amount'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('status').' AS '.$db->nameQuote('aec_status'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('lifetime').' AS '.$db->nameQuote('aec_lifetime'),
					))->join('INNER', "($subquery) AS ".$db->nameQuote('glue').' ON('.
							$db->nameQuote('glue').'.'.$db->nameQuote('subscr_id').'='.$db->nameQuote('tbl').'.'.$db->nameQuote('id')
						.')')
					->join('INNER', $db->nameQuote('#__acctexp_invoices').' AS '.$db->nameQuote('inv').' ON ('.
							$db->nameQuote('inv').'.'.$db->nameQuote('id').' = '.$db->nameQuote('glue').'.'.$db->nameQuote('invid')
						.')')
			)
		);
		
		// Import data
		$this->result = $this->importData($tables);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
		
		// Post-processing
		if(isset($this->data['levels'])) {
			$defaultImagePath = version_compare(JVERSION, '1.6.0', 'ge') ? 'images/' : 'images/stories';
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
			jimport('joomla.utilities.date');
			$jNow = new JDate();
			$jForever = new JDate('2038-01-01 00:00:00');
			$tsNow = $jNow->toUnix();
			if(!empty($this->data['subscriptions'])) foreach($this->data['subscriptions'] as $id => $subscription) {
				if($subscription['lifetime']) {
					$subscription['publish_down'] = $jForever->toMySQL();
					$this->data['subscriptions'][$id]['publish_down'] = $jForever->toMySQL();
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
					$this->data['subscriptions'][$id]['first_contact'] = $jNow->toMySQL();
					$this->data['subscriptions'][$id]['second_contact'] = $jNow->toMySQL();
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
