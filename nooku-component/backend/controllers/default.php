<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerDefault extends ComDefaultControllerDefault 
{
	public function _initialize(KConfig $config) {
		$config->append(array(
			'behaviors' => array('discoverable','executable','commandable','editable','validatable')
		));
		parent::_initialize($config);
	}
	
	public function _actionDelete(KCommandContext $context) {
		parent::_actionDelete($context);
		
		$error = $context->getError();
		if( $error instanceof KException ) {
			if($error->getCode() == 500) {
				$message = $error->getMessage();
				JFactory::getApplication()->enqueueMessage($message, 'error');
				$context->setError(null);
			}
		}
	}
}