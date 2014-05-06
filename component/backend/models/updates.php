<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * The updates provisioning Model
 */
class AkeebasubsModelUpdates extends F0FUtilsUpdate
{
	/**
	 * Public constructor. Initialises the protected members as well.
	 *
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->updateSiteName = 'Akeeba Subscriptions';
		$this->updateSite = 'http://cdn.akeebabackup.com/updates/akeebasubs.xml';
	}
}