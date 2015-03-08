<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;
use JDate;
use JLoader;

/**
 * The model for the subscription records
 *
 * Fields:
 *
 * @property  int			$akeebasubs_subscription_id	Primary key
 * @property  int			$user_id					User ID. FK to user relation.
 * @property  int			$akeebasubs_level_id		Subscription level. FK to level relation.
 * @property  string		$publish_up					Valid from date/time
 * @property  string		$publish_down				Valid to date/time
 * @property  string		$notes						Notes, displayed in admin only
 * @property  int			$enabled					Is this subscription active?
 * @property  string		$processor					Payments processor
 * @property  string		$processor_key				Unique key for the payments processor
 * @property  string		$state						Payment state (N new, P pending, C completed, X cancelled)
 * @property  float			$net_amount					Payable amount without tax
 * @property  float			$tax_amount					Tax portion of payable amount
 * @property  float			$gross_amount				Total payable amount
 * @property  float			$recurring_amount			Total payable amount for further recurring subscriptions
 * @property  float			$tax_percent				% of tax (tax_amount / net_amount)
 * @property  string		$created_on					Date/time when this subscription was created
 * @property  \stdClass		$params						Parameters, used by custom fields and plugins
 * @property  string		$ip							IP address of the user who created this subscription
 * @property  string		$ip_country					Country of the user who created this subscription, based on IP geolocation
 * @property  int			$akeebasubs_coupon_id		Coupon code used. FK to coupon relation.
 * @property  int			$akeebasubs_upgrade_id		Upgrade rule used. FK to upgrade relation
 * @property  int			$akeebasubs_affiliate_id	NO LONGER USED IN CORE. Store an affiliate ID (plugin specific)
 * @property  float			$affiliate_comission		NO LONGER USED IN CORE. Store the commission amount of you affiliate (plugin specific)
 * @property  int			$akeebasubs_invoice_id		Invoice issues. FK to invoice relation.
 * @property  float			$prediscount_amount			Total amount, before taxes and before any discount was applied.
 * @property  float			$discount_amount			Discount amount (before taxes), applied over the prediscount_amount
 * @property  int			$contact_flag				Which expiration emails we've sent. 0 none, 1 first, 2 second, 3 after expiration
 * @property  string		$first_contact				Date/time of first expiration notification email sent.
 * @property  string		$second_contact				Date/time of second expiration notification email sent.
 * @property  string		$after_contact				Date/time of post-expiration notification email sent.
 *
 * Filters / state:
 *
 * @property  bool          $_noemail                   Do not send email on save when true (resets after successful save)
 *
 * Relations:
 *
 * @property-read  Users	 $user		The subscription user
 * @property-read  Levels	 $level		The subscription level
 * @property-read  Coupons	 $coupon	The coupon code used (if akeebasubs_coupon_id is not empty)
 * @property-read  Upgrades	 $upgrade	The upgrade rule used (if akeebasubs_upgrade_id is not empty)
 * @property-read  Invoices  $invoice	The invoice issues (if akeebasubs_invoice_id is not empty)
 */
class Subscriptions extends DataModel
{
	use Mixin\JsonData;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Add the filtering behaviour
		$this->addBehaviour('Filters');

		// Set up relations
		$this->hasOne('user', 'Users');
		$this->hasOne('level', 'Levels');
		$this->hasOne('coupon', 'Coupons');
		$this->hasOne('upgrade', 'Upgrades');
		$this->hasOne('invoice', 'Invoices', 'akeebasubs_subscription_id', 'akeebasubs_subscription_id');
	}

	/**
	 * Automatically process a list of subscriptions loaded off the database
	 *
	 * @param   Subscriptions[]  $resultArray  The list of loaded subscriptions to process
	 */
	protected function onAfterGetItemsArray(array &$resultArray)
	{
		// Implement the subscription automatic expiration
		if (empty($resultArray))
		{
			return;
		}

		if ($this->getState('skipOnProcessList', 0))
		{
			return;
		}

		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();

		$k     = $this->getKeyName();

		foreach ($resultArray as $index => &$row)
		{
			if (!property_exists($row, 'params'))
			{
				continue;
			}

			// TODO: This should no longer be necessary
			if (!is_array($row->params))
			{
				if (!empty($row->params))
				{
					$row->params = json_decode($row->params, true);
				}
			}
			if (is_null($row->params) || empty($row->params))
			{
				$row->params = array();
			}

			$triggered = false;

			if (($row->getFieldValue('state', 'N') != 'C') && $row->enabled)
			{
				$row->enabled = false;
				$row->save();

				continue;
			}

			if ($row->publish_down && ($row->publish_down != '0000-00-00 00:00:00'))
			{
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

				if (!preg_match($regex, $row->publish_down))
				{
					$row->publish_down = '2037-01-01';
				}

				if (!preg_match($regex, $row->publish_up))
				{
					$row->publish_up = '2001-01-01';
				}

				$jDown = new JDate($row->publish_down);
				$jUp   = new JDate($row->publish_up);

				if (($uNow >= $jDown->toUnix()) && $row->enabled)
				{
					$row->enabled = 0;
					$triggered    = true;
				}
				elseif (($uNow >= $jUp->toUnix()) && !$row->enabled && ($uNow < $jDown->toUnix()))
				{
					$row->enabled = 1;
					$triggered    = true;
				}
			}

			if ($triggered)
			{
				$row->save();
			}
		}
	}

	/**
	 * Handle the _noemail flag, used to avoid sending emails after we modify a subscriptions
	 *
	 * @param   array  $data
	 */
	protected function onBeforeBind(&$data)
	{
		if (!is_array($data))
		{
			return;
		}

		if (isset($data['_noemail']))
		{
			$this->setState('_noemail', $data['_noemail']);
			unset($data['_noemail']);
		}
	}

	/**
	 * Reset the _noemail flag after save
	 */
	protected function onAfterSave()
	{
		$this->setState('_noemail', false);
	}

	protected function getParamsAttribute($value)
	{
		return $this->getAttributeForJson($value);
	}

	protected function setParamsAttribute($value)
	{
		return $this->setAttributeForJson($value);
	}
}