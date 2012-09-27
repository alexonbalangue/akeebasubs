<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerCpanels extends FOFController
{
	public function execute($task) {
		if(!in_array($task, array('browse','hide2copromo'))) {
			$task = 'browse';
		}
		parent::execute($task);
	}
	
	protected function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		
		if($result) {
			$db = JFactory::getDbo();
			$columns = $db->getTableColumns('#__akeebasubs_levels', true);
			if(!array_key_exists('akeebasubs_levelgroup_id', $columns)) {
				$sql = JFile::read(JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.3.0-2012-06-15.sql');
				if($sql) {
					$commands = explode(';', $sql);
					foreach($commands as $query) {
						$db->setQuery($query);
						$db->query();
					}
				}
			}
		}
		
		return $result;
	}
	
	public function hide2copromo()
	{
		// Fetch the component parameters
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		// Set the displayphpwarning parameter to 0
		$params->set('show2copromo', 0);

		// Save the component parameters
		$data = $params->toString('JSON');
		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'));

		$db->setQuery($sql);
		$db->query();
		
		// Redirect back to the control panel
		$url = '';
		$returnurl = FOFInput::getBase64('returnurl', '', $this->input);
		if(!empty($returnurl)) {
			$url = base64_decode($returnurl);
		}
		if(empty($url)) {
			$url = JURI::base().'index.php?option=com_akeebasubs';
		}
		$this->setRedirect($url);
	}
}