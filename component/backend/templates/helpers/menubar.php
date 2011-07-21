<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsTemplateHelperMenubar extends ComDefaultTemplateHelperMenubar
{
	/**
	* Render the menubar 
	*
	* @param array An optional array with configuration options
	* @return string Html
	*/
	public function render($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'menubar' => null
		));

		$html = '';
		$joomlaVersion = JVersion::isCompatible('1.6.0') ? '1.6' : '1.5';
		if ($joomlaVersion == '1.6') {
			$html = '<div id="submenu-box"><div class="t"><div class="t"><div class="t"></div></div></div><div class="m"><ul id="submenu">';
			foreach ($config->menubar->getCommands() as $command) 
			{
				$html .= '<li>';
				$html .= $this->command(array('command' => $command)); 
				$html .= '</li>'; 
			}
			$html .= '</ul><div class="clr"></div></div><div class="b"><div class="b"><div class="b"></div></div></div></div>';
		} else {
			$html = '<ul id="submenu">';
			foreach ($config->menubar->getCommands() as $command) 
			{
				$html .= '<li>';
				$html .= $this->command(array('command' => $command)); 
				$html .= '</li>'; 
			}
			$html .= '</ul>';
		}

		return $html;
	}
}