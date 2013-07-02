<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableAffiliate extends FOFTable
{
	/** @var   float  Required for the browse view */
	public $owed = 0;

	/** @var   float  Required for the browse view */
	public $paid = 0;
}