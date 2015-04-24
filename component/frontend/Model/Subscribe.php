<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Admin\Helper\EUVATInfo;
use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Admin\PluginAbstracts\AkpaymentBase;
use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use Akeeba\Subscriptions\Site\Model\Subscribe\Validation;
use Akeeba\Subscriptions\Site\Model\Subscribe\ValidatorFactory;
use FOF30\Container\Container;
use FOF30\Model\Model;
use FOF30\Utils\Ip;
use JFactory;

defined('_JEXEC') or die;

/**
 * This model handles validation and subscription creation
 *
 * @method $this slug() slug(string $v)
 * @method $this id() id(int $v)
 * @method $this paymentmethod() paymentmethod(string $v)
 * @method $this username() username(string $v)
 * @method $this password() password(string $v)
 * @method $this password2() password2(string $v)
 * @method $this name() name(string $v)
 * @method $this email() email(string $v)
 * @method $this email2() email2(string $v)
 * @method $this address1() address1(string $v)
 * @method $this address2() address2(string $v)
 * @method $this country() country(string $v)
 * @method $this state() state(string $v)
 * @method $this city() city(string $v)
 * @method $this zip() zip(string $v)
 * @method $this isbusiness() isbusiness(int $v)
 * @method $this businessname() businessname(string $v)
 * @method $this vatnumber() vatnumber(string $v)
 * @method $this coupon() coupon(string $v)
 * @method $this occupation() occupation(string $v)
 * @method $this custom() custom(array $v)
 * @method $this subcustom() subcustom(array $v)
 *
 * TODO Refactor this. Over 3k lines in a specialised model is just asking for trouble!
 */
class Subscribe extends Model
{
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
	 * Coupon ID used in the price calculation
	 *
	 * @var int|null
	 */
	protected $_coupon_id = null;

	/**
	 * Upgrade ID used in the price calculation
	 *
	 * @var int|null
	 */
	protected $_upgrade_id = null;

	/**
	 * Upgrade ID for expired subscriptions used in the price calculation
	 *
	 * @var int|null
	 */
	protected $_expired_upgrade_id = null;

	/**
	 * @var  ValidatorFactory  The validator object factory
	 */
	protected $validatorFactory = null;

	/**
	 * Public constructor. Initialises the internal objects used for validation and subscription creation.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->validatorFactory = new ValidatorFactory($this->container, $this->getStateVariables());
	}

	/**
	 * Gets the state variables from the form submission or validation request
	 *
	 * @param    bool  $force  Should I force-reload the data?
	 *
	 * @return   StateData
	 */
	public function getStateVariables($force = false)
	{
		static $stateVars = null;

		if (is_null($stateVars) || $force)
		{
			$stateVars = new StateData($this);
		}

		return $stateVars;
	}

	/**
	 * Gets a validator object by type. If you request the same object type again the same object will be returned.
	 *
	 * @param   string  $type  The validator type
	 *
	 * @return  Validation\Base
	 *
	 * @throws  \InvalidArgumentException  If the validator type is not found
	 */
	public function getValidator($type)
	{
		return $this->validatorFactory->getValidator($type);
	}

	/**
	 * Performs a validation
	 */
	public function getValidation()
	{
		$response = new \stdClass();

		$state = $this->getStateVariables();

		switch ($state->opt)
		{
			case 'username':
				$response->validation = $this->validateUsernamePassword();
				break;

			// Perform validations on plugins only
			case 'plugins':
				$response = $this->pluginValidation();
				break;

			default:
				$response->validation = $this->_validateState();
				$response->validation->username = $this->getValidator('username')->execute();
				$response->validation->password = $this->getValidator('password')->execute();
				$response->price = $this->validatePrice();

				$pluginResponse = $this->pluginValidation();
				$response->custom_validation = $pluginResponse->custom_validation;
				$response->custom_valid = $pluginResponse->custom_valid;
				$response->subscription_custom_validation = $pluginResponse->subscription_custom_validation;
				$response->subscription_custom_valid = $pluginResponse->subscription_custom_valid;

				break;
		}

		return $response;
	}

	/**
	 * Executes per user and per subscription custom field validation using the plugins.
	 *
	 * The return object contains the following keys:
	 * custom_validation				array
	 * custom_valid						bool
	 * subscription_custom_validation	array
	 * subscription_custom_valid		bool
	 *
	 * @return   object  See above
	 */
	private function pluginValidation()
	{
		$ret = [
			'custom_validation' => [],
			'custom_valid' => false,
			'subscription_custom_validation' => [],
			'subscription_custom_valid' => false
		];

		$customResponse = $this->getValidator('CustomFields')->execute();
		$ret['custom_validation'] = $customResponse->custom_validation;
		$ret['custom_valid'] = $customResponse->custom_valid;

		$subcustomResponse = $this->getValidator('SubscriptionCustomFields')->execute();
		$ret['subscription_custom_validation'] = $subcustomResponse->subscription_custom_validation;
		$ret['subscription_custom_valid'] = $subcustomResponse->subscription_custom_valid;

		return (object)$ret;
	}

	/**
	 * Validates the username for uniqueness
	 */
	private function validateUsernamePassword()
	{
		return (object)[
			'username' => $this->getValidator('username')->execute(),
			'password' => $this->getValidator('password')->execute()
		];
	}

	/**
	 * Validates the state data for completeness
	 */
	private function _validateState()
	{
		$state = $this->getStateVariables();

		$personalInfo = ComponentParams::getParam('personalinfo', 1);
		$allowNonEUVAT = ComponentParams::getParam('noneuvat', 0);
		$requireCoupon = ComponentParams::getParam('reqcoupon', 0) ? true : false;

		// 1. Basic checks
		$ret = array(
			'name'         => !empty($state->name),
			'email'        => !empty($state->email),
			'email2'       => !empty($state->email2) && ($state->email == $state->email2),
			'address1'     => $personalInfo == 1 ? !empty($state->address1) : true,
			'country'      => $personalInfo != 0 ? !empty($state->country) : true,
			'state'        => $personalInfo == 1 ? !empty($state->state) : true,
			'city'         => $personalInfo == 1 ? !empty($state->city) : true,
			'zip'          => $personalInfo == 1 ? !empty($state->zip) : true,
			'businessname' => $personalInfo == 1 ? !empty($state->businessname) : true,
			'vatnumber'    => $personalInfo == 1 ? !empty($state->vatnumber) : true,
			'coupon'       => $personalInfo == 1 ? !empty($state->coupon) : true,
		);

		$ret['rawDataForDebug'] = (array)$state;

		// Name validation
		$ret['name'] = $this->getValidator('Name')->execute();

		// Email validation
		$ret['email'] = $this->getValidator('Email')->execute();

		// 2. Country validation
		$ret['country'] = $this->getValidator('Country')->execute();

		// 3. State validation
		$ret['state'] = $this->getValidator('State')->execute();

		// 4. Business validation
		// Fix the VAT number's format
		$vat_check = EUVATInfo::checkVATFormat($state->country, $state->vatnumber);

		if ($vat_check->valid)
		{
			$state->vatnumber = $vat_check->vatnumber;
		}
		else
		{
			$state->vatnumber = '';
		}

		$this->setState('vatnumber', $state->vatnumber);

		if (!$state->isbusiness || ($personalInfo <= 0))
		{
			$ret['businessname'] = true;
			$ret['vatnumber'] = false;
		}
		else
		{
			// Do I have to check the VAT number?
			if (EUVATInfo::isEUVATCountry($state->country))
			{
				// If the country has two rules with VIES enabled/disabled and a non-zero VAT,
				// we will skip VIES validation. We'll also skip validation if there are no
				// rules for this country (the default tax rate will be applied)

				/** @var TaxRules $taxRulesModel */
				$taxRulesModel = $this->container->factory->model('TaxRules')->tmpInstance();

				// First try loading the rules for this level
				$taxrules = $taxRulesModel
					->enabled(1)
					->akeebasubs_level_id($state->id)
					->country($state->country)
					->filter_order('ordering')
					->filter_order_Dir('ASC')
					->get(true);

				// If this level has no rules try the "All levels" rules
				if (!$taxrules->count())
				{
					$taxrules = $taxRulesModel
						->enabled(1)
						->akeebasubs_level_id(0)
						->country($state->country)
						->filter_order('ordering')
						->filter_order_Dir('ASC')
						->get(true);
				}

				$catchRules = 0;
				$lastVies = null;

				if ($taxrules->count())
				{
					/** @var TaxRules $rule */
					foreach ($taxrules as $rule)
					{
						// Note: You can't use $rule->state since it returns the model state
						$rule_state = $rule->getFieldValue('state', null);

						if (empty($rule_state) && empty($rule->city) && $rule->taxrate && ($lastVies != $rule->vies))
						{
							$catchRules++;
							$lastVies = $rule->vies;
						}
					}
				}

				$mustCheck = ($catchRules < 2) && ($catchRules > 0);

				if ($mustCheck)
				{
					// Can I use cached result? In order to do so...
					$useCachedResult = false;

					// ...I have to be logged in...
					if (!JFactory::getUser()->guest)
					{
						// ...and I must have my viesregistered flag set to 2
						// and my VAT number must match the saved record.
						/** @var Users $subsUsersModel */
						$subsUsersModel = $this->container->factory->model('Users')->tmpInstance();

						$userparams = $subsUsersModel
							->getMergedData(JFactory::getUser()->id);

						if (($userparams->viesregistered == 2) && ($userparams->vatnumber == $state->vatnumber))
						{
							$useCachedResult = true;
						}
					}

					if ($useCachedResult)
					{
						// Use the cached VIES validation result
						$ret['vatnumber'] = true;
					}
					else
					{
						// No, check the VAT number against the VIES web service
						$ret['vatnumber'] = EUVATInfo::isVIESValidVATNumber($state->country, $state->vatnumber);
					}

					$ret['novatrequired'] = false;
				}
				else
				{
					$ret['novatrequired'] = true;
				}
			}
			elseif ($allowNonEUVAT)
			{
				// Allow non-EU VAT input
				$ret['novatrequired'] = true;
				$ret['vatnumber'] = EUVATInfo::isVIESValidVATNumber($state->country, $state->vatnumber);
			}
		}

		// 5. Coupon validation
		$ret['coupon'] = $this->_validateCoupon(!$requireCoupon);

		return (object)$ret;
	}

	/**
	 * Calculates the level's price applicable to the specific user and the
	 * actual state information
	 *
	 * @param   bool  $force  Set true to force recalculation of the price validation
	 *
	 * @return  \stdClass
	 */
	public function validatePrice($force = false)
	{
		static $result = null;

		if (is_null($result) || $force)
		{
			$state = $this->getStateVariables($force);

			// Get the subscription level
			/** @var Levels $level */
			$level = $this->container->factory->model('Levels')->tmpInstance();
			$level->find($state->id);

			// Get the user's subscription levels and calculate the signup fee
			$subIDs = array();
			$signup_fee = 0;
			$user = JFactory::getUser();

			if ($user->id)
			{
				/** @var Subscriptions $subscriptionsModel */
				$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
				$mysubs = $subscriptionsModel
					->user_id($user->id)
					->paystate('C')
					->get(true);

				if (!empty($mysubs))
				{
					foreach ($mysubs as $sub)
					{
						$subIDs[] = $sub->akeebasubs_level_id;
					}
				}

				$subIDs = array_unique($subIDs);

				if (!in_array($level->akeebasubs_level_id, $subIDs))
				{
					$signup_fee = $level->signupfee;
				}
			}
			else
			{
				$signup_fee = $level->signupfee;
			}

			// Get the default price value
			$netPrice = (float)$level->price + (float)$signup_fee;

			// Net price modifiers (via plugins)
			$price_modifier = 0;
			$this->container->platform->importPlugin('akeebasubs');
			$this->container->platform->importPlugin('akpayment');

			$priceValidationData = array_merge(
				(array)$state, array(
					'level' => $level,
					'netprice' => $netPrice
				)
			);

			$jResponse = $this->container->platform->runPlugins('onValidateSubscriptionPrice', array(
				(object)$priceValidationData)
			);

			if (is_array($jResponse) && !empty($jResponse))
			{
				foreach ($jResponse as $pluginResponse)
				{
					if (empty($pluginResponse))
					{
						continue;
					}

					$price_modifier += $pluginResponse;
				}
			}

			$netPrice += $price_modifier;

			// Coupon discount
			$validCoupon = $this->_validateCoupon(false);

			$couponDiscount = 0;

			if ($validCoupon)
			{
				/** @var Coupons $couponsModel */
				$couponsModel = $this->container->factory->model('Coupons')->tmpInstance();
				$coupon = $couponsModel
					->coupon(strtoupper($state->coupon))
					->firstOrNew();

				$this->_coupon_id = $coupon->akeebasubs_coupon_id;

				switch ($coupon->type)
				{
					case 'value':
						$couponDiscount = (float)$coupon->value;

						if ($couponDiscount > $netPrice)
						{
							$couponDiscount = $netPrice;
						}

						if ($couponDiscount <= 0)
						{
							$couponDiscount = 0;
						}
						break;

					case 'percent':
						$percent = (float)$coupon->value / 100.0;

						if ($percent <= 0)
						{
							$percent = 0;
						}

						if ($percent > 1)
						{
							$percent = 1;
						}

						$couponDiscount = $percent * $netPrice;
						break;
				}
			}
			else
			{
				$this->_coupon_id = null;
			}

			// Upgrades (auto-rule) validation
			$discountStructure = $this->_getAutoDiscount();
			$autoDiscount = $discountStructure['discount'];

			// Should I use the coupon code or the automatic discount?
			$this->_coupon_id = null;
			$useCoupon = false;
			$useAuto = true;

			if ($validCoupon)
			{
				if ($autoDiscount > $couponDiscount)
				{
					$useCoupon = false;
					$useAuto = true;
					$this->_coupon_id = null;
				}
				else
				{
					$useAuto = false;
					$useCoupon = true;
					$this->_upgrade_id = null;
				}
			}

			$discount = $useCoupon ? $couponDiscount : $autoDiscount;
			$couponid = is_null($this->_coupon_id) ? 0 : $this->_coupon_id;
			$upgradeid = is_null($this->_upgrade_id) ? 0 : $this->_upgrade_id;

			// Note: do not reset the oldsup and expiration fields. Subscription level relations must not be bound
			// to the discount.

			// Get the applicable tax rule
			$taxRule = $this->_getTaxRule();

			// Calculate the base price minimising rounding errors
			$basePrice = 0.01 * (100 * $netPrice - 100 * $discount);

			if ($basePrice < 0.01)
			{
				$basePrice = 0;
			}

			// Calculate the tax amount minimising rounding errors
			$taxAmount = 0.01 * ($taxRule->taxrate * $basePrice);

			// Calculate the gross amount minimising rounding errors
			$grossAmount = 0.01 * (100 * $basePrice + 100 * $taxAmount);

			// Calculate the recurring amount, if necessary
			$recurringAmount = 0;

			if ($level->recurring && (abs($signup_fee) >= 0.01))
			{
				$rectaxAmount = 0.01 * ($taxRule->taxrate * $level->price);
				$recurringAmount = 0.01 * (100 * $level->price + 100 * $rectaxAmount);
			}

			$result = (object)array(
				'net'        => sprintf('%1.02F', round($netPrice, 2)),
				'realnet'    => sprintf('%1.02F', round($level->price, 2)),
				'signup'     => sprintf('%1.02F', round($signup_fee, 2)),
				'discount'   => sprintf('%1.02F', round($discount, 2)),
				'taxrate'    => sprintf('%1.02F', (float)$taxRule->taxrate),
				'tax'        => sprintf('%1.02F', round($taxAmount, 2)),
				'gross'      => sprintf('%1.02F', round($grossAmount, 2)),
				'recurring'  => sprintf('%1.02F', round($recurringAmount, 2)),
				'usecoupon'  => $useCoupon ? 1 : 0,
				'useauto'    => $useAuto ? 1 : 0,
				'couponid'   => $couponid,
				'upgradeid'  => $upgradeid,
				'oldsub'     => $discountStructure['oldsub'],
				'allsubs'    => $discountStructure['allsubs'],
				'expiration' => $discountStructure['expiration'],
				'taxrule_id' => $taxRule->id,
				'tax_match'  => $taxRule->match,
				'tax_fuzzy'  => $taxRule->fuzzy,
			);
		}

		return $result;
	}

	/**
	 * Validates a coupon code, making sure it exists, it's activated, it's not expired,
	 * it applies to the specific subscription and user.
	 *
	 * @param   bool  $validIfNotExists
	 * @param   bool  $force
	 *
	 * @return bool
	 */
	private function _validateCoupon($validIfNotExists = true, $force = false)
	{
		$couponValidation = $this->getValidator('Coupon')->execute($force);

		$valid = $couponValidation['valid'];
		/** @var Coupons $coupon */
		$coupon = $couponValidation['coupon'];

		if (!$valid && $validIfNotExists && !$couponValidation['couponFound'])
		{
			$valid = true;
		}

		if (!is_null($coupon))
		{
			$this->_coupon_id = $coupon->akeebasubs_coupon_id;
		}

		return $valid;
	}

	/**
	 * Loads any relevant auto discount (upgrade rules or discount) and returns
	 * the max discount possible under those rules, as well as related
	 * information in subscription expiration and so on.
	 *
	 * @return array Discount type and value
	 */
	private function _getAutoDiscount()
	{
		// Get the automatic discount based on upgrade rules for active and expired subscriptions
		$autoDiscount = $this->_getUpgradeRule();
		$autoDiscountExpired = $this->_getUpgradeExpiredRule();

		if ($autoDiscountExpired > $autoDiscount)
		{
			$autoDiscount = $autoDiscountExpired;
			$this->_upgrade_id = $this->_expired_upgrade_id;
		}

		// Initialise the return value
		$ret = array(
			'discount'   => $autoDiscount, // discount amount
			'expiration' => 'overlap', // old subscription expiration mode
			'allsubs'    => null, // all old subscription ids
			'oldsub'     => null, // old subscription id
		);

		// Check if we have a valid subscription level and user
		if (!JFactory::getUser()->guest)
		{
			$relationData = $this->_getLevelRelation($autoDiscount);

			// Check that a relation row is relevant
			if (!is_null($relationData['relation']))
			{
				// As long as we have an expiration method other than "overlap"
				// pass along the subscriptions which will be replaced / used
				// to extend the subscription time
				if ($relationData['relation']->expiration != 'overlap')
				{
					$ret['expiration'] = $relationData['relation']->expiration;
					$ret['oldsub'] = $relationData['oldsub'];
					$ret['allsubs'] = $relationData['allsubs'];
					$this->_upgrade_id = null;
				}

				// Non-rule-based relation discount
				if ($relationData['relation']->mode != 'rules')
				{
					// Get the discount from the levels relation and make sure it's greater than the rule-based discount
					$relDiscount = $relationData['discount'];

					if ($relDiscount > $autoDiscount)
					{
						// yes, it's greated than the upgrade rule-based discount. Use it.
						$ret['discount'] = $relDiscount;
					}
				}
				// Rule-based relation discount
				else
				{
					$ret['discount'] = $autoDiscount;
				}
			}
		}

		// Finally, return the structure
		return $ret;
	}

	/**
	 * Gets the applicable subscription level relation rule applicable for this
	 * subscription attempt.
	 *
	 * @param   float  $autoDiscount  The value of an existing upgrade rule based discount already discovered
	 *
	 * @return  array  Hash array. discount is the value of the discount,
	 *                 relation is a copy of the relation row, oldsub is the id
	 *                 of the old subscription on which the relation row was
	 *                 applied against.
	 */
	private function _getLevelRelation($autoDiscount)
	{
		$state = $this->getStateVariables();

		// Initialise the return array
		$ret = array(
			'discount' => 0,
			'relation' => null,
			'oldsub'   => null,
			'allsubs'  => null,
		);

		$combinedReturn = array(
			'discount' => 0,
			'relation' => null,
			'oldsub'   => null,
			'allsubs'  => array(),
		);

		// Get applicable relation rules

		/** @var Relations $relModel */
		$relModel = $this->container->factory->model('Relations')->tmpInstance();

		$autoRules = $relModel
			->target_level_id($state->id)
			->enabled(1)
			->limit(0)
			->limitstart(0)
			->get(true);

		if (!$autoRules->count())
		{
			// No point continuing if we don't have any rules, right?
			return $ret;
		}

		// Get the current subscription level's price
		/** @var Levels $level */
		$level = $this->container->factory->model('Levels')->tmpInstance();
		$level->find($state->id);
		$net = (float)$level->price;

		/** @var Relations $rule */
		foreach ($autoRules as $rule)
		{
			// Get all of the user's paid subscriptions with an expiration date
			// in the future in the source_level_id of the rule.
			$jNow = $this->container->platform->getDate();
			$user_id = JFactory::getUser()->id;

			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

			$subscriptions = $subscriptionsModel
				->level($rule->source_level_id)
				->user_id($user_id)
				->expires_from($jNow->toSql())
				->paystate('C')
				->filter_order('publish_down')
				->filter_order('ASC')
				->get(true);

			if (!$subscriptions->count())
			{
				// If there are no subscriptions on this level don't bother.
				continue;
			}

			$allsubs = array();

			foreach ($subscriptions as $sub)
			{
				$allsubs[] = $sub->akeebasubs_level_id;
			}

			reset($allsubs);

			switch ($rule->mode)
			{
				// Rules-based discount.
				default:
				case 'rules':
					$discount = $autoDiscount;
					break;

				// Fixed discount
				case 'fixed':
					if ($rule->type == 'value')
					{
						$discount = (float)$rule->amount;
					}
					else
					{
						$discount = $net * (float)$rule->amount / 100;
					}
					break;

				// Flexible subscriptions
				case 'flexi':
					// Translate period to days
					switch ($rule->flex_uom)
					{
						default:
						case 'd':
							$modifier = 1;
							break;

						case 'w':
							$modifier = 7;
							break;

						case 'm':
							$modifier = 30;
							break;

						case 'y':
							$modifier = 365;
							break;
					}

					$modifier = $modifier * 86400; // translate to seconds

					$period = $rule->flex_period;

					// Calculate presence
					$remaining_seconds = 0;
					$now = time();

					/** @var Subscriptions $sub */
					foreach ($subscriptions as $sub)
					{
						if ($rule->flex_timecalculation && !$sub->enabled)
						{
							continue;
						}

						$from = $this->container->platform->getDate($sub->publish_up)->toUnix();
						$to = $this->container->platform->getDate($sub->publish_down)->toUnix();

						if ($from > $now)
						{
							$remaining_seconds += $to - $from;
						}
						else
						{
							$remaining_seconds += $to - $now;
						}
					}

					$remaining = $remaining_seconds / $modifier;

					// Check for low threshold
					if (($rule->low_threshold > 0) && ($remaining <= $rule->low_threshold))
					{
						$discount = $rule->low_amount;
					}
					// Check for high threshold
					elseif (($rule->high_threshold > 0) && ($remaining >= $rule->high_threshold))
					{
						$discount = $rule->high_amount;
					}
					else
					// Calculate discount based on presence
					{
						// Round the quantised presence
						switch ($rule->time_rounding)
						{
							case 'floor':
								$remaining = floor($remaining / $period);
								break;

							case 'ceil':
								$remaining = ceil($remaining / $period);
								break;

							case 'round':
								$remaining = round($remaining / $period);
								break;
						}

						$discount = $rule->flex_amount * $remaining;
					}

					// Translate percentages to net values
					if ($rule->type == 'percent')
					{
						$discount = $net * (float)$discount / 100;
					}

					break;
			}

			// Combined rule. Add to, um, the combined rules return array
			if ($rule->combine)
			{
				$combinedReturn['discount'] += $discount;
				$combinedReturn['relation'] = clone $rule;
				$combinedReturn['allsubs'] = array_merge($combinedReturn['allsubs'], $allsubs);
				$combinedReturn['allsubs'] = array_unique($combinedReturn['allsubs']);

				foreach ($subscriptions as $sub)
				{
					// Loop until we find an enabled subscription
					if (!$sub->enabled)
					{
						continue;
					}

					// Use that subscription and beat it
					$combinedReturn['oldsub'] = $sub->akeebasubs_subscription_id;
					break;
				}
			}
			elseif ($discount > $ret['discount'])
			// If the current discount is greater than what we already have, use it
			{
				$ret['discount'] = $discount;
				$ret['relation'] = clone $rule;
				$ret['allsubs'] = $allsubs;

				foreach ($subscriptions as $sub)
				{
					// Loop until we find an enabled subscription
					if (!$sub->enabled)
					{
						continue;
					}

					// Use that subscription and beat it
					$ret['oldsub'] = $sub->akeebasubs_subscription_id;
					break;
				}
			}
		}

		// Finally, check if the combined rule trumps the currently selected
		// rule. If it does, use it instead of the regular return array.
		if (
			($combinedReturn['discount'] > $ret['discount']) ||
			(($ret['discount'] <= 0.01) && !is_null($combinedReturn['relation']))
		)
		{
			$ret = $combinedReturn;
		}

		return $ret;
	}

	/**
	 * Loads any relevant upgrade rules and returns the max discount possible
	 * under those rules.
	 *
	 * @return float Discount amount
	 */
	private function _getUpgradeRule()
	{
		$state = $this->getStateVariables();

		// Check that we do have a user (if there's no logged in user, we have
		// no subscription information, ergo upgrades are not applicable!)
		$user_id = JFactory::getUser()->id;

		if (empty($user_id))
		{
			$this->_upgrade_id = null;

			return 0;
		}

		// Get applicable auto-rules
		/** @var Upgrades $upgradesModel */
		$upgradesModel = $this->container->factory->model('Upgrades')->tmpInstance();
		$autoRules = $upgradesModel
			->to_id($state->id)
			->enabled(1)
			->expired(0)
			->get(true);

		if (!$autoRules->count())
		{
			$this->_upgrade_id = null;

			return 0;
		}

		// Get the user's list of subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->enabled(1)
			->get(true);

		if (!$subscriptions->count())
		{
			$this->_upgrade_id = null;

			return 0;
		}

		$subs = array();
		$uNow = time();

		$subPayments = array();

		foreach ($subscriptions as $subscription)
		{
			$uFrom = $this->container->platform->getDate($subscription->publish_up)->toUnix();
			$presence = $uNow - $uFrom;
			$subs[$subscription->akeebasubs_level_id] = $presence;

			$uOn = $this->container->platform->getDate($subscription->created_on)->toUnix();

			if (!array_key_exists($subscription->akeebasubs_level_id, $subPayments))
			{
				$subPayments[$subscription->akeebasubs_level_id] = array(
					'value' => $subscription->net_amount,
					'on'    => $uOn,
				);
			}
			else
			{
				$oldOn = $subPayments[$subscription->akeebasubs_level_id]['on'];
				if ($oldOn < $uOn)
				{
					$subPayments[$subscription->akeebasubs_level_id] = array(
						'value' => $subscription->net_amount,
						'on'    => $uOn,
					);
				}
			}
		}

		// Get the current subscription level's price
		/** @var Levels $levelsModel */
		$levelsModel = $this->container->factory->model('Levels')->tmpInstance();
		$net = (float)$levelsModel->find($state->id)->price;

		if ($net == 0)
		{
			$this->_upgrade_id = null;

			return 0;
		}

		$discount = 0;
		$this->_upgrade_id = null;


		// Remove any rules that do not apply
		foreach ($autoRules as $i => $rule)
		{
			if (
				// Make sure there is an active subscription in the From level
				!(array_key_exists($rule->from_id, $subs))
				// Make sure the min/max presence is respected
				|| ($subs[$rule->from_id] < ($rule->min_presence * 86400))
				|| ($subs[$rule->from_id] > ($rule->max_presence * 86400))
				// If From and To levels are different, make sure there is no active subscription in the To level yet
				|| ($rule->to_id != $rule->from_id && array_key_exists($rule->to_id, $subs))
			)
			{
				unset($autoRules[$i]);
			}
		}

		// First add add all combined rules
		foreach ($autoRules as $i => $rule)
		{
			if (!$rule->combine)
			{
				continue;
			}

			switch ($rule->type)
			{
				case 'value':
					$discount += $rule->value;
					$this->_upgrade_id = $rule->akeebasubs_upgrade_id;

					break;

				default:
				case 'percent':
					$newDiscount = $net * (float)$rule->value / 100.00;
					$discount += $newDiscount;
					$this->_upgrade_id = $rule->akeebasubs_upgrade_id;

					break;

				case 'lastpercent':
					if (!array_key_exists($rule->from_id, $subPayments))
					{
						$lastNet = 0.00;
					}
					else
					{
						$lastNet = $subPayments[$rule->from_id]['value'];
					}

					$newDiscount = (float)$lastNet * (float)$rule->value / 100.00;
					$discount += $newDiscount;
					$this->_upgrade_id = $rule->akeebasubs_upgrade_id;

					break;
			}

			unset($autoRules[$i]);
		}

		// Then check all non-combined rules if they give a higher discount
		foreach ($autoRules as $rule)
		{
			if ($rule->combine)
			{
				continue;
			}

			switch ($rule->type)
			{
				case 'value':
					if ($rule->value > $discount)
					{
						$discount = $rule->value;
						$this->_upgrade_id = $rule->akeebasubs_upgrade_id;
					}

					break;

				default:
				case 'percent':
					$newDiscount = $net * (float)$rule->value / 100.00;

					if ($newDiscount > $discount)
					{
						$discount = $newDiscount;
						$this->_upgrade_id = $rule->akeebasubs_upgrade_id;
					}

					break;

				case 'lastpercent':
					if (!array_key_exists($rule->from_id, $subPayments))
					{
						$lastNet = 0.00;
					}
					else
					{
						$lastNet = $subPayments[$rule->from_id]['value'];
					}

					$newDiscount = (float)$lastNet * (float)$rule->value / 100.00;

					if ($newDiscount > $discount)
					{
						$discount = $newDiscount;
						$this->_upgrade_id = $rule->akeebasubs_upgrade_id;
					}

					break;
			}
		}

		return $discount;
	}

	/**
	 * Loads any relevant upgrade rules for expired subscriptions and returns the max discount possible
	 * under those rules.
	 *
	 * @return float Discount amount
	 */
	private function _getUpgradeExpiredRule()
	{
		$state = $this->getStateVariables();

		// Check that we do have a user (if there's no logged in user, we have
		// no subscription information, ergo upgrades are not applicable!)
		$user_id = JFactory::getUser()->id;

		if (empty($user_id))
		{
			$this->_upgrade_id = null;

			return 0;
		}

		// Get applicable auto-rules
		/** @var Upgrades $upgradesModel */
		$upgradesModel = $this->container->factory->model('Upgrades')->tmpInstance();

		$autoRules = $upgradesModel
			->to_id($state->id)
			->enabled(1)
			->expired(1)
			->get(true);

		if (!$autoRules->count())
		{
			$this->_upgrade_id = null;

			return 0;
		}

		// Get the user's list of paid but no longer active (therefore: expired) subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->enabled(0)
			->paystate('C')
			->get(true);

		if (!$subscriptions->count())
		{
			$this->_expired_upgrade_id = null;

			return 0;
		}

		$subs = array();
		$uNow = time();

		$subPayments = array();

		foreach ($subscriptions as $subscription)
		{
			$uTo = $this->container->platform->getDate($subscription->publish_down)->toUnix();
			$age = $uNow - $uTo;
			$subs[$subscription->akeebasubs_level_id] = $age;

			$uOn = $this->container->platform->getDate($subscription->created_on);

			if (!array_key_exists($subscription->akeebasubs_level_id, $subPayments))
			{
				$subPayments[$subscription->akeebasubs_level_id] = array(
					'value' => $subscription->net_amount,
					'on'    => $uOn,
				);
			}
			else
			{
				$oldOn = $subPayments[$subscription->akeebasubs_level_id]['on'];

				if ($oldOn < $uOn)
				{
					$subPayments[$subscription->akeebasubs_level_id] = array(
						'value' => $subscription->net_amount,
						'on'    => $uOn,
					);
				}
			}
		}

		// Get the current subscription level's price
		/** @var Levels $levelsModel */
		$levelsModel = $this->container->factory->model('Levels')->tmpInstance();
		$net = (float)$levelsModel->find($state->id)->price;

		if ($net == 0)
		{
			$this->_expired_upgrade_id = null;

			return 0;
		}

		$discount = 0;
		$this->_expired_upgrade_id = null;

		// Remove any rules that do not apply
		foreach ($autoRules as $i => $rule)
		{
			if (
				// Make sure there is an active subscription in the From level
				!(array_key_exists($rule->from_id, $subs))
				// Make sure the min/max presence is repected
				|| ($subs[$rule->from_id] < ($rule->min_presence * 86400))
				|| ($subs[$rule->from_id] > ($rule->max_presence * 86400))
				// If From and To levels are different, make sure there is no active subscription in the To level yet
				|| ($rule->to_id != $rule->from_id && array_key_exists($rule->to_id, $subs))
			)
			{
				unset($autoRules[$i]);
			}
		}

		// First add add all combined rules
		foreach ($autoRules as $i => $rule)
		{
			if (!$rule->combine)
			{
				continue;
			}

			switch ($rule->type)
			{
				case 'value':
					$discount += $rule->value;
					$this->_expired_upgrade_id = $rule->akeebasubs_upgrade_id;

					break;

				default:
				case 'percent':
					$newDiscount = $net * (float)$rule->value / 100.00;
					$discount += $newDiscount;
					$this->_expired_upgrade_id = $rule->akeebasubs_upgrade_id;

					break;

				case 'lastpercent':
					if (!array_key_exists($rule->from_id, $subPayments))
					{
						$lastNet = 0.00;
					}
					else
					{
						$lastNet = $subPayments[$rule->from_id]['value'];
					}

					$newDiscount = (float)$lastNet * (float)$rule->value / 100.00;
					$discount += $newDiscount;
					$this->_expired_upgrade_id = $rule->akeebasubs_upgrade_id;

					break;
			}
			unset($autoRules[$i]);
		}

		// Then check all non-combined rules if they give a higher discount
		foreach ($autoRules as $rule)
		{
			if ($rule->combine)
			{
				continue;
			}

			switch ($rule->type)
			{
				case 'value':
					if ($rule->value > $discount)
					{
						$discount = $rule->value;
						$this->_expired_upgrade_id = $rule->akeebasubs_upgrade_id;
					}
					break;

				default:
				case 'percent':
					$newDiscount = $net * (float)$rule->value / 100.00;

					if ($newDiscount > $discount)
					{
						$discount = $newDiscount;
						$this->_expired_upgrade_id = $rule->akeebasubs_upgrade_id;
					}
					break;

				case 'lastpercent':
					if (!array_key_exists($rule->from_id, $subPayments))
					{
						$lastNet = 0.00;
					}
					else
					{
						$lastNet = $subPayments[$rule->from_id]['value'];
					}

					$newDiscount = (float)$lastNet * (float)$rule->value / 100.00;

					if ($newDiscount > $discount)
					{
						$discount = $newDiscount;
						$this->_expired_upgrade_id = $rule->akeebasubs_upgrade_id;
					}
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
		$state = $this->getStateVariables();
		$isVIES = $validation->vatnumber && EUVATInfo::isEUVATCountry($state->country);

		/** @var TaxHelper $taxModel */
		$taxModel = $this->container->factory->model('TaxHelper')->tmpInstance();

		return $taxModel->getTaxRule($state->id, $state->country, $state->state, $state->city, $isVIES);
	}

	/**
	 * Checks that the current state passes the validation
	 *
	 * @return bool
	 */
	public function isValid()
	{
		// Step #1. Check the validity of the user supplied information
		// ----------------------------------------------------------------------
		$validation = $this->getValidation();
		$state = $this->getStateVariables();

		$requireCoupon = ComponentParams::getParam('reqcoupon', 0) ? true : false;

		// Iterate the core validation rules
		$isValid = true;

		foreach ($validation->validation as $key => $validData)
		{
			if (ComponentParams::getParam('personalinfo', 1) == 0)
			{
				if (!in_array($key, array('username', 'email', 'email2', 'name', 'coupon')))
				{
					continue;
				}
			}
			elseif (ComponentParams::getParam('personalinfo', 1) == -1)
			{
				if (!in_array($key, array('username', 'email', 'email2', 'name', 'country', 'coupon')))
				{
					continue;
				}
			}

			// An invalid (not VIES registered) VAT number is not a fatal error
			if ($key == 'vatnumber')
			{
				continue;
			}

			// A wrong coupon code is not a fatal error, unless we require a coupon code
			if (!$requireCoupon && ($key == 'coupon'))
			{
				continue;
			}

			// A missing business occupation is not a fatal error either
			if ($key == 'occupation')
			{
				continue;
			}

			// This is a dummy key which must be ignored
			if ($key == 'novatrequired')
			{
				continue;
			}

			$isValid = $isValid && $validData;

			if (!$isValid)
			{
				if ($key == 'username')
				{
					$user = JFactory::getUser();

					if ($user->username == $state->username)
					{
						$isValid = true;
					}
					else
					{
						break;
					}
				}

				break;
			}
		}
		// Make sure custom fields also validate
		$isValid = $isValid && $validation->custom_valid && $validation->subscription_custom_valid;

		return $isValid;
	}

	/**
	 * Updates the user info based on the state data
	 *
	 * @param   bool    $allowNewUser  When true, we can create a new user. False, only update an existing user's data.
	 * @param   Levels  $level         The subscription level object
	 *
	 * @return  bool  True on success
	 */
	public function updateUserInfo($allowNewUser = true, $level = null)
	{
		$state = $this->getStateVariables();
		$user = JFactory::getUser();
		$user = $this->getState('user', $user);

		if (($user->id == 0) && !$allowNewUser)
		{
			// New user creation is not allowed. Sorry.
			return false;
		}

		if ($user->id == 0)
		{
			// Check for an existing, blocked, unactivated user with the same
			// username or email address.
			/** @var JoomlaUsers $joomlaUsers */
			$joomlaUsers = $this->container->factory->model('JoomlaUsers')->tmpInstance();

			/** @var JoomlaUsers $user1 */
			$user1 = $joomlaUsers->getClone()->reset(true, true)
				->username($state->username)
				->block(1)
				->firstOrNew();

			/** @var JoomlaUsers $user2 */
			$user2 = $joomlaUsers->getClone()->reset(true, true)
				->email($state->email)
				->block(1)
				->firstOrNew();

			$id1 = $user1->id;
			$id2 = $user2->id;

			// Do we have a match?
			if ($id1 || $id2)
			{
				if ($id1 == $id2)
				{
					// Username and email match with the blocked user; reuse that
					// user, please.
					$user = JFactory::getUser($user1->id);
				}
				elseif ($id1 && $id2)
				{
					// We have both the same username and same email, but in two
					// different users. In order to avoid confusion we will remove
					// user 2 and change user 1's email into the email address provided

					// Remove the last subscription for $user2 (it will be an unpaid one)
					/** @var Subscriptions $subscriptionsModel */
					$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

					$substodelete = $subscriptionsModel
						->user_id($id2)
						->get(true);

					if ($substodelete->count())
					{
						/** @var Subscriptions $subtodelete */
						foreach ($substodelete as $subtodelete)
						{
							$substodelete->delete($subtodelete->akeebasubs_subscription_id);
						}
					}

					// Remove $user2 and set $user to $user1 so that it gets updated
					$jUser2 = JFactory::getUser($user2->id);
					$error = '';

					try
					{
						$jUser2->delete();
					}
					catch (\Exception $e)
					{
						$error = $e->getMessage();
					}

					// If deleting through JUser failed, try a direct deletion (may leave junk behind, e.g. in user-usergroup map table)
					if ($jUser2->getErrors() || $error)
					{
						$user2->delete($id2);
					}

					$user = JFactory::getUser($user1->id);
					$user->email = $state->email;
					$user->save(true);
				}
				elseif (!$id1 && $id2)
				{
					// We have a user with the same email, but the wrong username.
					// Use this user (the username is updated later on)
					$user = JFactory::getUser($user2->id);
				}
				elseif ($id1 && !$id2)
				{
					// We have a user with the same username, but the wrong email.
					// Use this user (the email is updated later on)
					$user = JFactory::getUser($user1->id);
				}
			}
		}

		if (is_null($user->id) || ($user->id == 0))
		{
			// CREATE A NEW USER
			$params = array(
				'name'      => $state->name,
				'username'  => $state->username,
				'email'     => $state->email,
				'password'  => $state->password,
				'password2' => $state->password2
			);

			// We have to use JUser directly instead of JFactory getUser
			$user = new \JUser(0);

			\JLoader::import('joomla.application.component.helper');
			$usersConfig = \JComponentHelper::getParams('com_users');
			$newUsertype = $usersConfig->get('new_usertype');

			// get the New User Group from com_users' settings
			if (empty($newUsertype))
			{
				$newUsertype = 2;
			}

			$params['groups'] = array($newUsertype);

			$params['sendEmail'] = 0;

			// Set the user's default language to whatever the site's current language is
			$params['params'] = array(
				'language' => JFactory::getConfig()->get('language')
			);

			// We always block the user, so that only a successful payment or
			// clicking on the email link activates his account. This is to
			// prevent spam registrations when the subscription form is abused.
			\JLoader::import('joomla.user.helper');
			\JLoader::import('cms.application.helper');
			$params['block'] = 1;

			$randomString = \JUserHelper::genRandomPassword();
			$hash = \JApplicationHelper::getHash($randomString);
			$params['activation'] = $hash;

			$user->bind($params);
			$userIsSaved = $user->save();
		}
		else
		{
			// UPDATE EXISTING USER

			// Remove unpaid subscriptions on the same level for this user
			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

			$unpaidSubs = $subscriptionsModel
				->user_id($user->id)
				->paystate('N', 'X')
				->get(true);

			if (!empty($unpaidSubs))
			{
				/** @var Subscriptions $unpaidSub */
				foreach ($unpaidSubs as $unpaidSub)
				{
					$unpaidSub->delete($unpaidSub->akeebasubs_subscription_id);
				}
			}

			// Update existing user's details
			/** @var JoomlaUsers $userRecord */
			$userRecord = $this->container->factory->model('JoomlaUsers')->tmpInstance();
			$userRecord->find($user->id);

			$updates = array(
				'name'  => $state->name,
				'email' => $state->email
			);

			if (!empty($state->password) && ($state->password == $state->password2))
			{
				\JLoader::import('joomla.user.helper');
				$salt = \JUserHelper::genRandomPassword(32);
				$pass = \JUserHelper::getCryptedPassword($state->password, $salt);
				$updates['password'] = $pass . ':' . $salt;
			}

			if (!empty($state->username))
			{
				$updates['username'] = $state->username;
			}

			$userIsSaved = $userRecord->save($updates);
		}

		// Send activation email for free subscriptions if confirmfree is enabled
		if ($user->block && ($level->price < 0.01))
		{
			$confirmfree = ComponentParams::getParam('confirmfree', 0);
			if ($confirmfree)
			{
				// Send the activation email
				if (!isset($params))
				{
					$params = array();
				}

				$this->sendActivationEmail($user, $params);
			}
		}

		if (!$userIsSaved)
		{
			$this->setState('user', null);

			return false;
		}

		$this->setState('user', $user);

		return true;
	}

	/**
	 * Saves the custom fields of a user record
	 *
	 * @return bool
	 */
	public function saveCustomFields()
	{
		$state = $this->getStateVariables();
		$validation = $this->getValidation();

		$user = JFactory::getUser();
		$user = $this->getState('user', $user);

		// Find an existing record
		/** @var Users $subsUsersModel */
		$subsUsersModel = $this->container->factory->model('Users')->tmpInstance();

		$thisUser = $subsUsersModel
			->user_id($user->id)
			->firstOrNew();
		$id = $thisUser->akeebasubs_user_id;

		$data = array(
			'akeebasubs_user_id' => $id,
			'user_id'            => $user->id,
			'isbusiness'         => $state->isbusiness ? 1 : 0,
			'businessname'       => $state->businessname,
			'occupation'         => $state->occupation,
			'vatnumber'          => $state->vatnumber,
			'viesregistered'     => $validation->validation->vatnumber,
			// @todo Ask for tax authority (does it make sense, I think it's a Greek thing only...)
			'taxauthority'       => '',
			'address1'           => $state->address1,
			'address2'           => $state->address2,
			'city'               => $state->city,
			'state'              => $state->state,
			'zip'                => $state->zip,
			'country'            => $state->country,
			'params'             => $state->custom
		);

		// Allow plugins to post-process the fields
		$this->container->platform->importPlugin('akeebasubs');
		$jResponse = $this->container->platform->runPlugins('onAKSignupUserSave', array((object)$data));

		if (is_array($jResponse) && !empty($jResponse))
		{
			foreach ($jResponse as $pResponse)
			{
				if (!is_array($pResponse))
				{
					continue;
				}

				if (empty($pResponse))
				{
					continue;
				}

				if (array_key_exists('params', $pResponse))
				{
					if (!empty($pResponse['params']))
					{
						foreach ($pResponse['params'] as $k => $v)
						{
							$data['params'][$k] = $v;
						}
					}

					unset($pResponse['params']);
				}

				$data = array_merge($data, $pResponse);
			}
		}

		try
		{
			$thisUser->save($data);

			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	/**
	 * Processes the form data and creates a new subscription
	 */
	public function createNewSubscription()
	{
		// Fetch state and validation variables
		$this->setState('opt', '');
		$state = $this->getStateVariables();
		$validation = $this->getValidation();

		// Mark this subscription attempt in the session
		JFactory::getSession()->set('apply_validation.' . $state->id, 1, 'com_akeebasubs');

		// Step #1.a. Check that the form is valid
		// ----------------------------------------------------------------------
		$isValid = $this->isValid();

		if (!$isValid)
		{
			return false;
		}

		// Step #1.b. Check that the subscription level is allowed
		// ----------------------------------------------------------------------

		// Is this actually an allowed subscription level?
		/** @var Levels $levelsModel */
		$levelsModel = $this->container->factory->model('Levels')->tmpInstance();

		$allowedLevels = $levelsModel
			->only_once(1)
			->enabled(1)
			->get(true);

		$allowed = false;

		if ($allowedLevels->count())
		{
			/** @var Levels $l */
			foreach ($allowedLevels as $l)
			{
				if ($l->akeebasubs_level_id == $state->id)
				{
					$allowed = true;
					break;
				}
			}
		}

		if (!$allowed)
		{
			return false;
		}

		// Fetch the level's object, used later on
		$level = $levelsModel->getClone()->find($state->id);

		// Step #2. Check that the payment plugin exists or return false
		// ----------------------------------------------------------------------
		/** @var PaymentMethods $paymentMethodsModel */
		$paymentMethodsModel = $this->container->factory->model('PaymentMethods')->tmpInstance();
		$plugins = $paymentMethodsModel->getPaymentPlugins();

		$found = false;
		if (!empty($plugins))
		{
			foreach ($plugins as $plugin)
			{
				if ($plugin->name == $state->paymentmethod)
				{
					$found = true;
					break;
				}
			}
		}
		if (!$found)
		{
			return false;
		}

		// Reset the session flag, so that future registrations will merge the
		// data from the database
		JFactory::getSession()->set('firstrun', true, 'com_akeebasubs');

		// Step #2.b. Apply block rules
		// ----------------------------------------------------------------------
		/** @var BlockRules $blockRulesModel */
		$blockRulesModel = $this->container->factory->model('BlockRules')->tmpInstance();

		if ($blockRulesModel->isBlocked($state))
		{
			throw new \Exception(\JText::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
		}

		// Step #3. Create or update a user record
		// ----------------------------------------------------------------------
		$user = JFactory::getUser();
		$this->setState('user', $user);
		$userIsSaved = $this->updateUserInfo(true, $level);

		if (!$userIsSaved)
		{
			return false;
		}
		else
		{
			$user = $this->getState('user', $user);
		}

		// Store the user's ID in the session
		$session = JFactory::getSession();
		$session->set('subscribes.user_id', $user->id, 'com_akeebasubs');

		// Step #4. Create or add user extra fields
		// ----------------------------------------------------------------------
		// Find an existing record
		$this->saveCustomFields();

		// Step #5. Check for existing subscription records and calculate the subscription expiration date
		// ----------------------------------------------------------------------
		// First, the question: is this level part of a group?
		$haveLevelGroup = false;

		if ($level->akeebasubs_levelgroup_id > 0)
		{
			// Is the level group published?
			/** @var LevelGroups $levelGroupModel */
			$levelGroupModel = $this->container->factory->model('LevelGroups')->tmpInstance();
			$levelGroup = $levelGroupModel->find($level->akeebasubs_levelgroup_id);

			if ($levelGroup->getId())
			{
				$haveLevelGroup = $levelGroup->enabled;
			}
		}

		if ($haveLevelGroup)
		{
			// We have a level group. Get all subscriptions for all levels in
			// the group.
			$subscriptions = array();

			/** @var Levels $levelsModel */
			$levelsModel = $this->container->factory->model('Levels')->tmpInstance();
			$levelsInGroup = $levelsModel
				->levelgroup($level->akeebasubs_levelgroup_id)
				->get(true);

			if ($levelsInGroup->count())
			{
				$groupList = [];

				foreach ($levelsInGroup as $l)
				{
					$groupList[] = $l->akeebasubs_level_id;
				}

				/** @var Subscriptions $subscriptionsModel */
				$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

				$subscriptions = $subscriptionsModel
					->user_id($user->id)
					->akeebasubs_level_id($groupList)
					->paystate('C')
					->get(true);
			}
		}
		else
		{
			// No level group found. Get subscriptions on the same level.
			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

			$subscriptions = $subscriptionsModel
				->user_id($user->id)
				->level($state->id)
				->paystate('C')
				->get(true);
		}

		$now = time();
		$mNow = $this->container->platform->getDate()->toSql();

		if (!$subscriptions->count())
		{
			$startDate = $now;
		}
		else
		{
			$startDate = $now;

			/** @var Subscriptions $row */
			foreach ($subscriptions as $row)
			{
				// Only take into account paid-for subscriptions. Note: you can't use $row->state, it returns the model state!
				if ($row->getFieldValue('state', null) != 'C')
				{
					continue;
				}

				// Calculate the expiration date
				$expiryDate = $this->container->platform->getDate($row->publish_down)->toUnix();

				// If the subscription expiration date is earlier than today, ignore it
				if ($expiryDate < $now)
				{
					continue;
				}

				// If the previous subscription's expiration date is later than the current start date,
				// update the start date to be one second after that.
				if ($expiryDate > $startDate)
				{
					$startDate = $expiryDate + 1;
				}

				// Also mark the old subscription as "communicated". We don't want
				// to spam our users with subscription renewal notices or expiration
				// notification after they have effectively renewed!
				$row->save([
					'contact_flag' => 3
				]);
			}
		}

		// Step #6. Create a new subscription record
		// ----------------------------------------------------------------------
		$nullDate = JFactory::getDbo()->getNullDate();

		/** @var Levels $level */
		$level = $this->container->factory->model('Levels')->tmpInstance();
		$level->find($state->id);

		if ($level->forever)
		{
			$jStartDate = $this->container->platform->getDate();
			$endDate = '2038-01-01 00:00:00';
		}
		elseif (!is_null($level->fixed_date) && ($level->fixed_date != $nullDate))
		{
			$jStartDate = $this->container->platform->getDate();
			$endDate = $level->fixed_date;
		}
		else
		{
			$jStartDate = $this->container->platform->getDate($startDate);

			// Subscription duration (length) modifiers, via plugins
			$duration_modifier = 0;

			$this->container->platform->importPlugin('akeebasubs');
			$jResponse = $this->container->platform->runPlugins('onValidateSubscriptionLength', array($state));

			if (is_array($jResponse) && !empty($jResponse))
			{
				foreach ($jResponse as $pluginResponse)
				{
					if (empty($pluginResponse))
					{
						continue;
					}

					$duration_modifier += $pluginResponse;
				}
			}

			// Calculate the effective duration
			$duration = (int)$level->duration + $duration_modifier;

			if ($duration <= 0)
			{
				$duration = 0;
			}

			$duration = $duration * 3600 * 24;
			$endDate = $startDate + $duration;
		}

		$mStartDate = $jStartDate->toSql();
		$mEndDate = $this->container->platform->getDate($endDate)->toSql();

		// Store the price validation's "oldsub" and "expiration" keys in
		// the subscriptions subcustom array
		$subcustom = $state->subcustom;

		if (empty($subcustom))
		{
			$subcustom = array();
		}
		elseif (is_object($subcustom))
		{
			$subcustom = (array)$subcustom;
		}

		$priceValidation = $this->validatePrice();

		$subcustom['fixdates'] = array(
			'oldsub'     => $priceValidation->oldsub,
			'allsubs'    => $priceValidation->allsubs,
			'expiration' => $priceValidation->expiration,
		);

		// Get the IP address
		$ip = Ip::getIp();

		// Get the country from the IP address if the Akeeba GeoIP Provider Plugin is installed and activated
		$ip_country = '(Unknown)';

		if (class_exists('AkeebaGeoipProvider'))
		{
			$geoip = new \AkeebaGeoipProvider();
			$ip_country = $geoip->getCountryName($ip);

			if (empty($ip_country))
			{
				$ip_country = '(Unknown)';
			}
		}

		// Setup the new subscription
		$data = array(
			'akeebasubs_subscription_id' => null,
			'user_id'                    => $user->id,
			'akeebasubs_level_id'        => $state->id,
			'publish_up'                 => $mStartDate,
			'publish_down'               => $mEndDate,
			'notes'                      => '',
			'enabled'                    => ($validation->price->gross < 0.01) ? 1 : 0,
			'processor'                  => ($validation->price->gross < 0.01) ? 'none' : $state->paymentmethod,
			'processor_key'              => ($validation->price->gross < 0.01) ? $this->_uuid(true) : '',
			'state'                      => ($validation->price->gross < 0.01) ? 'C' : 'N',
			'net_amount'                 => $validation->price->net - $validation->price->discount,
			'tax_amount'                 => $validation->price->tax,
			'gross_amount'               => $validation->price->gross,
			'recurring_amount'           => $validation->price->recurring,
			'tax_percent'                => $validation->price->taxrate,
			'created_on'                 => $mNow,
			'params'                     => $subcustom,
			'ip'                         => $ip,
			'ip_country'                 => $ip_country,
			'akeebasubs_coupon_id'       => $validation->price->couponid,
			'akeebasubs_upgrade_id'      => $validation->price->upgradeid,
			'contact_flag'               => 0,
			'prediscount_amount'         => $validation->price->net,
			'discount_amount'            => $validation->price->discount,
			'first_contact'              => '0000-00-00 00:00:00',
			'second_contact'             => '0000-00-00 00:00:00',
			'akeebasubs_affiliate_id'    => 0,
			'affiliate_comission'        => 0,
			// Flags
			'_dontCheckPaymentID'		 => true
		);

		/** @var Subscriptions $subscription */
		$subscription = $this->container->factory->model('Subscriptions')->tmpInstance();
		$this->_item = $subscription->reset(true, true)->save($data);

		// Step #7. Hit the coupon code, if a coupon is indeed used
		// ----------------------------------------------------------------------
		if ($validation->price->couponid)
		{
			/** @var Coupons $couponsModel */
			$couponsModel = $this->container->factory->model('Coupons')->tmpInstance();
			$couponsModel->find($validation->price->couponid);
			$couponsModel->hits++;
			$couponsModel->save();
		}

		// Step #8. Clear the session
		// ----------------------------------------------------------------------
		$session = JFactory::getSession();
		$session->set('apply_validation.' . $state->id, null, 'com_akeebasubs');

		// Step #9. Call the specific plugin's onAKPaymentNew() method and get the redirection URL,
		//          or redirect immediately on auto-activated subscriptions
		// ----------------------------------------------------------------------
		if ($subscription->gross_amount != 0)
		{
			// Non-zero charges; use the plugins
			$jResponse = $this->container->platform->runPlugins('onAKPaymentNew', array(
				$state->paymentmethod,
				$user,
				$level,
				$subscription
			));

			if (empty($jResponse))
			{
				return false;
			}

			foreach ($jResponse as $response)
			{
				if ($response === false)
				{
					continue;
				}

				$this->paymentForm = $response;
			}
		}
		else
		{
			// Zero charges. First apply subscription replacement
			$updates = array();
			AkpaymentBase::fixSubscriptionDates($subscription, $updates);

			if (!empty($updates))
			{
				$subscription->save($updates);
				$this->_item = $subscription;
			}

			// and then just redirect
			$app = JFactory::getApplication();
			$app->redirect(str_replace('&amp;', '&', \JRoute::_('index.php?option=com_akeebasubs&layout=default&view=message&slug=' . $level->slug . '&layout=order&subid=' . $subscription->akeebasubs_subscription_id)));

			return false;
		}

		// Return true
		// ----------------------------------------------------------------------
		return true;
	}

	public function runCancelRecurring()
	{
		$state = $this->getStateVariables();

		$rawDataPost = JRequest::get('POST', 2);
		$rawDataGet = JRequest::get('GET', 2);
		$data = array_merge($rawDataGet, $rawDataPost);

		// Some plugins result in an empty Itemid being added to the request
		// data, screwing up the payment callback validation in some cases (e.g.
		// PayPal).
		if (array_key_exists('Itemid', $data))
		{
			if (empty($data['Itemid']))
			{
				unset($data['Itemid']);
			}
		}

		/** @var PaymentMethods $paymentMethodsModel */
		$paymentMethodsModel = $this->container->factory->model('PaymentMethods')->tmpInstance();
		$paymentMethodsModel->getPaymentPlugins();

		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKPaymentCancelRecurring', array(
			$state->paymentmethod,
			$data
		));

		if (empty($jResponse))
		{
			return false;
		}

		$status = false;

		foreach ($jResponse as $response)
		{
			$status = $status || $response;
		}

		return $status;
	}

	/**
	 * Runs a payment callback
	 */
	public function runCallback()
	{
		$state = $this->getStateVariables();

		$data = $this->input->getData();

		// Some plugins result in an empty Itemid being added to the request
		// data, screwing up the payment callback validation in some cases (e.g.
		// PayPal).
		if (array_key_exists('Itemid', $data))
		{
			if (empty($data['Itemid']))
			{
				unset($data['Itemid']);
			}
		}

		/** @var PaymentMethods $paymentMethodsModel */
		$paymentMethodsModel = $this->container->factory->model('PaymentMethods')->tmpInstance();
		$paymentMethodsModel->getPaymentPlugins();

		$jResponse = $this->container->platform->runPlugins('onAKPaymentCallback', array(
			$state->paymentmethod,
			$data
		));

		if (empty($jResponse))
		{
			return false;
		}

		$status = false;

		foreach ($jResponse as $response)
		{
			$status = $status || $response;
		}

		return $status;
	}

	/**
	 * Get the form set by the active payment plugin
	 */
	public function getPaymentForm()
	{
		return $this->paymentForm;
	}

	/**
	 * Returns the state data.
	 */
	public function getData()
	{
		return $this->getStateVariables();
	}

	/**
	 * Generates a Universally Unique IDentifier, version 4.
	 *
	 * This function generates a truly random UUID.
	 *
	 * @param   boolean  $hex  If TRUE return the uuid in hex format, otherwise as a string
	 *
	 * @return  string A UUID, made up of 36 characters or 16 hex digits.
	 *
	 * @see     http://tools.ietf.org/html/rfc4122#section-4.4
	 * @see     http://en.wikipedia.org/wiki/UUID
	 */
	protected function _uuid($hex = false)
	{
		$pr_bits = false;

		if (is_resource($this->_urand))
		{
			$pr_bits .= @fread($this->_urand, 16);
		}

		if (!$pr_bits)
		{
			$fp = @fopen('/dev/urandom', 'rb');

			if ($fp !== false)
			{
				$pr_bits .= @fread($fp, 16);
				@fclose($fp);
			}
			else
			{
				// If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
				$pr_bits = "";

				for ($cnt = 0; $cnt < 16; $cnt++)
				{
					$pr_bits .= chr(mt_rand(0, 255));
				}
			}
		}

		$time_low = bin2hex(substr($pr_bits, 0, 4));
		$time_mid = bin2hex(substr($pr_bits, 4, 2));
		$time_hi_and_version = bin2hex(substr($pr_bits, 6, 2));
		$clock_seq_hi_and_reserved = bin2hex(substr($pr_bits, 8, 2));
		$node = bin2hex(substr($pr_bits, 10, 6));

		/**
		 * Set the four most significant bits (bits 12 through 15) of the
		 * time_hi_and_version field to the 4-bit version number from
		 * Section 4.1.3.
		 *
		 * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
		 */
		$time_hi_and_version = hexdec($time_hi_and_version);
		$time_hi_and_version = $time_hi_and_version >> 4;
		$time_hi_and_version = $time_hi_and_version | 0x4000;

		/**
		 * Set the two most significant bits (bits 6 and 7) of the
		 * clock_seq_hi_and_reserved to zero and one, respectively.
		 */
		$clock_seq_hi_and_reserved = hexdec($clock_seq_hi_and_reserved);
		$clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
		$clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;

		//Either return as hex or as string
		$format = $hex ? '%08s%04s%04x%04x%012s' : '%08s-%04s-%04x-%04x-%012s';

		return sprintf($format, $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node);
	}

	/**
	 * Send an activation email to the user
	 *
	 * @param   \JUser  $user
	 * @param   array   $indata
	 *
	 * @return  bool
	 *
	 * @throws  \Exception
	 */
	private function sendActivationEmail($user, array $indata = [])
	{
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$db = JFactory::getDbo();
		$params = \JComponentHelper::getParams('com_users');

		$data = array_merge((array)$user->getProperties(), $indata);

		$useractivation = $params->get('useractivation');
		$sendpassword = $params->get('sendpassword', 1);

		// Check if the user needs to activate their account.
		if (($useractivation == 1) || ($useractivation == 2))
		{
			$user->activation = \JApplicationHelper::getHash(\JUserHelper::genRandomPassword());
			$user->block = 1;
			$user->lastvisitDate = JFactory::getDbo()->getNullDate();
		}
		else
		{
			$user->block = 0;
		}

		// Load the users plugin group.
		\JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save())
		{
			return false;
		}

		// Compile the notification mail values.
		$data = $user->getProperties();
		$data['password_clear'] = $indata['password2'];
		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['siteurl'] = \JUri::root();

		// Load com_users translation files
		$jlang = JFactory::getLanguage();
		$jlang->load('com_users', JPATH_SITE, 'en-GB', true); // Load English (British)
		$jlang->load('com_users', JPATH_SITE, $jlang->getDefault(), true); // Load the site's default language
		$jlang->load('com_users', JPATH_SITE, null, true); // Load the currently selected language

		// Handle account activation/confirmation emails.
		if ($useractivation == 2)
		{
			$uri = \JURI::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . \JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = \JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = \JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username'],
					$data['password_clear']
				);
			}
			else
			{
				$emailBody = \JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY_NOPW',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username']
				);
			}
		}
		elseif ($useractivation == 1)
		{
			// Set the link to activate the user account.
			$uri = \JUri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . \JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = \JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = \JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username'],
					$data['password_clear']
				);
			}
			else
			{
				$emailBody = \JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ACTIVATION_BODY_NOPW',
					$data['name'],
					$data['sitename'],
					$data['activate'],
					$data['siteurl'],
					$data['username']
				);
			}
		}
		else
		{

			$emailSubject = \JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = \JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_BODY',
					$data['name'],
					$data['sitename'],
					$data['siteurl'],
					$data['username'],
					$data['password_clear']
				);
			}
			else
			{
				$emailBody = \JText::sprintf(
					'COM_USERS_EMAIL_REGISTERED_BODY_NOPW',
					$data['name'],
					$data['sitename'],
					$data['siteurl']
				);
			}
		}

		// Send the registration email.
		$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $data['email'], $emailSubject, $emailBody);

		//Send Notification mail to administrators
		if (($params->get('useractivation') < 2) && ($params->get('mail_to_admin') == 1))
		{
			$emailSubject = \JText::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$data['name'],
				$data['sitename']
			);

			$emailBodyAdmin = \JText::sprintf(
				'COM_USERS_EMAIL_REGISTERED_NOTIFICATION_TO_ADMIN_BODY',
				$data['name'],
				$data['username'],
				$data['siteurl']
			);

			// get all admin users
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('name', 'email', 'sendEmail', 'id')))
				->from($db->quoteName('#__users'))
				->where($db->quoteName('sendEmail') . ' = ' . 1);

			$db->setQuery($query);

			try
			{
				$rows = $db->loadObjectList();
			}
			catch (\RuntimeException $e)
			{
				return false;
			}

			// Send mail to all superadministrators id
			foreach ($rows as $row)
			{
				$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $row->email, $emailSubject, $emailBodyAdmin);
			}
		}

		return $return;
	}
}