<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerApicoupons extends FOFController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('create', 'read');
	}
}