<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsTemplateHelperModules extends KTemplateHelperAbstract
{

	public function loadposition($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'position'		=> 'joomla_module_position',
			'style'			=> -2
		));
		
		$document	= &JFactory::getDocument();
		$renderer	= $document->loadRenderer('module');
		$params		= array('style'=>$config->style);
		
		$contents = '';
		foreach (JModuleHelper::getModules($config->position) as $mod)  {
			$contents .= $renderer->render($mod, $params);
		}
		return $contents;
	}

}