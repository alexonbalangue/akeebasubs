<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerTaxconfigs extends FOFController
{
	public function execute($task) {
		if(!in_array($task, array('main','apply'))) {
			$task = 'main';
		}
		parent::execute($task);
	}

	public function main($cachable = false, $urlparams = false)
	{
		parent::display($cachable, $urlparams);
	}

	public function apply()
	{
		// CSRF protection
		$this->_csrfProtection();

		$model = $this->getThisModel();
		$model->clearTaxRules();
		$model->createTaxRules();
		$model->applyComponentConfiguration();

		// Redirect back to the control panel
		$url = '';
		$returnurl = $this->input->getBase64('returnurl', '');
		if(!empty($returnurl)) {
			$url = base64_decode($returnurl);
		}
		if(empty($url)) {
			$url = JURI::base().'index.php?option=com_akeebasubs';
		}
		$this->setRedirect($url, JText::_('COM_AKEEBASUBS_TAXCONFIGS_MSG_APPLIED'));
	}
}