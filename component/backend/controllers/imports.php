<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die;

class AkeebasubsControllerImports extends FOFController
{
	public function import()
	{
		$app       = JFactory::getApplication();
		$model     = FOFModel::getTmpInstance('Imports', 'AkeebasubsModel');
		$file      = JRequest::getVar('csvfile', '', 'FILES');
		$delimiter = $this->input->getInt('csvdelimiters', 0);

		if($file['error'])
		{
			$this->setRedirect('index.php?option=com_akeebasubs&view=import', JText::_('COM_AKEEBASUBS_IMPORT_ERR_UPLOAD'), 'error');
			return true;
		}

		// Import ok, but maybe I have warnings (ie skipped lines)
		$result = $model->import($file['tmp_name'], $delimiter);
		if($result !== false)
		{
			$errors = $model->getErrors();
			if($errors)
			{
				$app->enqueueMessage(JText::_('COM_AKEEBASUBS_IMPORT_WITH_WARNINGS'), 'notice');

				foreach($errors as $error)
				{
					$app->enqueueMessage($error, 'notice');
				}
			}
			else
			{
				// Import ok, congrat with yourself!
				$app->enqueueMessage(JText::sprintf('COM_AKEEBASUBS_IMPORT_OK', $result));
			}
		}
		else
		{
			//Uh oh... import failed, let's inform the user why it happened
			$app->enqueueMessage(JText::sprintf('COM_AKEEBASUBS_IMPORT_FAIL', $model->getError()), 'error');
		}

		$this->setRedirect('index.php?option=com_akeebasubs&view=users');
	}
}
