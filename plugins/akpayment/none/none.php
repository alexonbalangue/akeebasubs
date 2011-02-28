<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkpaymentNone extends JPlugin
{
	private $ppName = 'none';
	private $ppKey = 'PLG_AKPAYMENT_NONE_TITLE';
	
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		JPlugin::loadLanguage( 'plg_akpayment_none' );
	}
	
	public function onAKPaymentGetIdentity()
	{
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> JText::_($this->ppKey)
		);
		return (object)$ret;
	}
	
	public function onAKPaymentNew($user, $level, $subscription)
	{
		die('TODO: Must do something on new payments');
		// TODO
	}
	
	public function onAKPaymentCallback($get, $post)
	{
		// Everything is fine, no matter what
		return true;
	}
}