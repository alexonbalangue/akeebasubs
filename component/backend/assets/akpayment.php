<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

/**
 * Akeeba Subscriptions payment plugin abstract class
 */
abstract class plgAkpaymentAbstract extends JPlugin
{
	/** @var string Name of the plugin, returned to the component */
	protected $ppName = 'abstract';
	
	/** @var string Translation key of the plugin's title, returned to the component */
	protected $ppKey = 'PLG_AKPAYMENT_ABSTRACT_TITLE';
	
	/** @var string Image path, returned to the component */
	protected $ppImage = '';
	
	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}
		
		parent::__construct($subject, $config);
		
		if(array_key_exists('ppName', $config)) {
			$this->ppName = $config['ppName'];
		}
		
		if(array_key_exists('ppImage', $config)) {
			$this->ppImage = $config['ppImage'];
		}
		
		$name = $this->ppName;
		
		if(array_key_exists('ppKey', $config)) {
			$this->ppKey = $config['ppKey'];
		} else {
			$this->ppKey = "PLG_AKPAYMENT_{$name}_TITLE";
		}
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_'.$name, JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_'.$name, JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_'.$name, JPATH_ADMINISTRATOR, null, true);
	}
	
	public final function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		
		$image = trim($this->params->get('ppimage',''));
		if(empty($image)) {
			$image = $this->ppImage;
		}
		
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title,
			'image'		=> $image
		);
		
		return (object)$ret;
	}
	
	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 * 
	 * @param string $paymentmethod Check it against $this->ppName
	 * @param JUser $user
	 * @param AkeebasubsTableLevel $level
	 * @param AkeebasubsTableSubscription $subscription
	 * @return string
	 */
	abstract public function onAKPaymentNew($paymentmethod, $user, $level, $subscription);
	
	/**
	 * Processes a callback from the payment processor
	 * 
	 * @param string $paymentmethod Check it against $this->ppName
	 * @param array $data Input data
	 */
	abstract public function onAKPaymentCallback($paymentmethod, $data);
	
	/**
	 * Fixes the starting and end dates when a payment is accepted after the
	 * subscription's start date. This works around the case where someone pays
	 * by e-Check on January 1st and the check is cleared on January 5th. He'd
	 * lose those 4 days without this trick. Or, worse, if it was a one-day pass
	 * the user would have paid us and we'd never given him a subscription!
	 * 
	 * @param AkeebasubsTableSubscription $subscription
	 * @param array $updates
	 */
	protected function fixDates($subscription, &$updates)
	{
		// Fix the starting date if the payment was accepted after the subscription's start date. This
		// works around the case where someone pays by e-Check on January 1st and the check is cleared
		// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
		// the user would have paid us and we'd never given him a subscription!
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
		if(!preg_match($regex, $subscription->publish_up)) {
			$subscription->publish_up = '2001-01-01';
		}
		if(!preg_match($regex, $subscription->publish_down)) {
			$subscription->publish_down = '2038-01-01';
		}
		$jNow = new JDate();
		$jStart = new JDate($subscription->publish_up);
		$jEnd = new JDate($subscription->publish_down);
		$now = $jNow->toUnix();
		$start = $jStart->toUnix();
		$end = $jEnd->toUnix();

		if($start < $now) {
			$duration = $end - $start;
			$start = $now;
			$end = $start + $duration;
			$jStart = new JDate($start);
			$jEnd = new JDate($end);
		}

		$updates['publish_up'] = $jStart->toSql();
		$updates['publish_down'] = $jEnd->toSql();
		$updates['enabled'] = 1;
	}
	
	/**
	 * Logs the received IPN information to file
	 * 
	 * @param array $data
	 * @param bool $isValid
	 */
	protected final function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		
		$logFilenameBase = $logpath.'/akpayment_'.strtolower($this->ppName).'_ipn';
		
		$logFile = $logFilenameBase.'.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logFilenameBase.'-1.php';
				if(JFile::exists($altLog)) {
					JFile::delete($altLog);
				}
				JFile::copy($logFile, $altLog);
				JFile::delete($logFile);
				$dummy = "<?php die(); ?>\n";
				JFile::write($logFile, $dummy);
			}
		}
		$logData = JFile::read($logFile);
		if($logData === false) $logData = '';
		$logData .= "\n" . str_repeat('-', 80);
		$pluginName = strtoupper($this->ppName);
		$logData .= $isValid ? 'VALID '.$pluginName.' IPN' : 'INVALID '.$pluginName.' IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}