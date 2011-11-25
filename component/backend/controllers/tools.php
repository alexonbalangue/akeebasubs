<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerTools extends FOFController
{
	public function import($cacheable = false)
	{
		$converter = FOFInput::getCmd('converter','',$this->input);
		$print_r = FOFInput::getBool('print_r',false,$this->input);
		
		$ret = $this->getThisModel()->getItem($converter)->convert();

		if($print_r) {
			echo "<pre>".var_dump($ret->result).'</pre>';
		} else {
			echo json_encode($ret->result);
		}
		JFactory::getApplication()->close();
	}
}