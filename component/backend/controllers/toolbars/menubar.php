<?php
class ComAkeebasubsControllerToolbarMenubar extends ComDefaultControllerToolbarMenubar
{
    public function getCommands()
    { 
        $this->_addASCommands(array(
			'dashboard' 		=> 'COM_AKEEBASUBS_DASHBOARD',
			'levels' 			=> 'COM_AKEEBASUBS_LEVELS_TITLE',
			'subscriptions'		=> 'COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE',
			'coupons'			=> 'COM_AKEEBASUBS_COUPONS_TITLE',
			'upgrades'			=> 'COM_AKEEBASUBS_UPGRADES_TITLE',
			'taxrules'			=> 'COM_AKEEBASUBS_TAXRULES_TITLE',
			'users'				=> 'COM_AKEEBASUBS_USERS_TITLE',
			'config'			=> 'COM_AKEEBASUBS_CONFIG_TITLE'
		));
         
        return parent::getCommands();
    }
	
	private function _addASCommand($view, $title)
	{
		static $name = null;
		static $namePlural = null;
		
		if(is_null($name)) {
			$name = $this->getController()->getIdentifier()->name;
			$namePlural = (KInflector::isSingular($name)) ? KInflector::pluralize($name) : $name;
		}
		
		$viewPlural = (KInflector::isSingular($view)) ? KInflector::pluralize($view) : $view;

		$this->addCommand($title, array(
			'id'		=> 'as-menubar-'.$view,
			'label'		=> JText::_($title),
        	'href'		=> JRoute::_('index.php?option=com_akeebasubs&view='.$view), 
			'active'	=> ($namePlural == $viewPlural)
        ));
	}
	
	private function _addASCommands($array)
	{
		foreach($array as $view => $title) {
			$this->_addASCommand($view, $title);
		}
	}
}