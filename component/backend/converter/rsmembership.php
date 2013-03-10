<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('');

/**
 * AMBRA.Subscriptions to Akeeba Subscriptions converter
 * @author Nicholas K. Dionysopoulos
 */
class AkeebasubsConverterRsmembership extends AkeebasubsConverterAbstract
{
	/**
	 * This converter is able to run in steps
	 *
	 * @var boolean
	 */
	public $splittable = true;

	public function __construct($properties = null) {
		parent::__construct($properties);
		$this->convertername = 'rsmembership';
	}
	
	public function convert()
	{
		$db = JFactory::getDbo();
		
		$tables = array(
			array(
				'name'		=>	'levels',
				'foreign'	=>	'#__rsmembership_memberships',
				'foreignkey'=>	'akeebasubs_level_id',
				'query'		=> $db->getQuery(true)
					->select(array(
						$db->qn('id'),
						$db->qn('id').' AS '.$db->qn('akeebasubs_level_id'),
						$db->qn('name').' AS '.$db->qn('title'),
						$db->qn('sku').' AS '.$db->qn('slug'),
						$db->qn('thumb').' AS '.$db->qn('image'),
						$db->qn('description'),
						$db->qn('period'),
						$db->qn('period_type'),
						$db->qn('price'),
						$db->qn('thankyou').' AS '.$db->qn('ordertext'),
						$db->qn('no_renew').' AS '.$db->qn('only_once'),
						$db->qn('recurring'),
						$db->qn('published').' AS '.$db->qn('enabled'),
						$db->qn('ordering'),
					))
			),
			array(
				'name'		=>	'subscriptions',
				'foreign'	=>	'#__rsmembership_membership_users',
				'foreignkey'=>	'akeebasubs_subscription_id',
				'query'		=> $db->getQuery(true)
					->select(array(
						$db->qn('tbl').'.'.$db->qn('id'),
						$db->qn('tbl').'.'.$db->qn('id').' AS '.$db->qn('akeebasubs_subscription_id'),
						$db->qn('tbl').'.'.$db->qn('user_id'),
						$db->qn('tbl').'.'.$db->qn('membership_id').' AS '.$db->qn('akeebasubs_level_id'),
						$db->qn('tbl').'.'.$db->qn('membership_start').' AS '.$db->qn('publish_up'),
						$db->qn('tbl').'.'.$db->qn('membership_end').' AS '.$db->qn('publish_down'),
						$db->qn('tbl').'.'.$db->qn('published').' AS '.$db->qn('enabled'),
						$db->qn('t').'.'.$db->qn('gateway').' AS '.$db->qn('processor'),
						$db->qn('t').'.'.$db->qn('hash').' AS '.$db->qn('processor_key'),
						$db->qn('t').'.'.$db->qn('status').' AS '.$db->qn('state'),
						$db->qn('tbl').'.'.$db->qn('price').' AS '.$db->qn('net_amount'),
						$db->q('0').' AS '.$db->qn('tax_amount'),
						$db->qn('tbl').'.'.$db->qn('price').' AS '.$db->qn('gross_amount'),
						$db->q('0').' AS '.$db->qn('tax_percent'),
						$db->qn('t').'.'.$db->qn('date').' AS '.$db->qn('created_on'),
						$db->qn('tbl').'.'.$db->qn('price').' AS '.$db->qn('prediscount_amount'),
						$db->q('0').' AS '.$db->qn('discount_amount'),
						$db->qn('tbl').'.'.$db->qn('notified').' AS '.$db->qn('contact_flag'),
					))
					->join('LEFT OUTER',
							$db->qn('#__rsmembership_transactions').' AS '.$db->qn('t')
							.' ON('.
								$db->qn('t').'.'.$db->qn('id').' = '.$db->qn('tbl').'.'.$db->qn('last_transaction_id')
							.')')
			),
			array(
				'name'		=> 'coupons',
				'foreign'	=> '#__rsmembership_coupons',
				'foreignkey'=> 'akeebasubs_coupon_id',
				'query' => $db->getQuery(true)
					->select(array(
						$db->qn('id'),
						$db->qn('id').' AS '.$db->qn('akeebasubs_coupon_id'),
						$db->qn('id').' AS '.$db->qn('ordering'),
						$db->qn('name').' AS '.$db->qn('title'),
						$db->qn('name').' AS '.$db->qn('coupon'),
						$db->qn('date_start').' AS '.$db->qn('publish_up'),
						$db->qn('date_end').' AS '.$db->qn('publish_down'),
						$db->qn('max_uses').' AS '.$db->qn('hitslimit'),
						$db->qn('discount_type').' AS '.$db->qn('type'),
						$db->qn('discount_price').' AS '.$db->qn('value'),
						$db->qn('published').' AS '.$db->qn('enabled'),
						$db->qn('date_added').' AS '.$db->qn('created_on'),
					))
			)
		);
		
		// Import data
		$this->result = $this->importData($tables);
		
		// Post-processing
		if(isset($this->data['levels'])) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
			$defaultImagePath = 'images/';
			if(!empty($this->data['levels'])) foreach($this->data['levels'] as $id => $level) {
				$img = $level['image'];
				$img = ltrim($img,'/');
				if(substr($img,0,strlen($defaultImagePath)) == $defaultImagePath) {
					$this->data['levels'][$id]['image'] = substr($img,strlen($defaultImagePath));
				}
				
				if(empty($level['slug'])) {
					$this->data['levels'][$id]['slug'] = $level['id'].'-'.AkeebasubsHelperFilter::toSlug($level['title']);
				} else {
					$this->data['levels'][$id]['slug'] = $level['id'].'-'.AkeebasubsHelperFilter::toSlug($level['slug']);
				}
				
				switch(strtolower($level['period_type'])) {
					case 'd':
					default:
						$multiplier = 1;
						break;
					
					case 'w':
						$multiplier = 7;
						break;
					
					case 'm':
						$multiplier = 30;
						break;
					
					case 'y':
						$multiplier = 365;
						break;
				}
				
				$this->data['levels'][$id]['duration'] = $multiplier * (int)$level['period'];
			}
		}
		
		if(isset($this->data['subscriptions'])) {
			JLoader::import('joomla.utilities.date');
			if(!empty($this->data['subscriptions'])) foreach($this->data['subscriptions'] as $id => $level) {
				$forceCompletedPayment = false;
				
				// Convert publish_up
				$time = (int)$level['publish_up'];
				if(empty($time)) {
					$jDate = new JDate('2037-04-02 00:00:00');
				} else {
					$jDate = new JDate($time);
				}
				$this->data['subscriptions'][$id]['publish_up'] = $jDate->toSql();
				
				// Convert publish_down
				$time = (int)$level['publish_down'];
				if(empty($time)) {
					$forceCompletedPayment = true;
					$jDate = new JDate('2037-04-02 00:00:00');
				} else {
					$jDate = new JDate($time);
				}
				$this->data['subscriptions'][$id]['publish_down'] = $jDate->toSql();
				
				// Convert created_on
				$time = (int)$level['created_on'];
				if(empty($time)) {
					$forceCompletedPayment = true;
					$jDate = new JDate('2037-04-02 00:00:00');
				} else {
					$jDate = new JDate($time);
				}
				$this->data['subscriptions'][$id]['created_on'] = $jDate->toSql();
				
				// Fix empty processor
				if(empty($level['processor'])) {
					$forceCompletedPayment = true;
					$this->data['subscriptions'][$id]['processor'] = 'none';
				}
				
				// Fix empty processor key
				if(empty($level['processor_key'])) {
					$this->data['subscriptions'][$id]['processor_key'] = md5(microtime().implode('.',$level));
				}
				
				switch($level['state']) {
					case 'new':
					default:
						$level['state'] = $forceCompletedPayment ? 'C' : 'N';
						break;
					
					case 'completed':
						$level['state'] = 'C';
						break;
					
					case 'pending':
						$level['state'] = 'P';
						break;
				}
				$this->data['subscriptions'][$id]['status'] = $level['status'];
				
				if(!empty($level['contact_flag'])) {
					$this->data['subscriptions'][$id]['contact_flag'] = 3;
				}
			}
		}
		
		if(isset($this->data['coupons'])) {
			JLoader::import('joomla.utilities.date');
			if(!empty($this->data['subscriptions'])) foreach($this->data['subscriptions'] as $id => $level) {
				// Convert publish_up
				$time = (int)$level['publish_up'];
				if(empty($time)) {
					$jDate = new JDate('2037-04-02 00:00:00');
				} else {
					$jDate = new JDate($time);
				}
				$this->data['subscriptions'][$id]['publish_up'] = $jDate->toSql();
				
				// Convert publish_down
				$time = (int)$level['publish_down'];
				if(empty($time)) {
					$jDate = new JDate('2037-04-02 00:00:00');
				} else {
					$jDate = new JDate($time);
				}
				$this->data['subscriptions'][$id]['publish_up'] = $jDate->toSql();
				
				// Convert created_on
				$time = (int)$level['created_on'];
				if(empty($time)) {
					$jDate = new JDate('2037-04-02 00:00:00');
				} else {
					$jDate = new JDate($time);
				}
				$this->data['subscriptions'][$id]['created_on'] = $jDate->toSql();
				
				// Convert type
				switch($level['type']) {
					case 1:
						$type = 'value';
						break;

					default:
						$type = 'percent';
						break;
				}
				$this->data['subscriptions'][$id]['type'] = $type;
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
		
		if(!in_array($prefix.'rsmembership_membership_users',$tables)) return false;
		return true;
	}
	
	
}