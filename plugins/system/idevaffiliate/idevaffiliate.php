<?php
/**
 * @package		akeebasubs
 * @copyright		Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

// PHP version check
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}
// Old PHP version detected. EJECT! EJECT! EJECT!
if(!version_compare($version, '5.3.0', '>=')) return;

// Make sure FOF is loaded, otherwise do not run
if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
}
if(!defined('FOF_INCLUDED') || !class_exists('FOFLess', true))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_akeebasubs', true)) return;

class plgSystemIdevaffiliate extends JPlugin
{
	
	/**
	 * Sets the affiliate ID in the session, if the ID is defined in the request.
	 */
	function onAfterInitialise()
	{
		if($this->isTrackingRelevant()) {
			$session = JFactory::getSession();
			$affiliateId = $session->get('idevaffiliate.idev_id', 0, 'com_akeebasubs');
			if(! $affiliateId) {
				$affiliateId = JRequest::getVar('idev_id', 0);
				if($affiliateId) {
					$session->set('idevaffiliate.idev_id', $affiliateId, 'com_akeebasubs');
				}
			}
		}
	}
	
	/**
	 * Sets the affiliate ID in the session, if an ID is already stored for the userparams.
	 */
	public function onSubscriptionFormRender($userparams, $cache)
	{
		if($this->isTrackingRelevant()) {
			$recurring = $this->params->get('recurring', 0);
			if($recurring) {
				if(is_object($userparams->params) && property_exists($userparams->params, 'idev_affiliate')) {
					$affiliateId = $userparams->params->idev_affiliate;
					$session = JFactory::getSession();
					$session->set('idevaffiliate.idev_id', $affiliateId, 'com_akeebasubs');
				}
			}
		}
	}
	
	/**
	 * Returns the affiliate ID to be stored in the userparams.
	 */
	public function onAKSignupUserSave($userData)
	{
		if($this->isTrackingRelevant()) {
			$session = JFactory::getSession();
			$affiliateId = $session->get('idevaffiliate.idev_id', 0, 'com_akeebasubs');
			if($affiliateId) {
				return array(
					'params' => array('idev_affiliate' => $affiliateId)
				);
			}
		}
	}
	
	/**
	 * Adds the code that tracks the sales.
	 */
	public function onOrderMessage($subscription)
	{
		// Do not track if there is no subscription ID
		if($subscription->akeebasubs_subscription_id <= 0) {
			return;
		}
		
		// If the Affiliate ID is set to -1 do not issue a second sale 
		if($subscription->akeebasubs_affiliate_id < 0) {
			return;
		}
		
		if($this->isTrackingRelevant()) {
			// Build the tracking URL
			$price = $subscription->gross_amount;
			$orderId = $subscription->akeebasubs_subscription_id;
			$idevUrl = rtrim(trim($this->params->get('url', '')), '/') .
					'/sale.php?profile=72198&idev_saleamt=' . $price .
					'&idev_ordernum=' . $orderId;
			$session = JFactory::getSession();
			$affiliateId = $session->get('idevaffiliate.idev_id', 0, 'com_akeebasubs');
			if($affiliateId) {
				$idevUrl .= '&affiliate_id=' . $affiliateId;
				$session->clear('idevaffiliate.idev_id', 'com_akeebasubs');
			}
			
			// Track the sale by using the "Generic Tracking Pixel"
			$trackingPixel = '<img border="0" src="' . $idevUrl . '" width="1" height="1">';
			
			// Update the subscription record
			$updates = array(
				'akeebasubs_affiliate_id'	=> -1
			);
			$subscription->save($updates);
			
			return $trackingPixel;
		}
	}
	
	/**
	 * Is it neccessary to add the tracking information in this context?
	 */
	private function isTrackingRelevant()
	{
		// only run in the front-end of the site
		if( !JFactory::getApplication()->isAdmin() ) {
			// only run in HTML context
			$document = JFactory::getDocument();
			if($document instanceof JDocumentHTML) {
				return true;
			}
		}
		return false;
	}
}