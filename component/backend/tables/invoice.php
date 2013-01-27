<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableInvoice extends FOFTable
{
	function __construct( $table, $key, &$db )
	{
		$table = '#__akeebasubs_invoices';
		$key = 'akeebasubs_subscription_id';
		parent::__construct($table, $key, $db);
	}
}