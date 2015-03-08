<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;

/**
 * Model for automatic discounts for upgrading and renewing levels
 *
 * @method  $this  search() search(string $upgradeTitle)
 */
class Upgrades extends DataModel
{
	use Mixin\Assertions;

	/**
	 * Public constructor. Sets up the object.
	 *
	 * @param   \FOF30\Container\Container  $container
	 * @param   array                       $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');
	}

	/**
	 * Check if we are allowed to delete a record, i.e. if it's not used in a subscription
	 *
	 * @param   int  $oid  The id of the record we are going to delete
	 */
	protected function onBeforeDelete(&$oid)
	{
		$joins = array(
			array(
				'label'		=> 'subscriptions',			// Used to construct the error text
				'name'		=> '#__akeebasubs_subscriptions', // Foreign table
				'idfield'	=> 'akeebasubs_upgrade_id',	// Field name on this table
				'joinfield'	=> 'akeebasubs_upgrade_id',	// Foreign table field
				'idalias'	=> 'upgradeid',				// Used in the query
			)
		);

		$this->canDelete($oid, $joins);
	}

	/**
	 * Check the data for validity.
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws \RuntimeException  When the data bound to this record is invalid
	 */
	public function check()
	{

		$this->assertNotEmpty($this->title,   'COM_AKEEBASUBS_UPGRADE_ERR_TITLE');
		$this->assertNotEmpty($this->from_id, 'COM_AKEEBASUBS_UPGRADE_ERR_FROM_ID');
		$this->assertNotEmpty($this->to_id,   'COM_AKEEBASUBS_UPGRADE_ERR_TO_ID');
		$this->assertNotEmpty($this->type,    'COM_AKEEBASUBS_UPGRADE_ERR_TYPE');
		$this->assertNotEmpty($this->value,   'COM_AKEEBASUBS_UPGRADE_ERR_VALUE');

		if (empty($this->min_presence))
		{
			$this->min_presence = 0;
		}

		if (empty($this->max_presence))
		{
			$this->max_presence = 36500;
		}

		return $this;
	}

	/**
	 * Build the query to fetch data from the database
	 *
	 * @param   boolean $overrideLimits Should I override limits
	 *
	 * @return  \JDatabaseQuery  The database query to use
	 */
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);
		$db = $this->getDbo();

		$search = $this->getState('search', null);

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where(
				$db->qn('title') . ' LIKE ' . $db->q($search)
			);
		}

		return $query;
	}
}