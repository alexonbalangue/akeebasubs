<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsModelSubscribes extends KModelAbstract
{
	private $european_states = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
	
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
			->insert('id'				, 'int', 0, true)
			->insert('paymentmethod'	, 'cmd')
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
		
		// Hack me plenty: Load the state from the POST variables (grrrrr!)
		$rawDataPost = JRequest::get('POST', 2);
		$rawDataGet = JRequest::get('GET', 2);
		$rawData = array_merge($rawDataGet, $rawDataPost);
		$this->_state->setData($rawData);
	}
	
	public function getValidation()
	{
		$response = new stdClass();
		
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
	
	private function _validateUsername()
	{
		$ret = (object)array('username' => false);
		$username = $this->_state->username;
		if(empty($username)) return $ret;
		$user = JFactory::getUser($username);
		$ret->username = !is_object($user);
		return $ret;
	}
	
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
		
		// 2. Country validation
		if($ret['country']) {
			$dummy = KFactory::get('admin::com.akeebasubs.template.helper.listbox');
			$ret['country'] = array_key_exists($this->_state->country, ComAkeebasubsTemplateHelperListbox::$countries);
		}
		
		// 3. State validation
		if($ret['state']) {
			if(!in_array($this->_state->country,array('US','CA'))) {
				$ret['state'] = true;
			} else {
				$dummy = KFactory::get('admin::com.akeebasubs.template.helper.listbox');
				$ret['state'] = array_key_exists($this->_state->state, ComAkeebasubsTemplateHelperListbox::$states);
			}
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
					// TODO Save session
				}
			}
		}
		
		// 5. Coupon validation
		// FIXME No coupon validation is performed!
		$ret['coupon'] = true;
		
		return (object)$ret;
	}
	
	private function _validatePrice()
	{
		// Get the default price value
		$level = KFactory::tmp('admin::com.akeebasubs.model.levels')
			->id($this->_state->id)
			->getItem();
		$netPrice = (float)$level->price;

		$couponDiscount = 0;
		// TODO Coupon validation
		
		$autoDiscount = 0;
		// TODO Auto-rule validation
		
		$discount = (float)max($couponDiscount, $autoDiscount);
		
		// Get the applicable tax rule
		$taxRule = $this->_getTaxRule();
		
		return (object)array(
			'net'		=> sprintf('%1.02f',$netPrice),
			'discount'	=> sprintf('%1.02f',$discount),
			'taxrate'	=> sprintf('%1.02f',(float)$taxRule->taxrate),
			'tax'		=> sprintf('%1.02f',0.01 * $taxRule->taxrate * ($netPrice - $discount)),
			'gross'		=> sprintf('%1.02f',($netPrice - $discount) + 0.01 * $taxRule->taxrate * ($netPrice - $discount))
		);
	}
	
	private function _getTaxRule()
	{
		// Do we have a VIES registered VAT number?
		$validation = $this->_validateState();
		$isVIES = $validation->vatnumber && in_array($this->_state->country, $this->european_states);
		
		// Load the tax rules
		$taxrules = KFactory::tmp('admin::com.akeebasubs.model.taxrules')
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
	
	public function getPaymentPlugins()
	{
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akpayment');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKPaymentGetIdentity');

		return $jResponse; // name, title
	}
}