<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */


class AkeebasubsControllerReports extends FOFController
{
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('getexpirations', 'browse');
		$this->registerTask('renewals', 'browse');
	}
}