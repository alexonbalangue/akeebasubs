<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsOsiaffiliate extends JPlugin
{
	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);
	}
	
	public function onAKAfterPaymentCallback($subscription)
	{
		// Make sure the subscription is enabled
		if(!$subscription->enabled) return;
		// Make sure the subscription is paid
		if(!$subscription->state != 'C') return;
		
		// Get the configuration
		$subdirectory = trim($this->params->get('subdirectory'),'/');
		if(empty($subdirectory)) return;
		
		// Do the post-back
		$host = 'ssl://www.osiaffiliate.com';		
		$url = '/'.$subdirectory.'/sale.php?amount='.
			sprintf('%.2f', $subscription->net_amount).'&transaction='.
			$subscription->id;
		
		$header = '';
		$header .= "GET $url HTTP/1.0\r\n";
		$port = 443;
		$fp = fsockopen ($host, $port, $errno, $errstr, 30);
		if (!$fp) return;
		fputs ($fp, $header);
		while (!feof($fp)) {
			$res = fgets ($fp, 1024);
		}
		fclose ($fp);
	}
}