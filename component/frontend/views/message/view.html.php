<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewMessage extends FOFViewHtml
{
	protected function onRead($tpl = null)
	{
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');

		$app = JFactory::getApplication();

		$ret = parent::onRead($tpl);

		switch($this->getLayout())
		{
			case 'cancel':
				$event = 'onCancelMessage';
				$field = 'cancelurl';
				break;

			case 'order':
			default:
				$event = 'onOrderMessage';
				$field = 'orderurl';
				break;
		}

		// Do I have a custom redirect URL? Follow it instead of showing the message
		// This check has been put here so controller and model can do all their logic and trigger every event
		if($this->item->$field)
		{
			$app->redirect($this->item->$field);
		}

		$pluginHtml = '';

		$jResponse = $app->triggerEvent($event, array($this->subscription));

		if(is_array($jResponse) && !empty($jResponse))
		{
			foreach($jResponse as $pluginResponse)
			{
				if(!empty($pluginResponse))
				{
					$pluginHtml .= $pluginResponse;
				}
			}
		}

		$this->assignRef('pluginHTML', $pluginHtml);

		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		JResponse::setHeader('X-Cache-Control', 'False', true);

		return $ret;
	}
}
