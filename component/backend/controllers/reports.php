<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerReports extends FOFController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('renewals', 'browse');
	}
}
