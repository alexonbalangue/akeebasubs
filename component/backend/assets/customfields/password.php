<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

require_once __DIR__.'/abstract.php';
require_once __DIR__.'/text.php';

/**
 * A password input field
 * 
 * @author Nicholas K. Dionysopoulos
 * @since 2.6.0
 */
class AkeebasubsCustomFieldPassword extends AkeebasubsCustomFieldText
{
	public function __construct(array $config = array()) {
		parent::__construct($config);
		
		$this->input_type = 'password';
	}
}