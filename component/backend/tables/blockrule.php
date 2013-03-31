<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableBlockrule extends FOFTable
{
	public function check() {
		$result = true;

		$this->username = trim($this->username);
		$this->name = trim($this->name);
		$this->email = trim($this->email);
		$this->iprange = trim($this->iprange);

		if (empty($this->username) && empty($this->name) && empty($this->email) && empty($this->iprange))
		{
			$this->setError(JText::_('COM_AKEEBASUBS_BLOCKRULE_ERR_ALLEMPTY'));
			$result = false;
		}

		return $result;
	}
}