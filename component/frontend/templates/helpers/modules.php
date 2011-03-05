<?php
class ComAkeebasubsTemplateHelperListbox extends ComDefaultTemplateHelperAbstract
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