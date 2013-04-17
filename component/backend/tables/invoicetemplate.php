<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableInvoicetemplate extends FOFTable
{
	public $localformat = null;

	public function check() {
		$result = true;

		// Work around for format killing the page load
		$this->format = $this->localformat;

		// Check for title
		if(empty($this->title)) {
			$this->setError(JText::_('COM_AKEEBASUBS_INVOICETEMPLATE_ERR_TITLE'));
			$result = false;
		}

		// Normalise subscription levels
		if(!empty($this->levels)) {
			if(is_array($this->levels)) {
				$levels = $this->levels;
			} else {
				$levels = explode(',', $this->levels);
			}
			if(empty($levels)) {
				$this->levels = '';
				$levels = array(0);
			} else {
				if(in_array(0, $levels)) {
					$masterlevels = array(0);
				} elseif(in_array(-1, $levels)) {
					$masterlevels = array(-1);
				} else {
					$masterlevels = array();
					foreach($levels as $id) {
						$levelObject = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
							->setId($id)
							->getItem();
						$id = null;
						if(is_object($levelObject)) {
							if($levelObject->akeebasubs_level_id > 0) {
								$id = $levelObject->akeebasubs_level_id;
							}
						}
						if(!is_null($id)) $masterlevels[] = $id;
					}
				}
				$this->levels = implode(',', $masterlevels);
			}
		} else {
			$this->levels = '';
		}

		return $result;
	}
}