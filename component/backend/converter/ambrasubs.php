<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
				'query'		=> FOFQueryAbstract::getNew($db)
					->select(array(
						$db->nameQuote('id'),
						$db->nameQuote('id').' AS '.$db->nameQuote('akeebasubs_level_id'),
						$db->nameQuote('title'),
						'LOWER('.$db->nameQuote('title').') AS '.$db->nameQuote('slug'),
						$db->nameQuote('description'),
						$db->nameQuote('img').' AS '.$db->nameQuote('image'),
						$db->nameQuote('period').' AS '.$db->nameQuote('duration'),
						$db->nameQuote('value').' AS '.$db->nameQuote('price'),
						$db->nameQuote('articleid'),
						$db->nameQuote('published').' AS '.$db->nameQuote('enabled'),
						$db->nameQuote('ordering'),
					))
			),
			array(
				'name'		=>	'subscriptions',
				'foreign'	=>	'#__ambrasubs_users2types',
				'foreignkey'=>	'akeebasubs_subscription_id',
				'query'		=> FOFQueryAbstract::getNew($db)
					->select(array(
						$db->nameQuote('tbl').'.'.$db->nameQuote('u2tid').' AS '.$db->nameQuote('akeebasubs_subscription_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('userid').' AS '.$db->nameQuote('user_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('typeid').' AS '.$db->nameQuote('akeebasubs_level_id'),
						$db->nameQuote('p').'.'.$db->nameQuote('created_datetime').' AS '.$db->nameQuote('publish_up'),
						'IF('.$db->nameQuote('tbl').'.'.$db->nameQuote('expires_datetime').' > '.$db->quote('2038-01-01').', '.$db->quote('NADA').', '.$db->nameQuote('tbl').'.'.$db->nameQuote('expires_datetime').') AS '.$db->nameQuote('publish_down'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('notes').' AS '.$db->nameQuote('notes'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('status').' AS '.$db->nameQuote('enabled'),
						'IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_type').' IS NULL, '.$db->quote('none').', '.$db->nameQuote('p').'.'.$db->nameQuote('payment_type').') AS '.$db->nameQuote('processor'),
						'IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_id').' IS NULL, '.$db->quote('Import').', '.$db->nameQuote('p').'.'.$db->nameQuote('payment_id').') AS '.$db->nameQuote('processor_key'),
						'IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_status').' = '.$db->quote('1').', '.$db->quote('C').', IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_status').' IS NULL, '.$db->quote('C').', '.$db->quote('X').')) AS '.$db->nameQuote('state'),
						'IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_amount').'IS NULL, '.$db->quote('0').', '.$db->nameQuote('p').'.'.$db->nameQuote('payment_amount').') AS '.$db->nameQuote('net_amount'),
						$db->quote('0').' AS '.$db->nameQuote('tax_amount'),
						'IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_amount').'IS NULL, '.$db->quote('0').', '.$db->nameQuote('p').'.'.$db->nameQuote('payment_amount').') AS '.$db->nameQuote('gross_amount'),
						'IF('.$db->nameQuote('p').'.'.$db->nameQuote('payment_datetime').'IS NULL, '.$db->quote('0000-00-00 00:00:00').', '.$db->nameQuote('p').'.'.$db->nameQuote('payment_datetime').') AS '.$db->nameQuote('created_on'),
						$db->quote('').' AS '.$db->nameQuote('params'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('flag_contact').' AS '.$db->nameQuote('contact_flag'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('contact_datetime').' AS '.$db->nameQuote('first_contact'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('contact_datetime').' AS '.$db->nameQuote('second_contact'),
					))
					->join('LEFT',
							$db->nameQuote('#__ambrasubs_payments').' AS '.$db->nameQuote('p').' ON('.
							$db->nameQuote('p').'.'.$db->nameQuote('id').' = '.
							$db->nameQuote('tbl').'.'.$db->nameQuote('paymentid').')'
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
					$q = FOFQueryAbstract::getNew($db)
						->select(array('introtext','fulltext'))
						->from($db->nameQuote('#__content'))
						->where(
							$db->nameQuote('id').' = '.$db->quote($articleid)
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
			jimport('joomla.utilities.date');
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
					$this->data['subscriptions'][$id]['first_contact'] = $jNow->toMySQL();
					$this->data['subscriptions'][$id]['second_contact'] = $jNow->toMySQL();
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
