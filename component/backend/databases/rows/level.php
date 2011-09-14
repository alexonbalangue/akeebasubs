<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsDatabaseRowLevel extends KDatabaseRowTable
{
	public function delete()
	{
		$result = false;
		
		if($this->isConnected())
		{
			// Do we have subscriptions on that level?
			$subs = KFactory::get('com://admin/akeebasubs.model.subscriptions')
				->level($this->id)
				->getTotal();

			if($subs) {
				$this->setStatusMessage(JText::_('COM_AKEEBASUBS_LEVELS_ERR_EXISTINGSUBS'));
				return false;
			} else {
				return parent::delete();
			}
		}
		
		return (bool) $result;
	}

	/**
	 * setData is overriden to add Joom!Fish support. JF won't work on Nooku
	 * extensions because Nooku is using its own db driver. So, what we do, is
	 * that we detect if Joom!Fish is installed and replace the data NF loaded
	 * from the database with localised data supplied by JF when we do a normal
	 * query against Joomla!'s database. Yes, it results in multiple requests,
	 * but the translation results are cached to mitigate this issue.
	 * 
	 * @param array $data The associative array of data to load
	 * @param bool $modified Are the row data modified?
	 * @return ComAkeebasubsDatabaseRowLevel 
	 */
	public function setData( $data, $modified = true )
	{
		static $jfCache = array();
		
		parent::setData($data, $modified);
		if(!$modified) {
			if($this->id) {
				if(JFactory::getConfig()->getValue('config.multilingual_support') == 1) {
					if(!array_key_exists($this->id, $jfCache)) {
						$db = JFactory::getDBO();
						$sql = 'SELECT * FROM '.$db->nameQuote('#__akeebasubs_levels').' WHERE '.
							$db->nameQuote('akeebasubs_level_id').' = '.$this->id;
						$db->setQuery($sql);
						$jfCache[$this->id] = $db->loadAssoc();
					}
					parent::setData($jfCache[$this->id], false);
				}
			}
		}
		return $this;		
	}
}