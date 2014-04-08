<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

class AkeebasubsViewReports extends F0FViewJson
{
	protected function onGetexpirations($tpl = null)
	{
		$model = F0FModel::getAnInstance('Subscriptions', 'AkeebasubsModel');
		$this->setModel($model, true);

		return parent::onDisplay($tpl);
	}
}
