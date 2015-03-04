<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;
use JDate;
use JLoader;
use JText;

class TaxRules extends DataModel
{
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');
	}

	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);

		$db = $this->getDbo();

		$search = $this->getState('search', null, 'string');

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where($db->qn('city') . ' LIKE ' . $db->q($search));
		}

		if (!$overrideLimits)
		{
			$order = $this->getState('filter_order', 'akeebasubs_taxrule_id', 'cmd');

			if (!in_array($order, array_keys($this->getData())))
			{
				$order = 'akeebasubs_taxrule_id';
			}

			$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
			$query->order($order . ' ' . $dir);
		}

		return $query;
	}
}