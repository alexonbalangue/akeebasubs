<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentAlphaUserPoints extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'alphauserpoints',
			'ppKey'			=> 'PLG_AKPAYMENT_ALPHAUSERPOINTS_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/alphauserpoints.png'
		));

		parent::__construct($subject, $config);
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 *
	 * @param string $paymentmethod
	 * @param JUser $user
	 * @param AkeebasubsTableLevel $level
	 * @param AkeebasubsTableSubscription $subscription
	 * @return string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;

		$data = (object)array(
			'url'	=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=alphauserpoints',
			'sid'	=> $subscription->akeebasubs_subscription_id,
			'uid'	=> $user->id
		);

		@ob_start();
		include dirname(__FILE__).'/alphauserpoints/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;

		// Load the relevant subscription row
		$id = $data['SID'];
		$subscription = null;
		if($id > 0) {
			$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->setId($id)
				->getItem();
			if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
				$subscription = null;
				$isValid = false;
			} else {
				$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
					->setId($subscription->akeebasubs_level_id)
					->getItem();
			}
		} else {
			$isValid = false;
		}
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'The subscription id is invalid';

		// At this point the payment should be new (N)
		if($isValid && $subscription->state != 'N') {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "Invalid subscription state";
		}

		// Check user
		$currentUser = JFactory::getUser();
		if($isValid && $currentUser->id != $data['UID']) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "Invalid user";
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		$errorUrl = 'index.php?option='.JRequest::getCmd('option').
			'&view=level&slug='.$level->slug.
			'&layout='.JRequest::getCmd('layout','default');
		$errorUrl = JRoute::_($errorUrl,false);
		if(!$isValid) {
			JFactory::getApplication()->redirect($errorUrl, $data['error_description'], 'error');
			return false;
		}

		// Do the payment
		$exchangeRate = trim($this->params->get('rate', 1));
		if(! is_numeric($exchangeRate)) {
			$exchangeRate = 1;
		}
		// Apply the exchange rate
		$priceInPoints = round(($subscription->gross_amount / $exchangeRate), 2);
		$errorMessage = '';
		if($this->getAUPPoints($currentUser->id) < $priceInPoints) {
			$errorMessage = JText::_('PLG_AKPAYMENT_ALPHAUSERPOINTS_MSG_NOT_ENOUGH_POINTS');
			$newStatus = 'X';
		} else {
			$description = strtoupper($level->slug) . ' #' . $subscription->akeebasubs_subscription_id;
			$this->bookAUPPoints($currentUser->id, -$priceInPoints, $description);
			$newStatus = 'C';
		}

		// Update subscription status (this also automatically calls the plugins)
		$processorKey = md5($currentUser->id . ':' . $subscription->akeebasubs_subscription_id . ':' . time());
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $processorKey,
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));

		// Redirect the user to the "thank you" page or show error message
		if(empty($errorMessage)) {
			$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$level->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
			JFactory::getApplication()->redirect($thankyouUrl);
		} else {
			JFactory::getApplication()->redirect($errorUrl, $errorMessage, 'error');
		}

		return true;
	}

	private function getAUPPoints($userId)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select($db->qn('points'))
			->from($db->qn('#__alpha_userpoints'))
			->where($db->qn('userid') . ' = ' . $db->q($userId));
		$db->setQuery($query);
		$points = $db->loadResult();
		return $points;
	}

	private function bookAUPPoints($userId, $points, $description)
	{
		$db = JFactory::getDBO();
		$now = strftime("%Y-%m-%d %H:%M:%S");

		// Get user info
		$getUserInfoQuery = $db->getQuery(true)
			->select($db->qn('referreid'))
			->select($db->qn('points'))
			->from($db->qn('#__alpha_userpoints'))
			->where($db->qn('userid') . ' = ' . $db->q($userId));
		$db->setQuery($getUserInfoQuery);
		$userInfo = $db->loadObject();
		$referreId = $userInfo->referreid;
		$currentPoints = $userInfo->points;

		// Update user points
		$newPoints = $currentPoints + $points;
		$setUserPointsQuery = $db->getQuery(true)
			->update($db->qn('#__alpha_userpoints'))
			->set($db->qn('points') . ' = ' . $db->q($newPoints))
			->set($db->qn('last_update') . ' = ' . $db->q($now))
			->where($db->qn('userid')  .' = '. $db->q($userId));
		$db->setQuery($setUserPointsQuery);
		$db->execute();

		// Add details
		$addDetailsQuery = $db->getQuery(true)
			->insert($db->qn('#__alpha_userpoints_details'))
			->columns(array(
				$db->qn('referreid'),
				$db->qn('points'),
				$db->qn('insert_date'),
				$db->qn('status'),
				$db->qn('rule'),
				$db->qn('approved'),
				$db->qn('datareference')
			))
			->values($db->q($referreId) . ', '
					. $db->q($points) . ', '
					. $db->q($now) . ', '
					. $db->q('1') . ', '
					. $db->q('1') . ', '
					. $db->q('1') . ', '
					. $db->q($description)
			);
		$db->setQuery($addDetailsQuery);
		$db->execute();
	}
}