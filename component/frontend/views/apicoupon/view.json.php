<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewApicoupon extends F0FViewJson
{
	public function onCreate($tpl = null)
	{
		$document = F0FPlatform::getInstance()->getDocument();
		if ($document instanceof JDocument)
		{
			if ($this->useHypermedia)
			{
				$document->setMimeEncoding('application/hal+json');
			}
			else
			{
				$document->setMimeEncoding('application/json');
			}
		}

		$key = $this->input->getCmd('key', '');
		$pwd = $this->input->getCmd('pwd', '');

		$json = $this->getModel()->createCoupon($key, $pwd);
		$json = json_encode($json);

		// JSONP support
		$callback = $this->input->get('callback', null);

		if (!empty($callback))
		{
			echo $callback . '(' . $json . ')';
		}
		else
		{
			$defaultName = $this->input->getCmd('view', 'joomla');
			$filename = $this->input->getCmd('basename', $defaultName);
			$document->setName($filename);
			echo $json;
		}

		return false;
	}
}