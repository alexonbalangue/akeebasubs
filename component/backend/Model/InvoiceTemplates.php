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
use JDate;
use JLoader;
use JText;

class InvoiceTemplates extends DataModel
{
	use Mixin\Assertions, Mixin\DateManipulation, Mixin\ImplodedArrays, Mixin\ImplodedLevels;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');
	}

	/**
	 * If you pass a format request parameter to Joomla! it screws up the page load. So we have to use the request
	 * parameter localformat and map it back to format here.
	 *
	 * @param   mixed  $data
	 */
	protected function onBeforeBind(&$data)
	{
		if (empty($data))
		{
			return;
		}

		if (!is_array($data))
		{
			$data = (array)$data;
		}

		if (isset($data['localformat']))
		{
			$data['format'] = $data['localformat'];
		}
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
		$this->assertNotEmpty($this->title, 'COM_AKEEBASUBS_INVOICETEMPLATE_ERR_TITLE');

		return $this;
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getLevelsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of subscription levels into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setLevelsAttribute($value)
	{
		return $this->setAttributeForImplodedLevels($value);
	}

}