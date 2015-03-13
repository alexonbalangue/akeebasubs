<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Subscriptions;

require_once __DIR__ . '/../subscriptionemails/subscriptionemails.php';

/**
 * Logs debugging information for sent out emails
 */
class plgAkeebasubsSubscriptionemailsdebug extends plgAkeebasubsSubscriptionemails
{
	/**
	 * Sends out the email to the owner of the subscription.
	 *
	 * @param   Subscriptions  $row   The subscription row object
	 * @param   string  	   $type  The type of the email to send (generic, new,)
	 * @param   array          $info  Subscription modification information (used in children classes)
	 *
	 * @return bool
	 */
	protected function sendEmail($row, $type = '', array $info = [])
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