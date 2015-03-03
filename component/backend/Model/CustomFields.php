<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Model\DataModel;

/**
 * Model for custom field defintions
 *
 * @property  int     $akeebasubs_customfield_id  Primary key
 * @property  string  $title          Field title
 * @property  string  $slug           Field alias
 * @property  string  $show           One of 'all', 'level', 'notlevel'
 * @property  array   $akeebasubs_level_id  The subscription levels where this field is show (show=level) or not show (show=notlevel)
 * @property  string  $type           Field type
 * @property  string  $options        Field options
 * @property  string  $default        Default value
 * @property  bool    $allow_empty    Should we allow empty values?
 * @property  string  $valid_label    Translation key to show next to a valid field
 * @property  string  $invalid_label  Translation key to show next to an invalid field
 * @property  string  $params         Field parameters
 */
class CustomFields extends DataModel
{
	use Mixin\Assertions, Mixin\ImplodedArrays;

	public function check()
	{
		$this->assertNotEmpty($this->slug, 'COM_AKEEBASUBS_ERR_SLUG_EMPTY');

		$pattern = '/^[a-z_][a-z0-9_\-]*$/';

		$this->assert(preg_match($pattern, $this->slug), 'COM_AKEEBASUBS_ERR_SLUG_INVALID');

		$this->slug = str_replace('-', '_', $this->slug);

		parent::check();
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string $value The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getAkeebasubsLevelIdAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of subscription levels into a comma separated list
	 *
	 * @param   array $value The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setAkeebasubsLevelIdAttribute($value)
	{
		if (!empty($value))
		{
			if (is_array($value))
			{
				$subs = $value;
			}
			else
			{
				$subs = explode(',', $value);
			}
			if (empty($subs))
			{
				$value = '';
			}
			else
			{
				$subscriptions = array();

				/** @var DataModel $levelModel */
				$levelModel = $this->container->factory
					->model('Levels')
					->setIgnoreRequest(true)->savestate(false);

				foreach ($subs as $id)
				{
					try
					{
						$levelModel->reset(true, true);
						$levelModel->findOrFail($id);
						$id = $levelModel->akeebasubs_level_id;
					}
					catch (\Exception $e)
					{
						$id = null;
					}


					if (!is_null($id))
					{
						$subscriptions[] = $id;
					}
				}

				$value = implode(',', $subscriptions);
			}
		}
		else
		{
			return '';
		}

		return $value;
	}
}