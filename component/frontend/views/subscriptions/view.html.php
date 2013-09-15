<?php

/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class AkeebasubsViewSubscriptions extends FOFViewHtml
{

	public function onDisplay($tpl = null)
	{
		$ret = parent::onDisplay($tpl);

		if ($ret)
		{
			// Get all levels and all active levels
			$rawActiveLevels = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->enabled(1)
				->getList();

			$activeLevels	 = array();
			$allLevels		 = array();

			if (!empty($rawActiveLevels))
			{
				foreach ($rawActiveLevels as $l)
				{
					$activeLevels[]						 = $l->akeebasubs_level_id;
					$allLevels[$l->akeebasubs_level_id]	 = $l;
				}
			}

			// Get subscription and subscription level IDs, sort subscriptions
			// based on status
			$subIDs				 = array();
			$subscription_ids	 = array();
			$sortTable			 = array(
				'active'	 => array(),
				'waiting'	 => array(),
				'pending'	 => array(),
				'expired'	 => array(),
			);

			if (count($this->items))
			{
				JLoader::import('joomla.utilities.date');

				foreach ($this->items as $sub)
				{
					$id = $sub->akeebasubs_subscription_id;

					$subIDs[]			 = $id;
					$subscription_ids[]	 = $id;

					if (!$sub->enabled)
					{
						$jd = new JDate($sub->publish_up);
					}

					if ($sub->enabled)
					{
						$sortTable['active'][] = $id;
					}
					elseif (($sub->state == 'C') && ($jd->toUnix() >= time()))
					{
						$sortTable['waiting'][] = $id;
					}
					elseif ($sub->state == 'P')
					{
						$sortTable['pending'][] = $id;
					}
					else
					{
						$sortTable['expired'][] = $id;
					}
				}
			}
			$subIDs = array_unique($subIDs);

			// Get invoices data
			$invoices = array();

			if (AKEEBASUBS_PRO && !empty($subscription_ids))
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

			// Get incoiving extensions
			$extensions = FOFModel::getTmpInstance('Invoices', 'AkeebasubsModel')->getExtensions();

			// Assign variables
			$this->activeLevels	 = $activeLevels;
			$this->allLevels	 = $allLevels;
			$this->subIDs		 = $subIDs;
			$this->invoices		 = $invoices;
			$this->extensions	 = $extensions;
			$this->sortTable	 = $sortTable;
		}

		return $ret;
	}

}