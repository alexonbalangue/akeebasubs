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

class plgSystemPostaffiliatepro extends JPlugin
{
	/**
	 * Adds the javascript file of the PAP installation.
	 */
	public function __construct(& $subject, $config = array())
	{
		parent::__construct($subject, $config);
		
		if($this->isTrackingCodeRelevant()) {
			$papUrl = rtrim(trim($this->params->get('url', '')), '/');
			$document = JFactory::getDocument();
			$document->addCustomTag('<script id="pap_x2s6df8d" src="' . $papUrl . '/scripts/trackjs.js" type="text/javascript"></script>');
		}
	}
	
	/**
	 * Adds the javascript code that tracks the clicks (referrals).
	 */
	public function onAfterDispatch()
	{
		if($this->isTrackingCodeRelevant()) {
			$document = JFactory::getDocument();
			$document->addCustomTag(
  '<script type="text/javascript">
  <!--
  PostAffTracker.setAccountId(\'default1\');
  try {
  PostAffTracker.track();
  } catch (err) { }
  //-->
  </script>');
		}
	}
	
	/**
	 * Adds the javascript code that tracks the sales.
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
		
		if($this->isTrackingCodeRelevant()) {
			// Load the subscription level
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getItem($subscription->akeebasubs_level_id);
			
			// Set up the sale
			$price = $subscription->gross_amount;
			$orderId = $subscription->akeebasubs_subscription_id;
			$productTitle = $level->title;
			
			$document = JFactory::getDocument();
			$document->addCustomTag(
'<script type="text/javascript">
  PostAffTracker.setAccountId(\'default1\');
  var sale = PostAffTracker.createSale();
  sale.setTotalCost(\'' . $price . '\');
  sale.setOrderID(\'' . $orderId . '\');
  sale.setProductID(\'' . $productTitle . '\');
  PostAffTracker.register();
  </script>');
			
			// Update the subscription record
			$updates = array(
				'akeebasubs_affiliate_id'	=> -1
			);
			$subscription->save($updates);
		}
	}
	
	/**
	 * Is it neccessary to add the tracking code in this context?
	 */
	private function isTrackingCodeRelevant()
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