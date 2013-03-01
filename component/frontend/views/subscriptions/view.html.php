<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsViewSubscriptions extends FOFViewHtml
{
	public function onDisplay($tpl = null) {
		$ret = parent::onDisplay($tpl);
		if ($ret)
		{
			// Get all levels and all active levels
			$rawActiveLevels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->enabled(1)
				->getList();
			$activeLevels = array();
			$allLevels = array();
			if(!empty($rawActiveLevels)) foreach($rawActiveLevels as $l) {
				$activeLevels[] = $l->akeebasubs_level_id;
				$allLevels[$l->akeebasubs_level_id] = $l;
			}

			// Get subscription level IDs
			$subIDs = array();
			$subscription_ids = array();
			if(count($this->items)) foreach($this->items as $sub) {
				$subIDs[] = $sub->akeebasubs_level_id;
				$subscription_ids[] = $sub->akeebasubs_subscription_id;
			}
			$subIDs = array_unique($subIDs);

			// Get invoices data
			$invoices = array();
			if (AKEEBASUBS_PRO)
			{
				$rawInvoices = FOFModel::getTmpInstance('Invoices', 'AkeebasubsModel')
					->subids($subscription_ids)
					->getList();
				if (!empty($rawInvoices))
				{
					foreach ($rawInvoices as $rawInvoice)
					{
						$invoices[$rawInvoice->akeebasubs_subscription_id] = $rawInvoice;
					}
				}
			}

			$extensions = FOFModel::getTmpInstance('Invoices', 'AkeebasubsModel')->getExtensions();

			// Assign variables
			$this->assign('activeLevels', $activeLevels);
			$this->assign('allLevels', $allLevels);
			$this->assign('subIDs', $subIDs);
			$this->assign('invoices', $invoices);
			$this->assign('extensions', $extensions);
		}
		return $ret;
	}
}