<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsModelUserinfos extends FOFModel
{
	/**
	 * List of European states
	 *
	 * @var array
	 */
	private $european_states = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
	
	/**
	 * We cache the results of all time-consuming operations, e.g. vat validation, subscription membership calculation,
	 * tax calculations, etc into this array, saved in the user's session.
	 * @var array
	 */
	private $_cache = array();
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$session = JFactory::getSession();
		$encodedCacheData = $session->get('validation_cache_data', null, 'com_akeebasubs');
		if(!is_null($encodedCacheData)) {
			$this->_cache = json_decode($encodedCacheData, true);
		} else {
			$this->_cache = array();
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
				'email2'		=> '',
				'address1'		=> '',
				'address2'		=> '',
				'country'		=> 'XX',
				'state'			=> '',
				'city'			=> '',
				'zip'			=> '',
				'isbusiness'	=> '',
				'businessname'	=> '',
				'vatnumber'		=> '',
				'coupon'		=> '',
				'occupation'	=> ''
				// @TODO Is the following line needed?
				//'custom'		=> array()
			);
		}
		
		// Otherwise we always see the same level over and over again
		if(array_key_exists('id',$this->_cache['state'])) {
			unset($this->_cache['state']['id']);
		}
		
		$rawDataCache = $this->_cache['state'];
		$rawDataPost = JRequest::get('POST', 2);
		$rawDataGet = JRequest::get('GET', 2);
		$rawData = array_merge($rawDataCache, $rawDataGet, $rawDataPost);
		if(!empty($rawData)) foreach($rawData as $k => $v) {
			if(substr($k,0,1) == chr(0)) continue; // Don't ask...
			$this->setState($k, $v);
		}
		
		// Save the new state data in the cache
		$this->_cache['state'] = (array)($this->getState());
		$encodedCacheData = json_encode($this->_cache);
		$session->set('validation_cache_data', $encodedCacheData, 'com_akeebasubs');
	}
	
	private function getStateVariables()
	{
		$session = JFactory::getSession();
		$firstRun = $session->get('firstrun', true, 'com_akeebasubs');
		if($firstRun) {
			$session->set('firstrun', false, 'com_akeebasubs');
		}
		
		return (object)array(
			'firstrun'			=> $firstRun,
			'slug'				=> $this->getState('slug','','string'),
			'id'				=> $this->getState('id',0,'int'),
			'paymentmethod'		=> $this->getState('paymentmethod','none','cmd'),
			'processorkey'		=> $this->getState('processorkey','','raw'),
			'username'			=> $this->getState('username','','string'),
			'password'			=> $this->getState('password','','raw'),
			'password2'			=> $this->getState('password2','','raw'),
			'name'				=> $this->getState('name','','string'),
			'email'				=> $this->getState('email','','string'),
			'email2'			=> $this->getState('email2','','string'),
			'address1'			=> $this->getState('address1','','string'),
			'address2'			=> $this->getState('address2','','string'),
			'country'			=> $this->getState('country','','cmd'),
			'state'				=> $this->getState('state','','cmd'),
			'city'				=> $this->getState('city','','string'),
			'zip'				=> $this->getState('zip','','string'),
			'isbusiness'		=> $this->getState('isbusiness','','int'),
			'businessname'		=> $this->getState('businessname','','string'),
			'occupation'		=> $this->getState('occupation','','string'),
			'vatnumber'			=> $this->getState('vatnumber','','cmd'),
			'coupon'			=> $this->getState('coupon','','string'),
			// @TODO Is the following line needed?
			//'custom'			=> $this->getState('custom','','raw'),
			'opt'				=> $this->getState('opt','','cmd')
		);
	}
	
	/**
	 * Performs a validation
	 */
	public function getValidation()
	{
		$response = new stdClass();
		
		$state = $this->getStateVariables();
		
		switch($state->opt)
		{
			case 'username':
				$response->validation = $this->_validateUsername();
				break;
				
			default:
				$response->validation = $this->_validateState();
				$response->validation->username = $this->_validateUsername()->username;
				
				// @TODO Is the following block needed?
				// Get the results from the custom validation
				$response->custom_validation = array();
				$response->custom_valid = true;
				jimport('joomla.plugin.helper');
				JPluginHelper::importPlugin('akeebasubs');
				$app = JFactory::getApplication();
				$jResponse = $app->triggerEvent('onValidate', array($state));
				if(is_array($jResponse) && !empty($jResponse)) {
					foreach($jResponse as $pluginResponse) {
						if(!is_array($pluginResponse)) continue;
						if(!array_key_exists('valid', $pluginResponse)) continue;
						if(!array_key_exists('custom_validation', $pluginResponse)) continue;
						$response->custom_valid = $response->custom_valid && $pluginResponse['valid'];
						$response->custom_validation = array_merge($response->custom_validation, $pluginResponse['custom_validation']);
						if(array_key_exists('data', $pluginResponse)) {
							$state = $pluginResponse['data'];
						}
					}
				}
				break;
		}
		return $response;
	}
	
	/**
	 * Validates the username for uniqueness
	 */
	private function _validateUsername()
	{
		$state = $this->getStateVariables();
		
		$ret = (object)array('username' => false);
		$username = $state->username;
		if(empty($username)) return $ret;
		$myUser = JFactory::getUser();
		$user = FOFModel::getTmpInstance('Jusers','AkeebasubsModel')
			->username($username)
			->getFirstItem();
		
		if($myUser->guest) {
			if(empty($user->username)) {
				$ret->username = true;
			} else {
				// If it's a blocked user, we should allow reusing the username;
				// this would be a user who tried to subscribe, closed the payment
				// window and came back to re-register. However, if the validation
				// field is non-empty, this is a manually blocked user and should
				// not be allowed to subscribe again.
				if($user->block) {
					if(!empty($user->activation)) {
						$ret->username = true;
					} else {
						$ret->username = false;
					}
				} else {
					$ret->username = false;
				}
			}
			
		} else {
			$ret->username = ($user->username == $myUser->username);
		}
		return $ret;
	}
	
	/**
	 * Validates the state data for completeness
	 */
	private function _validateState()
	{
		$state = $this->getStateVariables();
		
		// 1. Basic checks
		$ret = array(
			'name'			=> !empty($state->name),
			'email'			=> !empty($state->email),
			'email2'		=> !empty($state->email2) && ($state->email == $state->email2),
			'address1'		=> !empty($state->address1),
			'country'		=> !empty($state->country),
			'state'			=> !empty($state->state),
			'city'			=> !empty($state->city),
			'zip'			=> !empty($state->zip),
			'businessname'	=> !empty($state->businessname),
			'vatnumber'		=> !empty($state->vatnumber),
			'coupon'		=> !empty($state->coupon)
		);
		
		$ret['rawDataForDebug'] = (array)$state;
		
		// Name validation; must contain AT LEAST two parts (name/surname)
		// separated by a space
		if(!empty($state->name)) {
			$name = trim($state->name);
			$nameParts = explode(" ", $name);
			if(count($nameParts) < 2) $ret['name'] = false;
		}
		
		// Email validation
		if(!empty($state->email)) {
			$list = FOFModel::getTmpInstance('Jusers','AkeebasubsModel')
				->email($state->email)
				->getItemList();
			$validEmail = true;
			foreach($list as $item) {
				if($item->email == $state->email) {
					if($item->id != JFactory::getUser()->id) {
						if(!$item->block) {
							// Email belongs to a non-blocked user; this is not allowed.
							$validEmail = false;
							break;
						} else {
							// Email belongs to a blocked user. Allow reusing it,
							// if the user is not activated yet. The idea is that
							// a newly created user is blocked and has the activation
							// field filled in. This is a user who failed to complete
							// his subscription. If the validation field is empty, it
							// is a user blocked by the administrator who should not
							// be able to subscribe again!
							if(empty($item->activation)) {
								$validEmail = false;
								break;
							}
						}
					}
				}
			}
			
			// Double check that it's a valid email
			if($validEmail) $validEmail = $this->validEmail($state->email);
			
			$ret['email'] = $validEmail;
		} else {
			$ret['email'] = false;
		}
		
		// 2. Country validation
		if($ret['country']) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';
			$ret['country'] = array_key_exists($state->country, AkeebasubsHelperSelect::$countries);
		}
		
		// 3. State validation
		if(in_array($state->country,array('US','CA'))) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';
			$ret['state'] = false;
			foreach(AkeebasubsHelperSelect::$states as $country => $states) {
				if(array_key_exists($state->state, $states)) $ret['state'] = true;
			}
		} else {
			$ret['state'] = true;
		}
		
		// 4. Business validation
		if(!$state->isbusiness) {
			$ret['businessname'] = true;
			$ret['vatnumber'] = false;
		} else {
			// Do I have to check the VAT number?
			if(in_array($state->country, $this->european_states)) {
				// If the country has two rules with VIES enabled/disabled and a non-zero VAT,
				// we will skip VIES validation. We'll also skip validation if there are no
				// rules for this country (the default tax rate will be applied)
				$taxrules = FOFModel::getTmpInstance('Taxrules','AkeebasubsModel')
					->savestate(0)
					->enabled(1)
					->country($state->country)
					->filter_order('ordering')
					->filter_order_Dir('ASC')
					->limit(0)
					->limitstart(0)
					->getList();
				$catchRules = 0;
				$lastVies = null;
				if(!empty($taxrules)) foreach($taxrules as $rule) {
					if( empty($rule->state) && empty($rule->city) && $rule->taxrate && ($lastVies != $rule->vies) ) {
						$catchRules++;
						$lastVies = $rule->vies;
					}
				}
				$mustCheck = ($catchRules < 2) && ($catchRules > 0);
			
				if($mustCheck) {
					$ret['vatnumber'] = $this->isVIESValidVAT($state->country, $state->vatnumber);
					$ret['novatrequired'] = false;
				} else {
					$ret['novatrequired'] = true;
				}
			}
		}
		
		// 5. Coupon validation
		$ret['coupon'] = $this->_validateCoupon(true);
		
		return (object)$ret;
	}

	/**
	 * Get the form set by the active payment plugin
	 */
	/*public function getForm()
	{
		return $this->paymentForm;
	}*/
	
	/**
	 * Returns the state data.
	 */
	public function getData()
	{
		return $this->getStateVariables();
	}
	
	private function validEmail($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else if ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} else if ($local[0] == '.' || $local[$localLen-1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} else if (preg_match('/\\.\\./', $local)) {
				// local part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				$isValid = false;
			} else if (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				$isValid = false;
			} else if
				(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
				str_replace("\\\\","",$local))) {
				// character not valid in local part unless 
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/',
				str_replace("\\\\","",$local))) {
					$isValid = false;
				}
			}
			
			// Check the domain name
			if($isValid && !$this->is_valid_domain_name($domain)) {
				return false;
			}
			
			// Uncomment below to have PHP run a proper DNS check (risky on shared hosts!)
			/**
			if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
				// domain not found in DNS
				$isValid = false;
			}
			/**/
		}
		return $isValid;
	}
	
	function is_valid_domain_name($domain_name)
	{
		$pieces = explode(".",$domain_name);
		foreach($pieces as $piece) {
			if (!preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $piece)
				|| preg_match('/-$/', $piece) ) {
				return false;
			}
		}
		return true;
	}
	
	private function isVIESValidVAT($country, $vat)
	{
		// Validate VAT number
		$vat = trim(strtoupper($vat));
		$country = $country == 'GR' ? 'EL' : $country;
		// (remove the country prefix if present)
		if(substr($vat,0,2) == $country) $vat = trim(substr($vat,2));

		// Is the validation already cached?
		$key = $country.$vat;
		$ret = null;
		if(array_key_exists('vat', $this->_cache)) {
			if(array_key_exists($key, $this->_cache['vat'])) {
				$ret = $this->_cache['vat'][$key];
			}
		}
		
		if(!is_null($ret)) return $ret;
		
		if(empty($vat)) {
			$ret = false;
		} else {
			if(!class_exists('SoapClient')) {
				$ret = false;
			} else {
				// Using the SOAP API
				// Code credits: Angel Melguiz / KMELWEBDESIGN SLNE (www.kmelwebdesign.com)
				$sClient = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
				$params = array('countryCode'=>$country,'vatNumber'=>$vat);
				$response = $sClient->checkVat($params);
				if ($response->valid) {
					$ret = true;
				}else{
					$ret = false;
				}
			}
		}

		// Cache the result
		if(!array_key_exists('vat', $this->_cache)) {
			$this->_cache['vat'] = array();
		}
		$this->_cache['vat'][$key] = $ret;
		$encodedCacheData = json_encode($this->_cache);

		$session = JFactory::getSession();
		$session->set('validation_cache_data', $encodedCacheData, 'com_akeebasubs');
		
		// Return the result
		return $ret;
	}
	
	public function save($data)
	{
		// Step #1. Check the validity of the user supplied information
		// ----------------------------------------------------------------------
		$validation = $this->getValidation();
		$state = $this->getStateVariables();
		
		// Iterate the core validation rules
		$isValid = true;
		foreach($validation->validation as $key => $validData)
		{
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
			if(!AkeebasubsHelperCparams::getParam('personalinfo',1)) {
				if(!in_array($key, array('username','email','email2','name'))) continue;
			}
			// An invalid (not VIES registered) VAT number is not a fatal error
			if($key == 'vatnumber') continue;
			// A wrong coupon code is not a fatal error
			if($key == 'coupon') continue;
			// A missing business occupation is not a fatal error either
			if($key == 'occupation') continue;
			// This is a dummy key which must be ignored
			if($key == 'novatrequired') continue;
			
			$isValid = $isValid && $validData;
			if(!$isValid) {
				if($key == 'username') {
					$user = JFactory::getUser();
					if($user->username == $state->username) {
						$isValid = true;
					} else {
						break;
					}
				}
				break;
			}
		}
		// Make sure custom fields also validate
		//$isValid = $isValid && $validation->custom_valid;
		
		if(!$isValid) {
			$this->setError(JText::_('COM_AKEEBASUBS_LBL_USERINFO_ERROR'));
			return false;
		}
		
		// Reset the session flag, so that future registrations will merge the
		// data from the database
		JFactory::getSession()->set('firstrun', true, 'com_akeebasubs');

		
		// Step #2. Create or update a user record
		// ----------------------------------------------------------------------
		$user = JFactory::getUser();
		
		if($user->id == 0) {
			// Check for an existing, blocked, unactivated user with the same
			// username or email address.
			$user1 = FOFModel::getTmpInstance('Jusers','AkeebasubsModel')
				->username($state->username)
				->block(1)
				->getFirstItem();
			$user2 = FOFModel::getTmpInstance('Jusers','AkeebasubsModel')
				->email($state->email)
				->block(1)
				->getFirstItem();
			$id1 = $user1->id;
			$id2 = $user2->id;
			// Do we have a match?
			if($id1 || $id2) {
				if($id1 == $id2) {
					// Username and email match with the blocked user; reuse that
					// user, please.
					$user = JFactory::getUser($user1->id);
				} else {
					// Remove the last subscription for $user2 (it will be an unpaid one)
					$submodel = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel');
					$substodelete = $submodel
						->user_id($id2)
						->getList();
					if(!empty($substodelete)) foreach($substodelete as $subtodelete) {
						$subtable = $submodel->getTable();
						$subtable->delete($subtodelete->akeebasubs_subscription_id);
					}
					
					// Remove $user2 and set $user to $user1 so that it gets updated
					$user2->delete($id2);
					$user = JFactory::getUser($user1->id);
				}
			}
		}
		
		if(is_null($user->id) || ($user->id == 0)) {
			// New user
			$params = array(
				'name'			=> $state->name,
				'username'		=> $state->username,
				'email'			=> $state->email,
				'password'		=> $state->password,
				'password2'		=> $state->password2
			);
			
			$user = JFactory::getUser(0);

			jimport('joomla.application.component.helper');
			$usersConfig = &JComponentHelper::getParams( 'com_users' );
			$newUsertype = $usersConfig->get( 'new_usertype' );
			
			if(version_compare(JVERSION, '1.6.0', 'ge')) {
				// get the New User Group from com_users' settings
				if(empty($newUsertype)) $newUsertype = 2;
				$params['groups'] = array($newUsertype);
			} else {
				if (!$newUsertype) {
					$newUsertype = 'Registered';
				}
				$acl = JFactory::getACL();
				$params['gid'] = $acl->get_group_id( '', $newUsertype, 'ARO' );
			}
			
			$params['sendEmail'] = 0;
			
			// Set the user's default language to whatever the site's current language is
			$params['params'] = array(
				'language'	=> JFactory::getConfig()->getValue('config.language')
			);
			
			// We always block the user, so that only a successful payment or
			// clicking on the email link activates his account. This is to
			// prevent spam registrations when the subscription form is abused.
			jimport('joomla.user.helper');
			$params['block'] = 1;
			$params['activation'] = JUtility::getHash( JUserHelper::genRandomPassword() );
			
			$userIsSaved = false;
			$user->bind($params);
			$userIsSaved = $user->save();
		} else {
			// Remove unpaid subscriptions on the same level for this user
			$unpaidSubs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->user_id($user->id)
				->paystate('N','X')
				->getItemList();
			if(!empty($unpaidSubs)) foreach($unpaidSubs as $unpaidSub) {
				$table = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')->getTable();
				$table->delete($unpaidSub->akeebasubs_subscription_id);
			}
			
			// Update existing user's details
			$userRecord = FOFModel::getTmpInstance('Jusers','AkeebasubsModel')
				->setId($user->id)
				->getItem();
			
			$updates = array(
				'name'			=> $state->name,
				'email'			=> $state->email
			);
			if(!empty($state->password) && ($state->password = $state->password2)) {
				jimport('joomla.user.helper');
				$salt = JUserHelper::genRandomPassword(32);
				$pass = JUserHelper::getCryptedPassword($state->password, $salt);
				$updates['password'] = $pass.':'.$salt;
			}
			if(!empty($state->username)) {
				$updates['username'] = $state->username;
			}
			$userIsSaved = $userRecord->save($updates);			
		}
		
		if(!$userIsSaved) {
			$this->setError($user->getError());
			return false;
		}
		
		
		// Step #3. Create or add user extra fields
		// ----------------------------------------------------------------------
		// Find an existing record
		$list = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getItemList();

		if(!count($list)) {
			$id = 0;
		} else {
			$thisUser = array_pop($list);
			$id = $thisUser->akeebasubs_user_id;
		}
		
		$data = array(
			'akeebasubs_user_id' => $id,
			'user_id'		=> $user->id,
			'isbusiness'	=> $state->isbusiness ? 1 : 0,
			'businessname'	=> $state->businessname,
			'occupation'	=> $state->occupation,
			'vatnumber'		=> $state->vatnumber,
			'viesregistered' => $validation->validation->vatnumber,
			// @todo Ask for tax authority
			'taxauthority'	=> '',
			'address1'		=> $state->address1,
			'address2'		=> $state->address2,
			'city'			=> $state->city,
			'state'			=> $state->state,
			'zip'			=> $state->zip,
			'country'		=> $state->country,
			'params'		=> $state->custom
		);
		
		// @TODO Is the following block needed?
		// Allow plugins to post-process the fields
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKSignupUserSave', array((object)$data));
		if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $pResponse) {
			if(!is_array($pResponse)) continue;
			if(empty($pResponse)) continue;
			if(array_key_exists('params', $pResponse)) {
				if(!empty($pResponse['params'])) foreach($pResponse['params'] as $k => $v) {
					$data['params'][$k] = $v;
				}
				unset($pResponse['params']);
			}
			$data = array_merge($data, $pResponse);
		}
		
		// Serialize custom fields
		$data['params'] = json_encode($data['params']);
		
		FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->setId($id)
			->getItem()
			->save($data);

		
		// Clear the session
		// ----------------------------------------------------------------------
		$session = JFactory::getSession();
		$session->set('validation_cache_data', null, 'com_akeebasubs');		
		
		// Return true
		// ----------------------------------------------------------------------
		return true;
	}
}