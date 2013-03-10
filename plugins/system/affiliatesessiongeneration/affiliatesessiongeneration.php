<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

JLoader::import('joomla.plugin.plugin');

class plgSystemaffiliatesessiongeneration extends JPlugin
{
	function plgSystemaffiliatesessiongeneration( &$subject, $params )
	{
		parent::__construct( $subject, $params );
    }

	function onAfterInitialise()
	{
		$affid=JRequest::getInt('affid','0');
		if($affid)
		{
			$session = JFactory::getSession();
			$session->set('affid', $affid, 'com_akeebasubs');			
		}
	}
}
?>