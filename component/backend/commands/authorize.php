<?php

/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsCommandAuthorize extends ComDefaultCommandAuthorize
{
	public function _controllerBeforeAdd(KCommandContext $context)
    {
        if (version_compare(JVERSION,'1.6.0','ge')) {
            $result = KFactory::get('lib.joomla.user')->authorise('core.create');
        } else {
            $result = KFactory::get('lib.joomla.user')->get('gid') > 22;
        }
        
        return $result;
    }
    
    public function _controllerBeforeEdit(KCommandContext $context)
    {
        if (version_compare(JVERSION,'1.6.0','ge')) {
            $result = KFactory::get('lib.joomla.user')->authorise('core.edit');
        } else {
            $result = KFactory::get('lib.joomla.user')->get('gid') > 22;
        }
          
        return $result;
    }
    
    public function _controllerBeforeDelete(KCommandContext $context)
    {
        if (version_compare(JVERSION,'1.6.0','ge')) {
            $result = KFactory::get('lib.joomla.user')->authorise('core.delete');
        } else {
            $result = KFactory::get('lib.joomla.user')->get('gid') > 22;
        }
          
        return $result;
    }
}