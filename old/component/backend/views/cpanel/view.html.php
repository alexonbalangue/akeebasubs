<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewCpanel extends F0FViewHtml
{
	protected function onBrowse($tpl = null)
	{
		$result = parent::onBrowse($tpl);

		if ($result || is_null($result))
		{
			/** @var AkeebasubsModelCpanels $model */
			$model = $this->getModel();
			$this->hasGeoIPPlugin = $model->hasGeoIPPlugin();
			$this->geoIPPluginNeedsUpdate = $model->GeoIPDBNeedsUpdate();
		}

		return $result;
	}
}