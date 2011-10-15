<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
			$format = '%Y-%m-%d %H:%M';
		}
		
		return $jDate->toFormat($format);
	}
	
	public static function formatLevel($id)
	{
		static $levels;
		
		if(empty($levels)) {
			$levelsList = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getItemList();
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
}