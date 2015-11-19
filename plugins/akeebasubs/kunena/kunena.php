<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c) 2012 Roland Dalmulder / csvimproved.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Levels;

class plgAkeebasubsKunena extends \Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase
{
	/**
	 * Used to prevent firing this plugin when we're making changes to subscriptions
	 *
	 * @var bool
	 */
	private static $dontFire = false;

	public function __construct(&$subject, $config = array())
	{
		$config['templatePath'] = dirname(__FILE__);
		$config['name']         = 'kunena';

		parent::__construct($subject, $config);
	}

	/**
	 * Renders the configuration page in the component's back-end
	 *
	 * @param   Levels $level The subscription level
	 *
	 * @return  stdClass  Definition object, with two properties: 'title' and 'html'
	 */
	public function onSubscriptionLevelFormRender(Levels $level)
	{
		$filePath = 'plugin://akeebasubs/' . $this->name . '/default.php';
		$filename = $this->container->template->parsePath($filePath, true);

		$params = $level->params;

		if (!isset($params['slavesubs_maxSlaves']))
		{
			$params['slavesubs_maxSlaves'] = 0;
		}

		$level->params = $params;

		@ob_start();

		include_once $filename;

		$html = @ob_get_clean();

		$ret = (object) array(
			'title' => JText::_('PLG_AKEEBASUBS_KUNENA_TAB_TITLE'),
			'html'  => $html
		);

		return $ret;
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param   int $user_id The Joomla! user ID to refresh information for.
	 *
	 * @return  void
	 */
	public function onAKUserRefresh($user_id)
	{
		// Load groups
		$addGroups = array();
		$removeGroups = array();
		$this->loadUserGroups($user_id, $addGroups, $removeGroups);
		if (empty($addGroups) && empty($removeGroups))
		{
			return;
		}
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		foreach ($addGroups as $gid)
		{
			$query = $db->getQuery(true)
				->update($db->qn('#__kunena_users'))
				->set($db->qn('rank').' = '.$db->q($gid))
				->where($db->qn('userid').' = '.$db->q($user_id));
			$db->setQuery($query);
			$db->execute();
		}

		foreach ($removeGroups as $gid)
		{
			$query = $db->getQuery(true)
				->update($db->qn('#__kunena_users'))
				->set($db->qn('rank').' = '.$db->q($gid))
				->where($db->qn('userid').' = '.$db->q($user_id));
			$db->setQuery($query);
			$db->execute();
		}
		
	}
	
	protected function getGroups()
	{
		static $groups = null;
		
		if (is_null($groups))
		{
			$groups = array();
			
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('rank_title'),
					$db->qn('rank_id'),
				))
				->from($db->qn('#__kunena_ranks'));
			$db->setQuery($query);
			$res = $db->loadObjectList();
			
			if (!empty($res))
			{
				foreach ($res as $item)
				{
					$t = strtoupper(trim($item->rank_title));
					$groups[$t] = $item->rank_id;
				}
			}
		}
		
		return $groups;
	}

	/**
	 * Used by the template to render selection fields
	 *
	 * @param   \Akeeba\Subscriptions\Admin\Model\Levels  $level  The subscription level
	 * @param   string                                    $type   add or remove
	 *
	 * @return  string  The HTML for the drop-down field
	 */
	protected function getSelectField(\Akeeba\Subscriptions\Admin\Model\Levels $level, $type)
	{
		if (!in_array($type, array('add', 'remove')))
		{
			return '';
		}

		// Put groups in select field
		$groups = $this->getGroups();
		$options = array();
		$options[] = JHTML::_('select.option','',JText::_('PLG_AKEEBASUBS_' . strtoupper($this->name) . '_NONE'));
		foreach ($groups as $title => $id)
		{
			$options[] = JHTML::_('select.option',$id,$title);
		}

		// Set pre-selected values
		$selected = array();
		if($type == 'add')
		{
			if (!empty($this->addGroups[$level->akeebasubs_level_id]))
			{
				$selected = $this->addGroups[$level->akeebasubs_level_id];
			}
		}
		else
		{
			if (!empty($this->removeGroups[$level->akeebasubs_level_id]))
			{
				$selected = $this->removeGroups[$level->akeebasubs_level_id];
			}
		}

		// Create the select field
		return JHtmlSelect::genericlist($options, 'params[' . strtolower($this->name) . '_' . $type . 'groups][]', 'multiple="multiple" size="8" class="input-large"', 'value', 'text', $selected);
	}
}