<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

class plgAkpaymentIFthen extends JPlugin
{
	private $ppName = 'ifthen';
	private $ppKey = 'PLG_AKPAYMENT_IFTHEN_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}
			
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		require_once dirname(__FILE__).'/ifthen/library/mb.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_ifthen', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_ifthen', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_ifthen', JPATH_ADMINISTRATOR, null, true);
	}

	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title
		);
		$ret['image'] = trim($this->params->get('ppimage',''));
		if(empty($ret['image'])) {
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/multibanco.jpg';
		}
		return (object)$ret;
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
			'url'				=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=ifthen')),
			'entidade'			=> trim($this->params->get('entidade','')),
			'subentidade'		=> trim($this->params->get('subentidade','')),
			'valor'				=> sprintf('%.2f',$subscription->gross_amount),
			'currency'			=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'subscription_id'	=> $subscription->akeebasubs_subscription_id
		);
		
		$data->referencia = MBLibrary::GenerateMbRef(
				$data->entidade,
				$data->subentidade,
				$data->subscription_id,
				$data->valor);

		@ob_start();
		include dirname(__FILE__).'/ifthen/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		if($paymentmethod != $this->ppName) return false;
		
		// Enable the subscription
		$id = (int)$data['subscription'];
		$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->setId($id)
			->getItem();
		
		if(empty($subscription->akeebasubs_subscription_id)) return false;
		
		if($subscription->akeebasubs_subscription_id != $id) return false;
		
		$key = $data['reference'];
		if(empty($key)) return false;
		
		// Fix the starting date if the payment was accepted after the subscription's start date. This
		// works around the case where someone pays by e-Check on January 1st and the check is cleared
		// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
		// the user would have paid us and we'd never given him a subscription!
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
		if(!preg_match($regex, $subscription->publish_up)) {
			$subscription->publish_up = '2001-01-01';
		}
		if(!preg_match($regex, $subscription->publish_down)) {
			$subscription->publish_down = '2037-01-01';
		}
		jimport('joomla.utilities.date');
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
		
		$id = (int)$data['subscription'];
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> $key,
			'state'				=> 'C',
			'enabled'			=> 1,
			'publish_up'		=> $jStart->toSql(),
			'publish_down'		=> $jEnd->toSql()
		);
		$subscription->save($updates);
		
		// Run the onAKAfterPaymentCallback events
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
		
		// This plugin is a tricky one; it will redirect you to the thank you page
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		/**
		$url = $rootURL.str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)); 
		/**/ 
		$url = 'index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id;
		$app = JFactory::getApplication();
		$app->redirect($url);
		
		// Everything is fine, no matter what
		return true;
	}
}