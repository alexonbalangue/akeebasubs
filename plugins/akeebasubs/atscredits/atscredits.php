<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

// Make sure ATS is installed and activated
JLoader::import('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_ats', true)) return;

class plgAkeebasubsAtscredits extends JPlugin
{
	/** @var array Levels to number of credits added mapping */
	private $credits = array();
	
	/**
	 * Public constructor
	 * 
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *                             Recognized key values include 'name', 'group', 'params', 'language'
	 *                             (this list is not meant to be comprehensive).
	 */
	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
		
		$this->loadLanguage();
		
		$this->loadPluginConfiguration();
	}
	
	/**
	 * Loads the configuration parameters of this plugin from all of the
	 * subscription levels available.
	 */
	private function loadPluginConfiguration()
	{
		$this->credits = array();
		
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}
				if(property_exists($level->params, 'atscredits_credits'))
				{
					$this->credits[$level->akeebasubs_level_id] = $level->params->atscredits_credits;
				}
			}
		}
	}
	
	/**
	 * Renders the configuration page in the component's back-end
	 * 
	 * @param   AkeebasubsTableLevel  $level  The subscription level we're rendering for
	 * 
	 * @return object
	 */
	public function onSubscriptionLevelFormRender(AkeebasubsTableLevel $level)
	{
		JLoader::import('joomla.filesystem.file');
		$filename = dirname(__FILE__).'/override/default.php';
		if(!JFile::exists($filename)) {
			$filename = dirname(__FILE__).'/tmpl/default.php';
		}
		
		if(!property_exists($level->params, 'atscredits_credits')) {
			$level->params->atscredits_credits = 0;
		}
		
		@ob_start();
		include_once $filename;
		$html = @ob_get_clean();
		
		$ret = (object)array(
			'title'	=> JText::_('PLG_AKEEBASUBS_ATSCREDITS_TAB_TITLE'),
			'html'	=> $html
		);
		
		return $ret;
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		$this->onAKUserRefresh($row->user_id);
	}
	
	/**
	 * Called whenever the administrator asks to refresh integration status.
	 * 
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Make sure we're configured
		if(empty($this->credits)) return;

		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();

		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;
		
		// Get credit information for the user
		if(!class_exists('AtsHelperCredits')) {
			@include_once JPATH_ADMINISTRATOR.'/components/com_ats/helpers/credits.php';
		}
		if(!class_exists('AtsHelperCredits')) return;
		$userCreditAnalysis = AtsHelperCredits::creditsLeft($user_id, false);
		
		// Get all #__ats_credittransactions entries
		$atsCreditEntries = FOFModel::getTmpInstance('Credittransactions', 'AtsModel')
			->user_id($user_id)
			->type('akeebasubs')
			->getList(true);
		
		// Create a map of #__ats_credittransactions per subscription ID
		$creditTransactions = array();
		if(!empty($atsCreditEntries)) foreach($atsCreditEntries as $ce) {
			$temp = array(
				'id'			=> $ce->ats_credittransaction_id,
				'value'			=> $ce->value,
				'enabled'		=> $ce->enabled,
				'used'			=> 0
			);
			if(array_key_exists($ce->ats_credittransaction_id, $userCreditAnalysis['charges'])) {
				$temp['used'] = $userCreditAnalysis['charges'][$ce->ats_credittransaction_id];
			}
			$creditTransactions[$ce->unique_id] = $temp;
			unset($temp);
		}
		unset($atsCreditEntries, $userCreditAnalysis);
		
		// Walk through all subscriptions
		foreach($subscriptions as $sub)
		{
			// Does this subscription level exist in $this->credits?
			if(!array_key_exists($sub->akeebasubs_level_id, $this->credits)) {
				return;
			}
			
			$value = $this->credits[$sub->akeebasubs_level_id];
			
			// Do we have an #__ats_credittransactions record for it?
			$hasTransaction = array_key_exists($sub->akeebasubs_subscription_id, $creditTransactions);
			
			// Is it active or paid and with a start date in the future?
			$jPublishUp = new JDate($sub->publish_up);
			$jNow = new JDate();
			$enabled = $sub->enabled
					||( ($sub->state = 'C') && ($jPublishUp->toUnix() > $jNow->toUnix()) ) ;
			if($enabled) {
				if(!$hasTransaction) {
					// Create a new transaction
					$data = array(
						'user_id'			=> $user_id,
						'transaction_date'	=> $sub->created_on,
						'type'				=> 'akeebasubs',
						'unique_id'			=> $sub->akeebasubs_subscription_id,
						'value'				=> $value
					);
					$table = FOFModel::getTmpInstance('Credittransactions', 'AtsModel')
						->getTable();
					$table->reset();
					$table->save($data);
				} else {
					// Check how many credits are left, based on the current worth of the subscription
					$transaction = $creditTransactions[$sub->akeebasubs_subscription_id];
					$left = $value - $transaction['used'];
					
					$data = array(
					);
					
					if($value != $transaction['value']) {
						$data['value'] = $value;
					}

					if(!$transaction['enabled']) {
						$data['enabled'] = 1;
					}
					
					if(!empty($data)) {
						$record = FOFModel::getTmpInstance('Credittransactions', 'AtsModel')
							->type('akeebasubs')
							->unique_id($sub->akeebasubs_subscription_id)
							->getFirstItem(true);
						$record->save($data);
					}
				}
				
			}
			// Otherwise it's an expired or unpaid subscription with an #__ats_credittransactions record 
			elseif($hasTransaction)
			{
				// Disable the record
				$data = array(
					'enabled'			=> 0
				);
				$record = FOFModel::getTmpInstance('Credittransactions', 'AtsModel')
					->type('akeebasubs')
					->unique_id($sub->akeebasubs_subscription_id)
					->getFirstItem(true);
				$record->save($data);
			}
		} // end foreach subscrition
	}
}