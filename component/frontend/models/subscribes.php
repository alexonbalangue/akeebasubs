<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsModelSubscribes extends KModelAbstract
{
	/**
	 * List of European states
	 *
	 * @var array
	 */
	private $european_states = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
	
	/**
	 * Raw HTML source of the payment form, as returned by the payment plugin
	 *
	 * @var string
	 */
	private $paymentForm = '';
	
	/**
	 * File handle
	 *
	 * @var resource
	 */
	protected $_urand;
	
	/**
	 * We cache the results of all time-consuming operations, e.g. vat validation, subscription membership calculation,
	 * tax calculations, etc into this array, saved in the user's session.
	 * @var array
	 */
	private $_cache = array();
	
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		// Since we have no table per se, we insert state variables to let
		// Koowa handle the automatic filtering for us
		$this->_state
			->insert('slug'				, 'string', '', true)
			->insert('id'				, 'int')
			->insert('paymentmethod'	, 'cmd')
			->insert('processorkey'		, 'raw')
			->insert('username'			, 'string')
			->insert('password'			, 'raw')
			->insert('password2'		, 'raw')
			->insert('name'				, 'string')
			->insert('email'			, 'email')
			->insert('address1'			, 'string')
			->insert('address2'			, 'string')
			->insert('country'			, 'cmd')
			->insert('state'			, 'cmd')
			->insert('city'				, 'string')
			->insert('zip'				, 'string')
			->insert('isbusiness'		, 'int')
			->insert('businessname'		, 'string')
			->insert('occupation'		, 'string')
			->insert('vatnumber'		, 'cmd')
			->insert('coupon'			, 'string')
			
			->insert('opt'				, 'cmd')
			;
			
		// Load the cache from the session
		$encodedCacheData = KRequest::get('session.akeebasubs.subscribe.validation.cache.data','raw');
		if(!is_null($encodedCacheData)) {
			$this->_cache = json_decode($encodedCacheData, true);
		}
		
		// Load the state from cache, GET or POST variables
		if(!array_key_exists('state',$this->_cache)) {
			$this->_cache['state'] = array(
				'paymentmethod'	=> '',
				'username'		=> '',
				'password'		=> '',
				'password2'		=> '',
				'name'			=> '',
				'email'			=> '',
				'address1'		=> '',
				'address2'		=> '',
				'country'		=> 'XX',
				'state'			=> '',
				'city'			=> '',
				'zip'			=> '',
				'isbusiness'	=> '',
				'businessname'	=> '',
				'occupation'	=> '',
				'vatnumber'		=> '',
				'coupon'		=> ''
			);
		}
		$rawDataCache = $this->_cache['state'];
		$rawDataPost = JRequest::get('POST', 2);
		$rawDataGet = JRequest::get('GET', 2);
		$rawData = array_merge($rawDataCache, $rawDataGet, $rawDataPost);
		$this->_state->setData($rawData);
		
		// Save the new state data in the cache
		$this->_cache['state'] = $this->_state->getData();
		$encodedCacheData = json_encode($this->_cache);
		KRequest::set('session.akeebasubs.subscribe.validation.cache.data',$encodedCacheData);		
	}
	
	/**
	 * Performs a validation
	 */
	public function getValidation()
	{
		$response = new stdClass();
		
		if($this->_state->slug) {
			$this->_state->id = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->slug($this->_state->slug)
				->getItem()
				->id;
		}
		
		switch($this->_state->opt)
		{
			case 'username':
				$response->validation = $this->_validateUsername();
				break;
				
			default:
				$response->validation = $this->_validateState();
				$response->validation->username = $this->_validateUsername()->username;
				$response->price = $this->_validatePrice();
				break;
		}
		return $response;
	}
	
	/**
	 * Validates the username for uniqueness
	 */
	private function _validateUsername()
	{
		$ret = (object)array('username' => false);
		$username = $this->_state->username;
		if(empty($username)) return $ret;
		$user = JFactory::getUser($username);
		$ret->username = !is_object($user);
		return $ret;
	}
	
	/**
	 * Validates the state data for completeness
	 */
	private function _validateState()
	{
		// 1. Basic checks
		$ret = array(
			'name'			=> !empty($this->_state->name),
			'email'			=> !empty($this->_state->email),
			'address1'		=> !empty($this->_state->address1),
			'country'		=> !empty($this->_state->country),
			'state'			=> !empty($this->_state->state),
			'city'			=> !empty($this->_state->city),
			'zip'			=> !empty($this->_state->zip),
			'businessname'	=> !empty($this->_state->businessname),
			'occupation'	=> !empty($this->_state->occupation),
			'vatnumber'		=> !empty($this->_state->vatnumber),
			'coupon'		=> !empty($this->_state->coupon)
		);
		
		$ret['rawDataForDebug'] = $this->_state->getData();
		
		// Email validation
		if(!empty($this->_state->email)) {
			$list = KFactory::tmp('admin::com.akeebasubs.model.jusers')
				->email($this->_state->email)
				->getList();
			$validEmail = true;
			foreach($list as $item) {
				if($item->email == $this->_state->email) {
					if($item->id != KFactory::get('lib.joomla.user')->id) $validEmail = false;
					break;
				}
			}
			$ret['email'] = $validEmail;
		}
		
		// 2. Country validation
		if($ret['country']) {
			$dummy = KFactory::get('admin::com.akeebasubs.template.helper.listbox');
			$ret['country'] = array_key_exists($this->_state->country, ComAkeebasubsTemplateHelperListbox::$countries);
		}
		
		// 3. State validation
		if(in_array($this->_state->country,array('US','CA'))) {
			$dummy = KFactory::get('admin::com.akeebasubs.template.helper.listbox');
			$ret['state'] = array_key_exists($this->_state->state, ComAkeebasubsTemplateHelperListbox::$states);
		} else {
			$ret['state'] = true;
		}
		
		// 4. Business validation
		if(!$this->_state->isbusiness) {
			$ret['businessname'] = true;
			$ret['occupation'] = true;
			$ret['vatnumber'] = false;
		} else {
			// Do I have to check the VAT number?
			if(in_array($this->_state->country, $this->european_states)) {
				// Validate VAT number
				$country = ($this->_state->country == 'GR') ? 'EL' : $this->_state->country;
				$vat = trim(strtoupper($this->_state->vatnumber));
				$url = 'http://isvat.appspot.com/'.$country.'/'.$vat.'/';
				
				// Is the validation already cached?
				$key = $country.$vat;
				$ret['vatnumber'] = null;
				if(array_key_exists('vat', $this->_cache)) {
					if(array_key_exists($key, $this->_cache['vat'])) {
						$ret['vatnumber'] = $this->_cache['vat'][$key];
					}
				}				
				
				if(is_null($ret['vatnumber']))
				{
					$res = @file_get_contents($url);
					if($res === false) {
						$ch = curl_init($url);
						url_setopt($ch, CURLOPT_HEADER, 0);
						url_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$res = @curl_exec($ch);
					}
	
					if($res !== false) {
						$res = @json_decode($res);
					}
					
					$ret['vatnumber'] = $res === true;
					
					if(!array_key_exists('vat', $this->_cache)) {
						$this->_cache['vat'] = array();
					}
					$this->_cache['vat'][$key] = $ret['vatnumber'];
					$encodedCacheData = json_encode($this->_cache);
					KRequest::set('session.akeebasubs.subscribe.validation.cache.data',$encodedCacheData);
				}
			}
		}
		
		// 5. Coupon validation
		$ret['coupon'] = $this->_validateCoupon(true);
		
		return (object)$ret;
	}
	
	/**
	 * Calculates the level's price applicable to the specific user and the
	 * actual state information
	 */
	private function _validatePrice()
	{
		// Get the default price value
		$level = KFactory::tmp('site::com.akeebasubs.model.levels')
			->id($this->_state->id)
			->getItem();
		$netPrice = (float)$level->price;

		// Coupon discount
		$couponDiscount = 0;
		$validCoupon = $this->_validateCoupon(false);
		
		$couponDiscount = 0;
		if($validCoupon) {
			$coupon = KFactory::tmp('site::com.akeebasubs.model.coupons')
				->coupon(strtoupper($this->_state->coupon))
				->getItem();
				
			switch($coupon->type) {
				case 'value':
					$couponDiscount = (float)$coupon->value;
					if($couponDiscount > $netPrice) $couponDiscount = $netPrice;
					if($couponDiscount <= 0) $couponDiscount = 0;
					break;
					
				case 'percent':
					$percent = (float)$coupon->value / 100.0;
					if( $percent <= 0 ) $percent = 0;
					if( $percent > 1 ) $percent = 1;
					$couponDiscount = $percent * $netPrice;
					break;
			}
		}
		
		// Upgrades (auto-rule) validation
		$autoDiscount = $this->_getAutoDiscount();
		
		$useCoupon = false;
		$useAuto = false;
		if($validCoupon) {
			if($autoDiscount > $couponDiscount) {
				$discount = $autoDiscount;
				$useAuto = true;
			} else {
				$discount = $couponDiscount;
				$useCoupon = true;
			}	
		} else {
			$discount = $autoDiscount;
			$useAuto = true;
		}
		
		$discount = (float)max($couponDiscount, $autoDiscount);
		
		// Get the applicable tax rule
		$taxRule = $this->_getTaxRule();
		
		return (object)array(
			'net'		=> sprintf('%1.02f',$netPrice),
			'discount'	=> sprintf('%1.02f',$discount),
			'taxrate'	=> sprintf('%1.02f',(float)$taxRule->taxrate),
			'tax'		=> sprintf('%1.02f',0.01 * $taxRule->taxrate * ($netPrice - $discount)),
			'gross'		=> sprintf('%1.02f',($netPrice - $discount) + 0.01 * $taxRule->taxrate * ($netPrice - $discount)),
			'usecoupon'	=> $useCoupon ? 1 : 0,
			'useauto'	=> $useAuto ? 1 : 0
		);
	}
	
	/**
	 * Validates a coupon code, making sure it exists, it's activated, it's not expired,
	 * it applies to the specific subscription and user.
	 */
	private function _validateCoupon($validIfNotExists = true)
	{
		static $couponCode = null;
		static $valid = false;
	
		if($this->_state->coupon) {
			if($this->_state->coupon == $couponCode) {
				return $valid;
			}
		}
	
		$valid = $validIfNotExists;		
		if($this->_state->coupon) {
			$couponCode = $this->_state->coupon;
			$valid = false;
			
			$coupon = KFactory::tmp('site::com.akeebasubs.model.coupons')
				->coupon(strtoupper($this->_state->coupon))
				->getItem();
				
			if(is_object($coupon)) {
				$valid = false;
				if($coupon->enabled) {
					// Check validity period
					jimport('joomla.utilities.date');
					$jFrom = new JDate($coupon->publish_up);
					$jTo = new JDate($coupon->publish_down);
					$jNow = new JDate();
					
					$valid = ($jNow->toUnix() >= $jFrom->toUnix()) && ($jNow->toUnix() <= $jTo->toUnix());
					
					// Check levels list
					if($valid && !empty($coupon->subscriptions)) {
						$levels = explode(',', $coupon->subscriptions);
						$valid = in_array($this->_state->id, $levels);
					}
					
					// Check user
					if($valid && $coupon->user) {
						$user_id = JFactory::getUser()->id;
						$valid = $user_id == $coupon->user;
					}
					
					// Check hits limit
					if($valid && $coupon->hitslimit) {
						if($coupon->hitslimit >= 0) {
							$valid = $coupon->hits < $coupon->hitslimit;
						}
					}
				} else {
					$valid = false;
				}
			}
		}
		
		return $valid;
	}	
	
	/**
	 * Loads any relevant upgade (auto discount) rules and returns the max discount possible
	 * under those rules.
	 *
	 * @return array Discount type and value
	 */
	private function _getAutoDiscount()
	{
		// Check that we do have a user (if there's no logged in user, we have no subscription information,
		// ergo upgrades are not applicable!)
		$user_id = KFactory::get('lib.joomla.user')->id;
		if(empty($user_id)) return 0;
		
		// Get applicable auto-rules
		$autoRules = KFactory::tmp('admin::com.akeebasubs.model.upgrades')
			->to_id($this->_state->id)
			->enabled(1)
			->getList();
			
		if(empty($autoRules)) return 0;
		
		// Get the user's list of subscriptions
		$subscriptions = KFactory::tmp('site::com.akeebasubs.model.subscriptions')
			->user_id($user_id)
			->enabled(1)
			->getList();
			
		if(empty($subscriptions)) return 0;
		
		$subs = array();
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();
		foreach($subscriptions as $subscription) {
			$jFrom = new JDate($subscription->publish_up);
			$uFrom = $jFrom->toUnix();
			$presence = $uNow - $uFrom;
			$subs[$subscription->id] = $presence;
		}
		
		// Get the current subscription level's net worth
		$level = KFactory::tmp('site::com.akeebasubs.model.levels')
			->id($this->_state->id)
			->getItem();
		$net = (float)$level->price;
		
		if($net == 0) return 0;
		
		$discount = 0;
		
		foreach($autoRules as $rule) {
			if(!array_key_exists($rule->from_id, $subs)) continue;
			if($subs[$rule->from_id] < $rule->min_presence*86400) continue;
			if($subs[$rule->from_id] > $rule->max_presence*86400) continue;
			
			switch($rule->type) {
				case 'value':
					if($rule->value > $discount) $discount = $rule->value;
					break;
					
				case 'percent':
					$newDiscount = $net * (float)$rule->value / 100.00;
					if($newDiscount > $discount) $discount = $newDiscount;
					break;
			}
		}
		
		return $discount;
	}
	
	/**
	 * Gets the applicable tax rule based on the state variables
	 */
	private function _getTaxRule()
	{
		// Do we have a VIES registered VAT number?
		$validation = $this->_validateState();
		$isVIES = $validation->vatnumber && in_array($this->_state->country, $this->european_states);
		
		// Load the tax rules
		$taxrules = KFactory::tmp('site::com.akeebasubs.model.taxrules')
			->enabled(1)
			->sort('ordering')
			->direction('ASC')
			->limit(0)
			->offset(0)
			->getList();

		$bestTaxRule = (object)array(
			'match'		=> 0,
			'fuzzy'		=> 0,
			'taxrate'	=> 0
		);
			
		foreach($taxrules as $ruleRow)
		{
			// Pre-condition variables
			$rule = (object)$ruleRow->getData();
			
			// For each rule, get the match and fuzziness rating. The best, least fuzzy and last match wins.
			$match = 0;
			$fuzzy = 0;
			
			if(empty($rule->country)) {
				$match++;
				$fuzzy++;
			} elseif($rule->country == $this->_state->country) {
				$match++;
			}
			
			if(empty($rule->state)) {
				$match++;
				$fuzzy++;
			} elseif($rule->state == $this->_state->state) {
				$match++;
			}
			
			if(empty($rule->city)) {
				$match++;
				$fuzzy++;
			} elseif(strtolower(trim($rule->city)) == strtolower(trim($this->_state->city))) {
				$match++;
			}
			
			if( ($rule->vies && $isVIES) || (!$rule->vies && !$isVIES)) {
				$match++;
			}
			
			if(
				($bestTaxRule->match < $match) ||
				( ($bestTaxRule->match == $match) && ($bestTaxRule->fuzzy > $fuzzy) )
			) {
				if($match == 0) continue;
				$bestTaxRule->match = $match;
				$bestTaxRule->fuzzy = $fuzzy;
				$bestTaxRule->taxrate = $rule->taxrate;
				$bestTaxRule->id = $rule->id;
			}
		}
		return $bestTaxRule;
	}
	
	/**
	 * Gets a list of payment plugins and their titles
	 */
	public function getPaymentPlugins()
	{
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akpayment');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKPaymentGetIdentity');

		return $jResponse; // name, title
	}

	/**
	 * Processes the form data and creates a new subscription
	 */
	public function createNewSubscription()
	{
		// Step #1. Check the validity of the user supplied information
		// ----------------------------------------------------------------------
		$validation = $this->getValidation();
		
		$isValid = true;
		foreach($validation->validation as $key => $validData)
		{
			// An invalid (not VIES registered) VAT number is not a fatal error
			if($key == 'vatnumber') continue;
			// A wrong coupon code is not a fatal error
			if($key == 'coupon') continue;
			
			$isValid = $isValid && $validData;
			if(!$isValid) {
				if($key == 'username') {
					$user = KFactory::get('lib.joomla.user');
					if($user->username == $this->_state->username) {
						$isValid = true;
					} else {
						break;
					}
				}
				break;
			}
		}
		if(!$isValid) return false;

		// Step #2. Check that the payment plugin exists or return false
		// ----------------------------------------------------------------------
		$plugins = $this->getPaymentPlugins();
		$found = false;
		if(!empty($plugins)) {
			foreach($plugins as $plugin) {
				if($plugin->name == $this->_state->paymentmethod) {
					$found = true;
					break;
				}
			}
		}
		if(!$found) return false;
		
		// Step #3. Create a user record if required and send out the email with user information
		// ----------------------------------------------------------------------
		$user = JFactory::getUser();
		if($user->id == 0) {
			// New user
			$params = array(
				'name'			=> $this->_state->name,
				'username'		=> $this->_state->username,
				'email'			=> $this->_state->email,
				'password'		=> $this->_state->password,
				'password2'		=> $this->_state->password2
			);
			
			$acl =& JFactory::getACL();
			
			jimport('joomla.application.component.helper');
			$usersConfig = &JComponentHelper::getParams( 'com_users' );
			$user = JFactory::getUser(0);
			
			$newUsertype = $usersConfig->get( 'new_usertype' );
			if (!$newUsertype) {
				$newUsertype = 'Registered';
			}
			$params['gid'] = $acl->get_group_id( '', $newUsertype, 'ARO' );
			$params['sendEmail'] = 1;
			
			// We always block the user, so that only a successful payment or
			// clicking on the email link activates his account. This is to
			// prevent spam registrations when the subscription form is abused.
			jimport('joomla.user.helper');
			$params['block'] = 1;
			$params['activation'] = JUtility::getHash( JUserHelper::genRandomPassword() );
			
			$userIsSaved = true;
			if (!$user->bind( $params )) {
				JError::raiseWarning('', JText::_( $user->getError())); // ...raise a Warning
    			$userIsSaved = false;
			} elseif (!$user->save()) { // if the user is NOT saved...
			    JError::raiseWarning('', JText::_( $user->getError())); // ...raise a Warning
			    $userIsSaved = false;
			}
			
			if($userIsSaved) {
				// Send out user registration email
				$this->_sendMail($user, $this->_state->password);
			}
		} else {
			// Update existing user's details
			$userRecord = KFactory::get('admin::com.akeebasubs.model.jusers')
				->id($user->id)
				->getItem();
			if( ($userRecord->name != $this->_state->name) || ($userRecord->email != $this->_state->email) ) {
				$userIsSaved = $userRecord->setData(array(
						'name'			=> $this->_state->name,
						'email'			=> $this->_state->email
					))->save();
			} else {
				$userIsSaved = true;
			}
		}
		if(!$userIsSaved) return false;
		
		// Step #4. Create or add user extra fields
		// ----------------------------------------------------------------------
		// Find an existing record
		$list = KFactory::tmp('site::com.akeebasubs.model.users')
			->user_id($user->id)
			->getList();
		
		if(!count($list)) {
			$id = 0;
		} else {
			$list->rewind();
			$id = $list->current()->id;
		}
		$data = array(
			'id'			=> $id,
			'user_id'		=> $user->id,
			'isbusiness'	=> $this->_state->isbusiness ? 1 : 0,
			'businessname'	=> $this->_state->businessname,
			'occupation'	=> $this->_state->occupation,
			'vatnumber'		=> $this->_state->vatnumber,
			'viesregistered' => $validation->validation->vatnumber,
			'taxauthority'	=> '', // TODO Ask for tax authority
			'address1'		=> $this->_state->address1,
			'address2'		=> $this->_state->address2,
			'city'			=> $this->_state->city,
			'state'			=> $this->_state->state,
			'zip'			=> $this->_state->zip,
			'country'		=> $this->_state->country
		);
		KFactory::tmp('site::com.akeebasubs.model.users')
			->id($id)
			->getItem()
			->setData($data)
			->save();
		
		// Step #5. Check for existing subscription records and calculate the subscription expiration date
		// ----------------------------------------------------------------------
		$subscriptions = KFactory::tmp('site::com.akeebasubs.model.subscriptions')
			->user_id($user->id)
			->level($this->_state->id)
			->enabled(1)
			->getList();
			
		$jNow = new JDate();
		$now = $jNow->toUnix();
		$mNow = $jNow->toMySQL();
		
		if(empty($subscriptions)) {
			$startDate = $now;
		} else {
			$startDate = $now;
			foreach($subscriptions as $row) {
				// Only take into account active subscriptions
				if(!$row->enabled) continue;
				// Calculate the expiration date
				$jDate = new JDate($row->publish_down);
				$expiryDate = $jDate->toUnix();
				// If the subscription expiration date is earlier than today, ignore it
				if($expiryDate < $now) continue;
				// If the previous subscription's expiration date is later than the current start date,
				// update the start date to be one second after that.
				if($expiryDate > $startDate) {
					$startDate = $expiryDate + 1;
				}
			}
		}
		
		// Step #6. Create a new subscription record
		// ----------------------------------------------------------------------
		$level = KFactory::tmp('site::com.akeebasubs.model.levels')
			->id($this->_state->id)
			->getItem();
		$duration = (int)$level->duration * 3600 * 24;
		$endDate = $startDate + $duration;

		$jStartDate = new JDate($startDate);
		$mStartDate = $jStartDate->toMySQL();
		$jEndDate = new JDate($endDate);
		$mEndDate = $jEndDate->toMySQL();
		
		$data = array(
			'id'					=> null,
			'user_id'				=> $user->id,
			'akeebasubs_level_id'	=> $this->_state->id,
			'publish_up'			=> $mStartDate,
			'publish_down'			=> $mEndDate,
			'notes'					=> '',
			'enabled'				=> ($validation->price->gross == 0),
			'processor'				=> ($validation->price->gross == 0) ? 'none' : $this->_state->paymentmethod,
			'processor_key'			=> ($validation->price->gross == 0) ? $this->_uuid(true) : '',
			'state'					=> ($validation->price->gross == 0) ? 'C' : 'N',
			'net_amount'			=> $validation->price->net - $validation->price->discount,
			'tax_amount'			=> $validation->price->tax,
			'gross_amount'			=> $validation->price->gross,
			'created_on'			=> $mNow,
			'params'				=> '',
			'contact_flag'			=> 0,
			'first_contact'			=> '0000-00-00 00:00:00',
			'second_contact'		=> '0000-00-00 00:00:00'
		);
		$subscription = KFactory::tmp('site::com.akeebasubs.model.subscriptions')
			->id(0)
			->getItem();
		$subscription->setData($data)->save();
		$this->_item = $subscription;

		// Step #7. Hit the coupon code, if a coupon is indeed used
		// ----------------------------------------------------------------------
		if($validation->price->usecoupon) {
			$coupon = KFactory::tmp('site::com.akeebasubs.model.coupons')
				->coupon(strtoupper($this->_state->coupon))
				->getItem();
			$coupon->hits++;
			$coupon->save();
		}
		
		// Step #8. Call the specific plugin's onAKPaymentNew() method and get the redirection URL,
		//          or redirect immediately on auto-activated subscriptions
		// ----------------------------------------------------------------------
		if($subscription->gross_amount != 0) {
			// Non-zero charges; use the plugins
			$app = JFactory::getApplication();
			$jResponse = $app->triggerEvent('onAKPaymentNew',array(
				$this->_state->paymentmethod,
				$user,
				$level,
				$subscription
			));
			if(empty($jResponse)) return false;
			
			foreach($jResponse as $response) {
				if($response === false) continue;
				
				$this->paymentForm = $response;
			}
		} else {
			// Zero charges; just redirect
			$app = JFactory::getApplication();
			$slug = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->id($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
			$app->redirect( str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order')) );
			return false;
		}
		
		// Return true
		// ----------------------------------------------------------------------
		return true;
	}
	
	/**
	 * Runs a payment callback
	 */
	public function runCallback()
	{
		$rawDataPost = JRequest::get('POST', 2);
		$rawDataGet = JRequest::get('GET', 2);
		$data = array_merge($rawDataGet, $rawDataPost);
		
		$dummy = $this->getPaymentPlugins();
		
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKPaymentCallback',array(
			$this->_state->paymentmethod,
			$data
		));
		if(empty($jResponse)) return false;
		
		$status = false;
		
		foreach($jResponse as $response)
		{
			$status = $status || $response;
		}
		
		return $status;
	}
	
	/**
	 * Get the form set by the active payment plugin
	 */
	public function getForm()
	{
		return $this->paymentForm;
	}
	
	/**
	 * Returns the state data. Magically retrieves cached data.
	 */
	public function getData()
	{
		return $this->_state->getData();
	}
	
	/**
	 * Sends out an email to a specific user about his new user account
	 * and CC's the Super Administrators
	 */
	private function _sendMail(&$user, $password)
	{
		$password = preg_replace('/[\x00-\x1F\x7F]/', '', $password); //Disallow control chars in the email
		
		$mainframe = JFactory::getApplication();
		
		$lang = JFactory::getLanguage();
		$lang->load('com_user',JPATH_SITE);

		$db		=& JFactory::getDBO();

		$name 		= $user->get('name');
		$email 		= $user->get('email');
		$username 	= $user->get('username');

		$usersConfig 	= &JComponentHelper::getParams( 'com_users' );
		$sitename 		= $mainframe->getCfg( 'sitename' );
		$useractivation = $usersConfig->get( 'useractivation' );
		$mailfrom 		= $mainframe->getCfg( 'mailfrom' );
		$fromname 		= $mainframe->getCfg( 'fromname' );
		$siteURL		= JURI::base();

		$subject 	= sprintf ( JText::_( 'Account details for' ), $name, $sitename);
		$subject 	= html_entity_decode($subject, ENT_QUOTES);

		$message = sprintf ( JText::_( 'SEND_MSG_ACTIVATE' ), $name, $sitename, $siteURL."index.php?option=com_user&task=activate&activation=".$user->get('activation'), $siteURL, $username, $password);

		$message = html_entity_decode($message, ENT_QUOTES);

		//get all super administrator
		$query = 'SELECT name, email, sendEmail' .
				' FROM #__users' .
				' WHERE LOWER( usertype ) = "super administrator"';
		$db->setQuery( $query );
		$rows = $db->loadObjectList();

		// Send email to user
		if ( ! $mailfrom  || ! $fromname ) {
			$fromname = $rows[0]->name;
			$mailfrom = $rows[0]->email;
		}

		JUtility::sendMail($mailfrom, $fromname, $email, $subject, $message);

		// Send notification to all administrators
		$subject2 = sprintf ( JText::_( 'Account details for' ), $name, $sitename);
		$subject2 = html_entity_decode($subject2, ENT_QUOTES);

		// get superadministrators id
		foreach ( $rows as $row )
		{
			if ($row->sendEmail)
			{
				$message2 = sprintf ( JText::_( 'SEND_MSG_ADMIN' ), $row->name, $sitename, $name, $email, $username);
				$message2 = html_entity_decode($message2, ENT_QUOTES);
				JUtility::sendMail($mailfrom, $fromname, $row->email, $subject2, $message2);
			}
		}
	}

	/**
	 * Generates a Universally Unique IDentifier, version 4.
	 *
	 * This function generates a truly random UUID.
	 *
	 * @paream boolean	If TRUE return the uuid in hex format, otherwise as a string
	 * @see http://tools.ietf.org/html/rfc4122#section-4.4
	 * @see http://en.wikipedia.org/wiki/UUID
	 * @return string A UUID, made up of 36 characters or 16 hex digits.
	 */
	protected function _uuid($hex = false) 
	{
	    $pr_bits = false;
	 	if (is_resource ( $this->_urand )) {
	     	$pr_bits .= @fread ( $this->_urand, 16 );
	   	}
	    
	    if (! $pr_bits) 
	    {
	        $fp = @fopen ( '/dev/urandom', 'rb' );
	        if ($fp !== false) 
	        {
	            $pr_bits .= @fread ( $fp, 16 );
	            @fclose ( $fp );
	        } 
	        else 
	        {
	            // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
	            $pr_bits = "";
	            for($cnt = 0; $cnt < 16; $cnt ++) {
	                $pr_bits .= chr ( mt_rand ( 0, 255 ) );
	            }
	        }
	    }
	    
	    $time_low = bin2hex ( substr ( $pr_bits, 0, 4 ) );
	    $time_mid = bin2hex ( substr ( $pr_bits, 4, 2 ) );
	    $time_hi_and_version = bin2hex ( substr ( $pr_bits, 6, 2 ) );
	    $clock_seq_hi_and_reserved = bin2hex ( substr ( $pr_bits, 8, 2 ) );
	    $node = bin2hex ( substr ( $pr_bits, 10, 6 ) );
	   
	    /**
	     * Set the four most significant bits (bits 12 through 15) of the
	     * time_hi_and_version field to the 4-bit version number from
	     * Section 4.1.3.
	     * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
	     */
	    $time_hi_and_version = hexdec ( $time_hi_and_version );
	    $time_hi_and_version = $time_hi_and_version >> 4;
	    $time_hi_and_version = $time_hi_and_version | 0x4000;
	   
	    /**
	     * Set the two most significant bits (bits 6 and 7) of the
	     * clock_seq_hi_and_reserved to zero and one, respectively.
	     */
	    $clock_seq_hi_and_reserved = hexdec ( $clock_seq_hi_and_reserved );
	    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
	    $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
	   
	    //Either return as hex or as string
	    $format = $hex ? '%08s%04s%04x%04x%012s' : '%08s-%04s-%04x-%04x-%012s';
	    
	    return sprintf ( $format, $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node );
	}


}