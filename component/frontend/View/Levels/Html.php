<?php
/**
 * @package        Akeeba Subscriptions
 * @copyright      2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Akeeba\Subscriptions\Site\View\Levels;

use Akeeba\Subscriptions\Site\Model\Subscriptions;

class Html extends \FOF30\View\DataView\Html
{
	protected function onBeforeBrowse()
	{
		$subIDs = array();
		$user = \JFactory::getUser();

		if ($user->id)
		{
			/** @var Subscriptions $mysubs */
			$mysubs = $this->container->factory->model('Subscriptions')->savestate(0)->setIgnoreRequest(true);
			$mysubs
				->user_id($user->id)
				->paystate('C')
				->get(true);

			if (!empty($mysubs))
			{
				foreach ($mysubs as $sub)
				{
					$subIDs[] = $sub->akeebasubs_level_id;
				}
			}

			$subIDs = array_unique($subIDs);
		}

		$this->subIDs = $subIDs;

		parent::onBeforeBrowse();
	}
}