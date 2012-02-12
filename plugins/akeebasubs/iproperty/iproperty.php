<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsIproperty extends JPlugin
{
	/** @var array Subscription levels which cause Companies and Agents to be added/published */
	private $authLevels = array();
	
	/** @var array Subscription levels which cause Agents to be unpublished */
	private $deauthLevels = array();
	
	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);

		$this->authLevels = $this->params->get('autoauthids',array());
		$this->deauthLevels = $this->params->get('autodeauthids',array());
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(array_key_exists('enabled', (array)$info['modified'])) {
			$this->onAKUserRefresh($row->user_id);
		}
	}
	
	/**
	 * Called whenever the administrator asks to refresh integration status.
	 * 
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();
	
		// Do I have to activate the user?
		$mustActivate = false;
		$mustDeactivate = false;
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				if(in_array($level, $this->authLevels)) {
					$mustActivate = true;
				}
			} else {
				if(in_array($level, $this->deauthLevels)) {
					$mustDeactivate = true;
				}
			}
		}

		if($mustActivate && $mustDeactivate) {
			$mustDeactivate = false;
		}
		
		if($mustActivate) {
			$this->publishAgent($user_id);
		} elseif($mustDeactivate) {
			$this->unpublishAgent($user_id);
		}
		
	}
	
	private function publishAgent($user_id)
	{
		// First, check if we already have agents for that user ID
		$db = JFactory::getDbo();
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->nameQuote('#__iproperty_agents'))
			->where($db->nameQuote('user_id').' = '.$db->quote($user_id));
		$db->setQuery($query);
		$agents = $db->loadObjectList();
		
		if(!empty($agents)) {
			$query = FOFQueryAbstract::getNew($db)
				->update($db->nameQuote('#__iproperty_agents'))
				->set($db->nameQuote('state').' = '.$db->quote(1))
				->where($db->nameQuote('user_id').' = '.$db->quote($user_id));
			$db->setQuery($query);
			$db->query();
		} else {
			// Load the user data
			$user = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($user_id)
				->getMergedData();
			
			// Create a company
			$name = empty($user->businessname) ? $user->name : $user->businessname;
			
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
			$alias = AkeebasubsHelperFilter::toSlug($name);
			
			$company = (object)array(
				'name'			=> $name,
				'alias'			=> $alias,
				'description'	=> '&nbsp;',
				'street'		=> $user->address1,
				'city'			=> $user->city,
				'locstate'		=> $user->state,
				'province'		=> '',
				'postcode'		=> $user->zip,
				'country'		=> $user->country,
				'fax'			=> '',
				'phone'			=> '',
				'email'			=> $user->email,
				'website'		=> '',
				'featured'		=> 0,
				'icon'			=> 'nopic.png',
				'clicence'		=> '',
				'language'		=> '',
				'state'			=> 1,
				'params'		=> ''
			);
			
			$db->insertObject('#__iproperty_companies', $company);
			
			$companyid = $db->insertid();
			
			// Create an agent
			$nameParts = explode(' ', $user->name, 2);
			$firstName = $nameParts[0];
			if(count($nameParts) > 1) {
				$lastName = $nameParts[1];
			} else {
				$lastName = '';
			}
			$alias = AkeebasubsHelperFilter::toSlug($user->name);
			
			$agent = (object)array(
				'agent_type'	=> 0,
				'hometeam'		=> 0,
				'fname'			=> $firstName,
				'lname'			=> $lastName,
				'alias'			=> $alias,
				'company'		=> $companyid,
				'email'			=> $user->email,
				'phone'			=> '',
				'mobile'		=> '',
				'fax'			=> '',
				'street'		=> $user->address1,
				'street2'		=> $user->address2,
				'city'			=> $user->city,
				'locstate'		=> $user->state,
				'province'		=> '',
				'postcode'		=> $user->zip,
				'country'		=> $user->country,
				'website'		=> '',
				'bio'			=> '&nbsp;',
				'user_id'		=> $user_id,
				'featured'		=> 0,
				'icon'			=> 'nopic.png',
				'msn'			=> '',
				'skype'			=> '',
				'gtalk'			=> '',
				'linkedin'		=> '',
				'facebook'		=> '',
				'twitter'		=> '',
				'social1'		=> '',
				'alicense'		=> '',
				'state'			=> 1,
				'params'		=> '',
			);
			$db->insertObject('#__iproperty_agents', $agent);
		}
	}
	
	private function unpublishAgent($user_id)
	{
		$db = JFactory::getDbo();
		$query = FOFQueryAbstract::getNew($db)
			->update($db->nameQuote('#__iproperty_agents'))
			->set($db->nameQuote('state').' = '.$db->quote(0))
			->where($db->nameQuote('user_id').' = '.$db->quote($user_id));
		$db->setQuery($query);
		$db->query();
	}
}