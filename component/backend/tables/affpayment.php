<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableAffpayment extends FOFTable
{
	/** @var   float  Required for the browse view */
	public $user_id = 0;

	public function __construct($table, $key, &$db, $config = array())
	{
		parent::__construct($table, $key, $db, $config);

		$this->addKnownField('user_id', false);
	}
}