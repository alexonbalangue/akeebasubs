<?php
/**
 * @package        Akeeba Subscriptions
 * @copyright      2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Akeeba\Subscriptions\Site\View\Levels;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;
use Akeeba\Subscriptions\Site\Model\TaxHelper;

class Html extends \FOF30\View\DataView\Html
{
	/**
	 * List of subscription IDs of the current user
	 *
	 * @var  int[]
	 */
	public $subIDs = [];

	/**
	 * Should I include VAT in the front-end display?
	 *
	 * @var  bool
	 */
	public $showVat = false;

	/**
	 * The tax helper model
	 *
	 * @var  TaxHelper
	 */
	public $taxModel;

	/**
	 * Tax defining parameters, fetched from the tax helper model
	 *
	 * @var  array
	 */
	public $taxParams = [];

	/**
	 * Should I include sign-up fees in the displayed prices?
	 *
	 * @var  int
	 */
	public $includeSignup = 0;

	/**
	 * Should I include discounts in the displayed prices?
	 *
	 * @var  bool
	 */
	public $includeDiscount = false;

	/**
	 * Should I render prices of 0 as "FREE"?
	 *
	 * @var  bool
	 */
	public $renderAsFree = false;

	/**
	 * Cache of pricing information per subscription level, required to cut down on queries in the Strappy layout.
	 *
	 * @var  object[]
	 */
	protected $pricingInformationCache = [];

	/**
	 * Executes before rendering the page for the Browse task.
	 */
	protected function onBeforeBrowse()
	{
		// Cache subscription IDs of this user
		$subIDs = array();
		$user = \JFactory::getUser();

		if ($user->id)
		{
			/** @var Subscriptions $mysubs */
			$mysubs = $this->container->factory->model('Subscriptions')->savestate(0)->setIgnoreRequest(true);
			$mysubs
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
		}

		$this->subIDs = $subIDs;

		// Should I show VAT?
		$this->showVat = ComponentParams::getParam('showvat', 0);

		// Cache tax parameters
		/** @var \Akeeba\Subscriptions\Site\Model\TaxHelper $taxModel */
		$this->taxModel = $this->getContainer()->factory->model('TaxHelper')->savestate(0)->setIgnoreRequest(1);
		$this->taxParams = $this->taxModel->getTaxDefiningParameters();

		// Should I include sign-up one time fees?
		$this->includeSignup = ComponentParams::getParam('includesignup', 2);

		// Should I include discounts? (only valid if it's a logged in user)
		$this->includeDiscount = false;

		if (!$user->guest)
		{
			$this->includeDiscount = ComponentParams::getParam('includediscount', 0);
		}

		// Should I render zero prices as "FREE"?
		$this->renderAsFree = ComponentParams::getParam('renderasfree', 0);

		parent::onBeforeBrowse();
	}

	/**
	 * Returns the pricing information for a subscription level. Used by the view templates to avoid code duplication.
	 *
	 * @param   \Akeeba\Subscriptions\Site\Model\Levels  $level  The subscription level
	 *
	 * @return  object
	 */
	public function getLevelPriceInformation(Levels $level)
	{
		$levelKey = $level->getId() . '-' . $level->slug;

		if (isset($this->pricingInformationCache[$levelKey]))
		{
			return $this->pricingInformationCache[$levelKey];
		}

		$signupFee = 0;
		$discount = 0;
		$levelPrice = $level->price;
		$vatMultiplier = 1.0;
		$vatRule = (object)[
			'match'   => 0,    // How many parameters matched exactly
			'fuzzy'   => 0,    // How many parameters matched fuzzily
			'taxrate' => 0, // Tax rate in percentage points (e.g. 12.3 means 12.3% tax)
			'id'      => 0, // The ID of the tax rule in effect
		];

		if (!in_array($level->akeebasubs_level_id, $this->subIDs) && ($this->includeSignup != 0))
		{
			$signupFee = (float)$level->signupfee;
		}

		if ($this->showVat)
		{
			$vatRule = $this->taxModel->getTaxRule(
				$level->akeebasubs_level_id, $this->taxParams['country'], $this->taxParams['state'],
				$this->taxParams['city'], $this->taxParams['vies']
			);

			$vatMultiplier = (100 + (float)$vatRule->taxrate) / 100;
		}

		if ($this->includeDiscount)
		{
			/** @var \Akeeba\Subscriptions\Site\Model\Subscribe $subscribeModel */
			$subscribeModel = $this->getContainer()->factory->model('Subscribe')->savestate(0);
			$subscribeModel->setState('id', $level->akeebasubs_level_id);
			$subValidation = $subscribeModel->validatePrice(true);
			$discount = $subValidation->discount;
			$levelPrice = $level->price - $discount;
		}

		if ($this->includeSignup == 1)
		{
			if (($levelPrice + $signupFee) < 0)
			{
				$levelPrice = -$signupFee;
			}

			$formattedPrice = sprintf('%1.02F', ($levelPrice + $signupFee) * $vatMultiplier);
			$levelPrice += $signupFee;
		}
		else
		{
			if ($levelPrice < 0)
			{
				$levelPrice = 0;
			}

			$formattedPrice = sprintf('%1.02F', ($levelPrice) * $vatMultiplier);
		}

		$dotpos = strpos($formattedPrice, '.');
		$price_integer = substr($formattedPrice, 0, $dotpos);
		$price_fractional = substr($formattedPrice, $dotpos + 1);

		$formattedPriceSU = sprintf('%1.02F', $signupFee * $vatMultiplier);
		$dotposSU = strpos($formattedPriceSU, '.');
		$price_integerSU = substr($formattedPriceSU, 0, $dotposSU);
		$price_fractionalSU = substr($formattedPriceSU, $dotposSU + 1);

		$formattedPriceD = sprintf('%1.02F', $discount);
		$dotposD = strpos($formattedPriceD, '.');
		$price_integerD = substr($formattedPriceD, 0, $dotposD);
		$price_fractionalD = substr($formattedPriceD, $dotposD + 1);

		$this->pricingInformationCache[$levelKey] = (object)[
			'vatRule'              => $vatRule,
			'signupFee'            => $signupFee,
			'discount'             => $discount,
			'discountFormatted'    => $formattedPriceD,
			'discountInteger'      => $price_integerD,
			'discountFractional'   => $price_fractionalD,
			'vatMultiplier'        => $vatMultiplier,
			'levelPrice'           => $levelPrice,
			'formattedPrice'       => $formattedPrice,
			'priceInteger'         => $price_integer,
			'priceFractional'      => $price_fractional,
			'formattedPriceSignup' => $formattedPriceSU,
			'signupInteger'        => $price_integerSU,
			'signupFractional'     => $price_fractionalSU,
		];

		return $this->pricingInformationCache[$levelKey];
	}
}