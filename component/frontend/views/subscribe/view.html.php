<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewSubscribe extends FOFViewHtml
{
	protected function onDisplay($tpl = null)
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		JResponse::setHeader('X-Cache-Control', 'False', true);

		parent::onDisplay($tpl);
	}

	protected function onAdd($tpl = null)
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		JResponse::setHeader('X-Cache-Control', 'False', true);
	}
}