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

/**
 * The model for the subscription records
 *
 * Type hints for fields:
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
 * Type hints for relations:
 *
 * @property-read  Users	user		The subscription user
 * @property-read  Levels	level		The subscription level
 * @property-read  Coupons	coupon		The coupon code used (if akeebasubs_coupon_id is not empty)
 * @property-read  Upgrades	upgrade		The upgrade rule used (if akeebasubs_upgrade_id is not empty)
 * @property-read  Invoices invoice		The invoice issues (if akeebasubs_invoice_id is not empty)
 */
class Subscriptions extends DataModel
{
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

	// TODO Implement this model
}