<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsHelperFormat
{
	public static function date($date, $format = null)
	{
		jimport('joomla.utilities.date');
		$jDate = new JDate($date);
		
		if(empty($format)) {
			if(!class_exists('AkeebasubsHelperCparams')) {
				require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
			}
			$format = AkeebasubsHelperCparams::getParam('dateformat', '%Y-%m-%d %H:%M');
		}
		
		return $jDate->toFormat($format);
	}
	
	public static function formatLevel($id)
	{
		static $levels;
		
		if(empty($levels)) {
			$levelsList = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getItemList(true);
			if(!empty($levelsList)) foreach($levelsList as $level) {
				$levels[$level->akeebasubs_level_id] = $level->title;
			}
		}
		
		if(array_key_exists($id, $levels)) {
			return $levels[$id];
		} else {
			return '&mdash;&mdash;&mdash;';
		}
	}
	
	public static function formatLevelgroup($id)
	{
		static $levelgroups;
		
		if(empty($levelgroups)) {
			$levelgroupsList = FOFModel::getTmpInstance('Levelgroups', 'AkeebasubsModel')
				->getItemList(true);
			if(!empty($levelgroupsList)) foreach($levelgroupsList as $levelgroup) {
				$levelgroups[$levelgroup->akeebasubs_levelgroup_id] = $levelgroup->title;
			} else {
				$levelgroups = array();
			}
		}
		
		if(array_key_exists($id, $levelgroups)) {
			return $levelgroups[$id];
		} else {
			return JText::_('COM_AKEEBASUBS_SELECT_LEVELGROUP');
		}
	}
}