<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die('');

interface AkeebasubsConverterInterface
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