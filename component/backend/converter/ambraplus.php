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
class AkeebasubsConverterAmbraplus extends AkeebasubsConverterAbstract
{
	/**
	 * This converter is able to run in steps
	 *
	 * @var boolean
	 */
	public $splittable = true;
	
	public function __construct($properties = null) {
		parent::__construct($properties);
		$this->convertername = 'ambraplus';
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
			),
			array(
				'name'		=> 'users',
				'foreign'	=> '#__users',
				'foreignkey'=> 'akeebasubs_user_id',
				'query' => FOFQueryAbstract::getNew($db)
					->select(array(
						$db->nameQuote('tbl').'.'.$db->nameQuote('id').' AS '.$db->nameQuote('akeebasubs_user_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('params').' AS '.$db->nameQuote('rawparams')
					))
					->join('INNER', $db->nameQuote('ambrasubs_users2types').' AS '.$db->nameQuote('s').' ON('.
							$db->nameQuote('tbl').'.'.$db->nameQuote('id').' = '.
							$db->nameQuote('s').'.'.$db->nameQuote('userid')
							.')')
					->group($db->nameQuote('tbl').'.'.$db->nameQuote('id'))
			),
			array(
				'name'		=> 'coupons',
				'foreign'	=> '#__ambrasubs_coupons',
				'foreignkey'=> 'akeebasubs_coupon_id',
				'query' => FOFQueryAbstract::getNew($db)
					->select(array(
						$db->nameQuote('tbl').'.'.$db->nameQuote('id').' AS '.$db->nameQuote('akeebasubs_coupon_id'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('sub_id').' AS '.$db->nameQuote('subscriptions'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('name').' AS '.$db->nameQuote('title'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('type'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('value'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('code').' AS '.$db->nameQuote('coupon'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('publish_up'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('publish_down'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('published').' AS '.$db->nameQuote('enabled'),
						$db->nameQuote('tbl').'.'.$db->nameQuote('hits'),
					))
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
		
		// Convert user parameters
		if(isset($this->data['users'])) {
			die('psofa');
			$allUsers = $this->data['users'];
			$this->data['users'] = array();
			
			if(!empty($allUsers)) foreach($allUsers as $id => $rawuser) {
				if(empty($rawuser['rawparams'])) continue;
				$user = $this->parse_ini_file_php($rawuser['rawparams']);
				$data = array('isbusiness' => 0, 'businessname' => '', 'occupation' => '',
					'vatnumber' => '', 'viesregistered' => 0, 'taxauthority' => '',
					'address1' => '', 'address2' => '', 'city' => '', 'state' => '',
					'zip' => '', 'country' => 'XX', 'params' => '', 'notes' => '');
				if(!empty($user)) {
					if(array_key_exists('business_name', $user)) {
						$data['businessname'] = $user['business_name'];
						$data['isbusiness'] = 1;
						if(array_key_exists('occupation', $user)) $data['occupation'] = $user['occupation'];
						if(array_key_exists('vat_number', $user)) {
							$data['vatnumber'] = $user['vat_number'];
							$data['viesregistered'] = 1;
						}
					}
					if(array_key_exists('address', $user)) $data['address1'] = $user['address'];
					if(array_key_exists('address2', $user)) $data['address2'] = $user['address2'];
					if(array_key_exists('city', $user)) $data['city'] = $user['city'];
					if(array_key_exists('state', $user)) $data['state'] = $user['state'];
					if(array_key_exists('zip', $user)) $data['zip'] = $user['zip'];
					if(array_key_exists('country', $user)) $data['country'] = $user['country'];
				}
				
				$data['akeebasubs_user_id'] = $id;
				$data['user_id'] = $id;
				$data['params'] = '';
				$data['notes'] = 'Imported from AMBRA.Subscriptions';
				$this->data['users'][$id] = $data;
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
	
	private function parse_ini_file_php($rawdata, $process_sections = false)
	{
		$process_sections = ($process_sections !== true) ? false : true;

		$data = str_replace("\r","",$rawdata);
		$ini = explode("\n", $data);

		if (count($ini) == 0) {return array();}

		$sections = array();
		$values = array();
		$result = array();
		$globals = array();
		$i = 0;
		foreach ($ini as $line) {
			$line = trim($line);
			$line = str_replace("\t", " ", $line);

			// Comments
			if (!preg_match('/^[a-zA-Z0-9[]/', $line)) {continue;}

			// Sections
			if ($line{0} == '[') {
				$tmp = explode(']', $line);
				$sections[] = trim(substr($tmp[0], 1));
				$i++;
				continue;
			}

			// Key-value pair
			list($key, $value) = explode('=', $line, 2);
			$key = trim($key);
			$value = trim($value);
			if (strstr($value, ";")) {
				$tmp = explode(';', $value);
				if (count($tmp) == 2) {
					if ((($value{0} != '"') && ($value{0} != "'")) ||
					preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
					preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value) ){
						$value = $tmp[0];
					}
				} else {
					if ($value{0} == '"') {
						$value = preg_replace('/^"(.*)".*/', '$1', $value);
					} elseif ($value{0} == "'") {
						$value = preg_replace("/^'(.*)'.*/", '$1', $value);
					} else {
						$value = $tmp[0];
					}
				}
			}
			$value = trim($value);
			$value = trim($value, "'\"");

			if ($i == 0) {
				if (substr($line, -1, 2) == '[]') {
					$globals[$key][] = $value;
				} else {
					$globals[$key] = $value;
				}
			} else {
				if (substr($line, -1, 2) == '[]') {
					$values[$i-1][$key][] = $value;
				} else {
					$values[$i-1][$key] = $value;
				}
			}
		}

		for($j = 0; $j < $i; $j++) {
			if ($process_sections === true) {
				if( isset($sections[$j]) && isset($values[$j]) )	$result[$sections[$j]] = $values[$j];
			} else {
				if( isset($values[$j]) ) $result[] = $values[$j];
			}
		}

		return $result + $globals;
	}
}
