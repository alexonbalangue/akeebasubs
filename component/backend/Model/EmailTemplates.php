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

class EmailTemplates extends DataModel
{
	/**
	 * Overrides the constructor to add the Filters behaviour
	 *
	 * @param Container $container
	 * @param array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->addBehaviour('Filters');
	}

	/**
	 * Unpublish the newly copied item
	 *
	 * @param EmailTemplates $copy
	 */
	protected function onAfterCopy(EmailTemplates $copy)
	{
		// Unpublish the newly copied item
		if ($copy->enabled)
		{
			$this->publish(0);
		}
	}
}