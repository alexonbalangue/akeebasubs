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
class AkeebasubsConverterAmbrasubs extends AkeebasubsConverterAbstract
{
	/**
	 * This converter is able to run in steps
	 *
	 * @var boolean
	 */
	public $splittable = true;
	
	public function __construct($properties = null) {
		parent::__construct($properties);
		$this->convertername = 'ambrasubs';
	}
	
	public function convert()
	{
		$db = JFactory::getDbo();
		
		$tables = array(
			array(
				'name'		=>	'levels',
				'foreign'	=>	'#__ambrasubs_types',
				'foreignkey'=>	'akeebasubs_level_id',
				'query'		=> $db->getQuery(true)
					->select(array(
						$db->qn('id'),
						$db->qn('id').' AS '.$db->qn('akeebasubs_level_id'),
						$db->qn('title'),
						'LOWER('.$db->qn('title').') AS '.$db->qn('slug'),
						$db->qn('description'),
						$db->qn('img').' AS '.$db->qn('image'),
						$db->qn('period').' AS '.$db->qn('duration'),
						$db->qn('value').' AS '.$db->qn('price'),
						$db->qn('articleid'),
						$db->qn('published').' AS '.$db->qn('enabled'),
						$db->qn('ordering'),
					))
			),
			array(
				'name'		=>	'subscriptions',
				'foreign'	=>	'#__ambrasubs_users2types',
				'foreignkey'=>	'akeebasubs_subscription_id',
				'query'		=> $db->getQuery(true)
					->select(array(
						$db->qn('tbl').'.'.$db->qn('u2tid').' AS '.$db->qn('akeebasubs_subscription_id'),
						$db->qn('tbl').'.'.$db->qn('userid').' AS '.$db->qn('user_id'),
						$db->qn('tbl').'.'.$db->qn('typeid').' AS '.$db->qn('akeebasubs_level_id'),
						$db->qn('p').'.'.$db->qn('created_datetime').' AS '.$db->qn('publish_up'),
						'IF('.$db->qn('tbl').'.'.$db->qn('expires_datetime').' > '.$db->q('2038-01-01').', '.$db->q('NADA').', '.$db->qn('tbl').'.'.$db->qn('expires_datetime').') AS '.$db->qn('publish_down'),
						$db->qn('tbl').'.'.$db->qn('notes').' AS '.$db->qn('notes'),
						$db->qn('tbl').'.'.$db->qn('status').' AS '.$db->qn('enabled'),
						'IF('.$db->qn('p').'.'.$db->qn('payment_type').' IS NULL, '.$db->q('none').', '.$db->qn('p').'.'.$db->qn('payment_type').') AS '.$db->qn('processor'),
						'IF('.$db->qn('p').'.'.$db->qn('payment_id').' IS NULL, '.$db->q('Import').', '.$db->qn('p').'.'.$db->qn('payment_id').') AS '.$db->qn('processor_key'),
						'IF('.$db->qn('p').'.'.$db->qn('payment_status').' = '.$db->q('1').', '.$db->q('C').', IF('.$db->qn('p').'.'.$db->qn('payment_status').' IS NULL, '.$db->q('C').', '.$db->q('X').')) AS '.$db->qn('state'),
						'IF('.$db->qn('p').'.'.$db->qn('payment_amount').'IS NULL, '.$db->q('0').', '.$db->qn('p').'.'.$db->qn('payment_amount').') AS '.$db->qn('net_amount'),
						$db->q('0').' AS '.$db->qn('tax_amount'),
						'IF('.$db->qn('p').'.'.$db->qn('payment_amount').'IS NULL, '.$db->q('0').', '.$db->qn('p').'.'.$db->qn('payment_amount').') AS '.$db->qn('gross_amount'),
						'IF('.$db->qn('p').'.'.$db->qn('payment_datetime').'IS NULL, '.$db->q('0000-00-00 00:00:00').', '.$db->qn('p').'.'.$db->qn('payment_datetime').') AS '.$db->qn('created_on'),
						$db->q('').' AS '.$db->qn('params'),
						$db->qn('tbl').'.'.$db->qn('flag_contact').' AS '.$db->qn('contact_flag'),
						$db->qn('tbl').'.'.$db->qn('contact_datetime').' AS '.$db->qn('first_contact'),
						$db->qn('tbl').'.'.$db->qn('contact_datetime').' AS '.$db->qn('second_contact'),
					))
					->join('LEFT',
							$db->qn('#__ambrasubs_payments').' AS '.$db->qn('p').' ON('.
							$db->qn('p').'.'.$db->qn('id').' = '.
							$db->qn('tbl').'.'.$db->qn('paymentid').')'
					)
			)
		);
		
		// Import data
		$this->result = $this->importData($tables);
		
		// Post-processing
		if(isset($this->data['levels'])) {
			$defaultImagePath = 'images/';
			if(!empty($this->data['levels'])) foreach($this->data['levels'] as $id => $level) {
				$articleText = '<p></p>';
				$articleid = (int)($level['articleid']);
				if($articleid > 0) {
					$q = $db->getQuery(true)
						->select(array('introtext','fulltext'))
						->from($db->qn('#__content'))
						->where(
							$db->qn('id').' = '.$db->q($articleid)
						)
						;
					$db->setQuery($q);
					$article = $db->loadObject();
					if(is_object($article)) {
						$articleText = $article->introtext . "\n" . $article->fulltext;
					}
				}
				$this->data['levels'][$id]['ordertext'] = $articleText;
				$this->data['levels'][$id]['canceltext'] = $articleText;
				
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
			$tsNow = $jNow->toUnix();
			if(!empty($this->data['subscriptions'])) foreach($this->data['subscriptions'] as $id => $subscription) {
				// Fixes subscriptions without an attached payment
				if(empty($subscription['publish_up'])) {
					$this->data['subscriptions'][$id]['publish_up'] = '2010-01-01 00:00:00';
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
				}
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
		
		if(!in_array($prefix.'ambrasubs_types',$tables)) return false;
		if(!in_array($prefix.'ambrasubs_users2types',$tables)) return false;
		
		return true;
	}
}
