<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsSubscriptionemailsdebug extends JPlugin
{
	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		// No payment has been made yet; do not contact the user
		if($row->state == 'N') return;

		// Did the payment status just change to C or P? It's a new subscription
		if(array_key_exists('state', (array)$info['modified']) && in_array($row->state, array('P','C'))) {
			if($row->enabled) {
				if(is_object($info['previous']) && $info['previous']->state == 'P') {
					// A pending subscription just got paid
					$this->sendEmail($row, 'paid', $info);
				} else {
					// A new subscription just got paid; send new subscription notification
					$this->sendEmail($row, 'new_active', $info);
				}
			} elseif($row->state == 'C') {
				if($row->contact_flag <= 2) {
					// A new subscription which is for a renewal (will be active in a future date)
					$this->sendEmail($row, 'new_renewal', $info);
				}
			} else {
				// A new subscription which is pending payment by the processor
				$this->sendEmail($row, 'new_pending', $info);
			}
		} elseif(array_key_exists('state', (array)$info['modified']) && ($row->state == 'X')) {
			// The payment just got refused
			if(!is_object($info['previous']) || $info['previous']->state == 'N') {
				// A new subscription which could not be paid
				$this->sendEmail($row, 'cancelled_new', $info);
			} else {
				// A pending or paid subscription which was cancelled/refunded/whatever
				$this->sendEmail($row, 'cancelled_existing', $info);
			}
		} elseif($info['status'] == 'modified') {
			// If the subscription got disabled and contact_flag is 3, do not send out
			// an expiration notification. The flag is set to 3 only when a user has
			// already renewed his subscription.
			if(array_key_exists('enabled', (array)$info['modified']) && !$row->enabled && ($row->contact_flag == 3)) {
				return;
			} elseif(array_key_exists('enabled', (array)$info['modified']) && !$row->enabled) {
				// Disabled subscription, suppose expired
				if(($row->state == 'C')) $this->sendEmail($row, 'expired', $info);
			} elseif(array_key_exists('enabled', (array)$info['modified']) && $row->enabled) {
				// Subscriptions just enabled, suppose date triggered
				if(($row->state == 'C')) $this->sendEmail($row, 'published', $info);
			} elseif(array_key_exists('contact_flag', (array)$info['modified']) ) {
				// Only contact_flag change; ignore
				return;
			} else {
				// All other cases: generic email
				$this->sendEmail($row, 'generic', $info);
			}
		}
	}

	/**
	 * Sends out the email to the owner of the subscription.
	 *
	 * @param   $row   AkeebasubsTableSubscription  The subscription row object
	 * @param   $type  string                       The type of the email to send (generic, new,)
	 * @param   $info  array                        Subscription modification information
	 */
	private function sendEmail($row, $type = '', $info = array())
	{
		$timestamp = date('y-m-d H:i:s');
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50);
		$backtrace = print_r($backtrace, true);

		$modified = print_r((array)$info['modified'], true);

		$message = <<<ENDMESSAGE
================================================================================
$timestamp #{$row->akeebasubs_subscription_id} $type

Debug backtrace
------------------------------
$backtrace

Changed fields
------------------------------
$modified

ENDMESSAGE;

		$log = JFactory::getConfig()->get('log_path', JPATH_ROOT . '/logs');

		$fp = fopen($log . '/akeebasubs_emails.txt', 'at');
		fwrite($fp, $message);
		fclose($fp);

		return true;
	}
}