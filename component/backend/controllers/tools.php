<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerTools extends FOFController
{
	public function import($cacheable = false)
	{
		$converter = $this->input->getCmd('converter','');
		$print_r = $this->input->getBool('print_r',false);

		$ret = $this->getThisModel()->getItem($converter)->convert();

		if($print_r) {
			echo "<pre>".var_dump($ret->result).'</pre>';
		} else {
			echo json_encode($ret->result);
		}
		JFactory::getApplication()->close();
	}
}