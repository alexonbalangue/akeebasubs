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
}