<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerDashboard extends ComDefaultControllerResource
{
    protected function _initialize(KConfig $config) 
    {
        $config->append(array(
            'request'	=> array('layout' => 'default'),
        ));

        parent::_initialize($config);
    }
	
	/**
	 * I am sure this is a stupid way to do it, but I digress.
	 * 
	 * @return bool
	 */
	public function isEditable()
	{
		return false;
	}
}