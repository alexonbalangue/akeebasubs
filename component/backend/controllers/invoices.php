<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerInvoices extends FOFController
{
	public function download($cachable = false, $urlparams = false)
	{
		// Load the model
		$model = $this->getThisModel();
		if(!$model->getId()) $model->setIDsFromRequest();
		
		// Make sure we have a valid item
		$item = $model->getItem();
		if (!is_object($item) || empty($item->akeebasubs_subscription_id))
		{
			return false;
		}
		
		// Check that this is the item's owner or an administrator
		$user = JFactory::getUser();
		$sub = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')->getItem($item->akeebasubs_subscription_id);
		if (!$this->checkACL('core.manage') || ($sub->user_id != $user->id))
		{
			return false;
		}
		
		// Make sure we have a PDF file or try to generate one
		jimport('joomla.filesystem.file');
		$path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/invoices/';
		$filename = $item->filename;
		
		if(!JFile::exists($path . $filename))
		{
			$filename = $model->createPDF();
			if ($filename == false)
			{
				return false;
			}
		}
		
		// Clear any existing data
		while (@ob_end_clean());
		
		// Fix IE bugs
		if (empty($item->display_number))
		{
			$basename = 'invoice_' . $item->invoice_no;
		}
		else
		{
			$basename = $item->display_number;
		}
		if (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			$header_file = preg_replace('/\./', '%2e', $filename, substr_count($basename, '.') - 1);

			if (ini_get('zlib.output_compression'))  {
				ini_set('zlib.output_compression', 'Off');
			}
		}
		else {
			$header_file = $basename;
		}
		
		// Get the PDF file's data
		@clearstatcache();
		$fileData = JFile::read($path . $filename);

		// Disable caching
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public", false);

		// Send MIME headers
		header("Content-Description: File Transfer");
		header('Content-Type: application/pdf');
		header("Accept-Ranges: bytes");
		header('Content-Disposition: attachment; filename="'.$header_file.'"');
		header('Content-Transfer-Encoding: binary');
		header('Connection: close');

		error_reporting(0);
		if ( ! ini_get('safe_mode') ) {
			set_time_limit(0);
		}
		
		echo $fileData;
		
		JFactory::getApplication()->close();
	}
	
	public function send($cachable = false, $urlparams = false)
	{
		// Load the model
		$model = $this->getThisModel();
		if(!$model->getId()) $model->setIDsFromRequest();
		
		// Make sure we have a valid item
		$item = $model->getItem();
		if (!is_object($item) || empty($item->akeebasubs_subscription_id))
		{
			return false;
		}
		
		// Check that this is an administrator
		if (!$this->checkACL('core.manage'))
		{
			return false;
		}
		
		// Email the PDF file
		$status = ($model->emailPDF() === true);
		
		// Post-action redirection
		if($customURL = $this->input->get('returnurl','','string')) $customURL = base64_decode($customURL);
		$url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.FOFInflector::pluralize($this->view);

		if(!$status)
		{
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_INVOICES_MSG_NOTSENT'), 'error');
		} else {
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_INVOICES_MSG_SENT') );
		}
	}
	
	public function generate($cachable = false, $urlparams = false)
	{
		// Load the model
		$model = $this->getThisModel();
		if(!$model->getId()) $model->setIDsFromRequest();
		
		// Make sure we have a valid item
		$item = $model->getItem();
		if (!is_object($item) || empty($item->akeebasubs_subscription_id))
		{
			return false;
		}
		
		// Check that this is an administrator
		if (!$this->checkACL('core.manage'))
		{
			return false;
		}
		
		// (Re-)generate the invoice
		$sub = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
			->getItem($item->akeebasubs_subscription_id);
		
		$status = ($model->createInvoice($sub) === true);
		
		// Post-action redirection
		if($customURL = $this->input->get('returnurl','','string')) $customURL = base64_decode($customURL);
		$url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.FOFInflector::pluralize($this->view);

		if($status === false)
		{
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_INVOICES_MSG_NOTGENERATED'), 'error');
		} else {
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_INVOICES_MSG_GENERATED') );
		}
	}
}