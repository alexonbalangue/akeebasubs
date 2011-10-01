<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsTemplateHelperGrid extends KTemplateHelperGrid
{

	public function paykey($config = array())
	{
	    $config = new KConfig($config);
		$config->append(array(
			'search' => null
		));
	    
	    $html = '<input name="paykey" id="paykey" value="'.$config->search.'" />';
        $html .= '<button>'.JText::_('Go').'</button>';
		$html .= '<button onclick="document.getElementById(\'paykey\').value=\'\';this.form.submit();">'.JText::_('Reset').'</button>';
	
	    return $html;
	}
}