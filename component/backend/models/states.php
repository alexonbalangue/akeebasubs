<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelStates extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);

		if($this->getState('orderByLabels'))
		{
			$query->clear('order');
			$query->order('country ASC, label ASC');
		}

		return $query;
	}
}