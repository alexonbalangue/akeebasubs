<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * AMBRA.Subscriptions to Akeeba Subscriptions converter
 * 
 * Losely based on NinjaBoard's Kunena importer. Thank you, Stian, for your awesome code!
 *
 * @author Nicholas K. Dionysopoulos
 */
class ComAkeebasubsDatabaseConvertersAmbraplus extends ComAkeebasubsDatabaseConvertersAbstract
{
	/**
	 * This converter is able to run in steps
	 *
	 * @var boolean
	 */
	public $splittable = true;

	public function convert()
	{
		$tables = array(
			array(
				'name'		=>	'levels',
				'options'	=>	array(
					'name' => 'ambrasubs_types',
					'identity_column' => 'id'
				),
				'query'	=> KFactory::tmp('lib.koowa.database.query')
					->select(array(
						'id',
						'title',
						'LOWER(title) AS slug',
						'description',
						'img AS image',
						'period AS duration',
						'value AS price',
						'articleid',
						'published AS enabled',
						'ordering',
					))
			),
			array(
				'name'		=> 'subscriptions',
				'options'	=> array(
					'name' => 'ambrasubs_users2types',
					'identity_column' => 'u2tid'
				),
				'query'	=> KFactory::tmp('lib.koowa.database.query')
					->select(array(
						'tbl.u2tid',
						'tbl.userid AS user_id',
						'tbl.typeid AS akeebasubs_level_id',
						'p.created_datetime AS publish_up',
						'IF(tbl.expires_datetime > \'2038-01-01\', \'NADA\', tbl.expires_datetime) AS publish_down',
						'tbl.notes AS notes',
						'tbl.status AS enabled',
						'IF(p.payment_type IS NULL, \'None\', p.payment_type) AS processor',
						'IF(p.payment_id IS NULL, \'Import\', p.payment_id) AS processor_key',
						'IF(p.payment_status = 1, \'C\', IF(p.payment_status IS NULL, \'C\', \'X\')) AS state',
						'IF(p.payment_amount IS NULL, 0, p.payment_amount) AS net_amount',
						'0 AS tax_amount',
						'IF(p.payment_amount IS NULL, 0, p.payment_amount) AS gross_amount',
						'IF(p.payment_datetime IS NULL, \'0000-00-00 00:00:00\', p.payment_datetime) AS created_on',
						'\'\' AS params',
						'tbl.flag_contact AS contact_flag',
						'tbl.contact_datetime AS first_contact',
						'tbl.contact_datetime AS second_contact'
					))
					->join('left', 'ambrasubs_payments AS p', 'p.id = tbl.paymentid')
			),
			array(
				'name'	=> 'users',
				'options' => array(
					'name' => 'users',
					'identity_column' => 'id'
				),
				'query' => KFactory::tmp('lib.koowa.database.query')
					->select(array(
						'tbl.id',
						'tbl.params AS rawparams'
					))
					->join('inner', 'ambrasubs_users2types AS s','tbl.id = s.userid')
					->group('tbl.id')
			),
			array(
				'name'	=> 'coupons',
				'options' => array(
					'name'	=> 'ambrasubs_coupons',
					'identity_column' => 'id'
				),
				'query' => KFactory::tmp('lib.koowa.database.query')
					->select(array(
						'tbl.*'
					))
			)
		);
		
		//This returns false if the import is big enough to be done in steps.
		//So we need to stop the importing in this step, in order for it to initiate
		if($this->importData($tables) === false) return $this;
		
		jimport('joomla.utilities.date');
		
		// Post-proc the subscription levels, merging the Joomla! articles to the ordertext and
		// canceltext fields. Also take care of the image field.
		if(isset($this->data['levels'])) {
			$defaultImagePath = version_compare(JVERSION, '1.6.0', 'ge') ? 'images/' : 'images/stories';
			foreach($this->data['levels'] as $id => $level) {
				$articleText = '<p></p>';
				$articleid = (int)($level['articleid']);
				if($articleid > 0) {
					$article = KFactory::tmp('admin::com.default.database.table.content', array(
						'name'=>'content','identity_column'=>'id'
					))->select(KFactory::tmp('lib.koowa.database.query')
						->select(array('introtext','fulltext'))
						->where('id','=',$articleid), KDatabase::FETCH_ROW)
					->getItem();
					if($article instanceof KDatabaseRowInterface) {
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
			$jNow = new JDate();
			$tsNow = $jNow->toUnix();
			foreach($this->data['subscriptions'] as $id => $subscription) {
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
			$allUsers = $this->data['users'];
			$this->data['users'] = array();
			
			foreach($allUsers as $id => $rawuser) {
				if(empty($rawuser['rawparams'])) continue;
				$user = $this->parse_ini_file_php($rawuser['rawparams']);
				$data = array();
				if(empty($user)) {
					// Default dummy data
					$data['isbusiness'] = 0;
					$data['viesregistered'] = 0;
					$data['country'] = 'XX';
				} else {
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
		
		// Convert coupons
		if(isset($this->data['coupons'])) {
			$jNow = new JDate();
			foreach($this->data['coupons'] as $id => $coupon) {
				$jUp = new JDate($coupon['publish_up']);
				$jDown = new JDate($coupon['publish_down']);
				$this->data['coupons'][$id] = array(
					'id'			=> $id,
					'title'			=> $coupon['name'],
					'coupon'		=> $coupon['code'],
					'publish_up'	=> $jUp->toMySQL(),
					'publish_down'	=> $jDown->toMySQL(),
					'subscriptions'	=> str_replace(';',',',$coupon['sub_id']),
					'type'			=> $coupon['type'],
					'value'			=> $coupon['value'],
					'enabled'		=> $coupon['published'],
					'hits'			=> $coupon['hits'],
					'user'			=> null,
					'params'		=> '',
					'hitslimit'		=> null,
					'ordering'		=> 0,
					'created_on'	=> $jNow->toMySQL(),
					'created_by'	=> KFactory::get('lib.joomla.user')->id,
					'modified_on'	=> '0000-00-00 00:00:00',
					'modified_by'	=> 0,
					'locked_on'		=> '0000-00-00 00:00:00',
					'locked_by'		=> 0
				);
			}
		}
		
		parent::convert();
		
		return $this;
	}
	
	public function canConvert()
	{
		if(!JComponentHelper::getComponent( 'com_ambrasubs', true )->enabled) return false;

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