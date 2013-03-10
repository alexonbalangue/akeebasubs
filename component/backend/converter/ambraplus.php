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
			),
			array(
				'name'		=> 'users',
				'foreign'	=> '#__users',
				'foreignkey'=> 'akeebasubs_user_id',
				'query' => $db->getQuery(true)
					->select(array(
						$db->qn('tbl').'.'.$db->qn('id').' AS '.$db->qn('akeebasubs_user_id'),
						$db->qn('tbl').'.'.$db->qn('params').' AS '.$db->qn('rawparams')
					))
					->join('INNER', $db->qn('ambrasubs_users2types').' AS '.$db->qn('s').' ON('.
							$db->qn('tbl').'.'.$db->qn('id').' = '.
							$db->qn('s').'.'.$db->qn('userid')
							.')')
					->group($db->qn('tbl').'.'.$db->qn('id'))
			),
			array(
				'name'		=> 'coupons',
				'foreign'	=> '#__ambrasubs_coupons',
				'foreignkey'=> 'akeebasubs_coupon_id',
				'query' => $db->getQuery(true)
					->select(array(
						$db->qn('tbl').'.'.$db->qn('id').' AS '.$db->qn('akeebasubs_coupon_id'),
						$db->qn('tbl').'.'.$db->qn('sub_id').' AS '.$db->qn('subscriptions'),
						$db->qn('tbl').'.'.$db->qn('name').' AS '.$db->qn('title'),
						$db->qn('tbl').'.'.$db->qn('type'),
						$db->qn('tbl').'.'.$db->qn('value'),
						$db->qn('tbl').'.'.$db->qn('code').' AS '.$db->qn('coupon'),
						$db->qn('tbl').'.'.$db->qn('publish_up'),
						$db->qn('tbl').'.'.$db->qn('publish_down'),
						$db->qn('tbl').'.'.$db->qn('published').' AS '.$db->qn('enabled'),
						$db->qn('tbl').'.'.$db->qn('hits'),
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
