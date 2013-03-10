<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
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

class plgccInvoicetagsAkeebasubs extends JPlugin
{
	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
		
		// Load the language files and their overrides
		$jlang = JFactory::getLanguage();
		// -- English (default fallback)
		$jlang->load('plg_ccinvoicetags_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_ccinvoicetags_akeebasubs.override', JPATH_ADMINISTRATOR, 'en-GB', true);
		// -- Default site language
		$jlang->load('plg_ccinvoicetags_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_ccinvoicetags_akeebasubs.override', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		// -- Current site language
		$jlang->load('plg_ccinvoicetags_akeebasubs', JPATH_ADMINISTRATOR, null, true);
		$jlang->load('plg_ccinvoicetags_akeebasubs.override', JPATH_ADMINISTRATOR, null, true);
	}
	
	
	public function _getCustomTags($val)
	{
		include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';
		if(!class_exists('AkeebasubsHelperSelect')) {
			return;
		}
		
		include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		if(!class_exists('AkeebasubsHelperCparams')) {
			return;
		}
		
		$db = JFactory::getDbo();
		
		// Fetch the invoice ID
		$invoice_id = $val['id'];
		
		// Load the relevant subscription
		$query = $db->getQuery(true)
			->select($db->qn('akeebasubs_subscription_id'))
			->from($db->qn('#__akeebasubs_invoices'))
			->where($db->qn('invoice_no').' = '.$db->q($invoice_id))
			->where($db->qn('extension').' = '.$db->q('ccinvoices'));
		$db->setQuery($query, 0, 1);
		$subscriptionID = $db->loadResult();
		
		if(is_null($subscriptionID) || ($subscriptionID <= 0)) {
			return;
		}
		
		$sub = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
			->getItem($subscriptionID);

		if($sub->akeebasubs_subscription_id != $subscriptionID) {
			return;
		}

		// Initialise return array
		$ret = array();
		
		// =====================================================================
		// USER DATA
		// =====================================================================
		
		// Load merged user data
		$user = JFactory::getUser($sub->user_id);
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($sub->user_id)
			->getFirstItem();
		$userdata = array_merge((array)$user, (array)($kuser->getData()));
		
		// Add basic user data
		foreach($userdata as $k => $v) {
			if(is_object($v) || is_array($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = 'asuser_'.  strtolower($k);
			$ret[$tag] = $v;
		}
		
		// Format country and state
		$ret['asuser_country'] = AkeebasubsHelperSelect::decodeCountry($userdata['country']);
		if(!empty($userdata['state'])) {
			$ret['asuser_state'] = AkeebasubsHelperSelect::formatState($userdata['state']);
		}
		
		// Blank out the company if it's not a business registration
		if(!$userdata['isbusiness']) {
			$ret['asuser_businessname'] = '';
		}
		
		// Add custom fields data. Format {asuser_custom_variablename}
		if(array_key_exists('params', $userdata)) {
			$custom = json_decode($userdata['params']);
			if(!empty($custom)) foreach($custom as $k => $v) {
				if(substr($k,0,1) == '_') continue;
				$tag = 'asuser_custom_'.strtolower($k);
				$ret[$tag] = $v;
			}
		}

		// =====================================================================
		// SUBSCRIPTION DATA
		// =====================================================================
		foreach((array)($sub->getData()) as $k => $v) {
			if(is_array($v) || is_object($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = 'asubs_'.strtolower($k);
			$ret[$tag] = $v;
		}
		
		// Reformat percentage and values
		$ret['asubs_net_amount'] = sprintf('%0.2f', $sub->net_amount);
		$ret['asubs_tax_amount'] = sprintf('%0.2f', $sub->tax_amount);
		$ret['asubs_gross_amount'] = sprintf('%0.2f', $sub->gross_amount);
		$ret['asubs_tax_percent'] = sprintf('%0.2f', $sub->tax_percent);
		$ret['asubs_affiliate_comission'] = sprintf('%0.2f', $sub->affiliate_comission);
		$ret['asubs_prediscount_amount'] = sprintf('%0.2f', $sub->prediscount_amount);
		$ret['asubs_discount_amount'] = sprintf('%0.2f', $sub->discount_amount);
		
		// =====================================================================
		// SUBSCRIPTION LEVEL DATA
		// =====================================================================
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->getItem($sub->akeebasubs_level_id);
		foreach((array)($level->getData()) as $k => $v) {
			if(is_array($v) || is_object($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_level_id') $k = 'id';
			$tag = 'aslevel_'.strtolower($k);
			$ret[$tag] = $v;
		}
		
		// Reformat percentage and values
		$ret['aslevel_price'] = sprintf('%0.2f', $level->price);
		
		// =====================================================================
		// MISCELLANEOUS DATA
		// =====================================================================
		// VAT notice for B2B intra-EU transactions
		$ret['asubs_vat_notice'] = '';
		if($kuser->viesregistered && ($sub->tax_amount < 0.001) && ($sub->tax_percent < 0.001)) {
			// Is the user a resident of an EU country?
			$european_states = array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
			if(in_array($kuser->country, $european_states)) {
				// Show the VIES notice
				$ret['asubs_vat_notice'] = JText::_('PLG_CCINVOICETAGS_AKEEBASUBS_VATNOTICE');
			}
		}
		
		// Download ID
		$query = $db->getQuery(true)
			->select('MD5(CONCAT('.$db->qn('id').','.$db->qn('username').','.$db->qn('password').')) AS '.$db->qn('dlid'))
			->from($db->qn('#__users'))
			->where($db->qn('id').' = '.$db->q($sub->user_id));
		$db->setQuery($query, 0, 1);
		$dlid = $db->loadResult();
		$ret['akeeba_dlid'] = $dlid;
		
		// Currency sign
		$ret['$'] = AkeebasubsHelperCparams::getParam('currencysymbol','€');
		
		// =====================================================================
		// COUPON DATA
		// =====================================================================

		
		//echo "<pre>"; var_dump($ret); echo "</pre>"; die();
		
		return $ret;
	}
}
?>