<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

interface ComAkeebasubsDatabasesConvertersInterface
{
	/**
	 * Execute the convertion
	 */
	public function convert();

	/**
	 * Checks if the converter can convert
	 */
	public function canConvert();

	/**
	 * Gets the name of the converter
	 *
	 * Is used as an identifier for the JS and controller
	 *
	 * @return string
	 */
	public function getName();
}