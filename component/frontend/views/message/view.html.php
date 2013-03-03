<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewMessage extends FOFViewHtml
{
	protected function onRead($tpl = null) {
		$ret = parent::onRead($tpl);
		
		switch($this->getLayout())
		{
			case 'cancel':
				$event = 'onCancelMessage';
				break;
			
			case 'order':
			default:
				$event = 'onOrderMessage';
				break;
		}
		
		$pluginHtml = '';
		
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent($event, array($this->subscription));
		if(is_array($jResponse) && !empty($jResponse)) {
			foreach($jResponse as $pluginResponse) {
				if(!empty($pluginResponse)) {
					$pluginHtml .= $pluginResponse;
				}
			}
		}
		
		$this->assignRef('pluginHTML', $pluginHtml);
		
		return $ret;
	}
}
?>

