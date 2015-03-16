<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase;
use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;

class plgAkeebasubsSlavesubs extends AkeebasubsBase
{
	/**
	 * Maximum slave subscriptions per subscription level
	 *
	 * @var  array
	 */
	private $maxSlaves = array();

	/**
	 * Used to prevent firing this plugin when we're making changes to subscriptions
	 *
	 * @var bool
	 */
	private static $dontFire = false;

	public function __construct(&$subject, $name, $config = array())
	{
		parent::__construct($subject, $name, $config);

		$this->loadLanguage();

		$this->loadLevelAssignments();
	}

	/**
	 * Renders the configuration page in the component's back-end
	 *
	 * @param   Levels  $level  The subscription level
	 *
	 * @return  stdClass  Definition object, with two properties: 'title' and 'html'
	 */
	public function onSubscriptionLevelFormRender(Levels $level)
	{
		$filePath = 'plugin://akeebasubs/' . $this->name . '/default.php';
		$filename = $this->container->template->parsePath($filePath, true);

		$params = $level->params;

		if (!isset($params['slavesubs_maxSlaves']))
		{
			$params['slavesubs_maxSlaves'] = 0;
		}

		$level->params = $params;

		@ob_start();

		include_once $filename;

		$html = @ob_get_clean();

		$ret = (object) array(
			'title' => JText::_('PLG_AKEEBASUBS_SLAVESUBS_TAB_TITLE'),
			'html'  => $html
		);

		return $ret;
	}

	/**
	 * Renders custom fields in the form, allowing the subscriber to enter the
	 * dependent users
	 *
	 * @param  array $cache
	 *
	 * @return  array  The custom fields definitions
	 */
	public function onSubscriptionFormRenderPerSubFields($cache)
	{
		$fields = array();

		// Make sure we have a level
		if (!array_key_exists('subscriptionlevel', $cache))
		{
			return $fields;
		}

		// Make sure this level supports slave subscriptions
		$level = $cache['subscriptionlevel'];

		if (!array_key_exists($level, $this->maxSlaves))
		{
			return $fields;
		}

		$maxSlaves = $this->maxSlaves[ $level ];

		if ($maxSlaves <= 0)
		{
			return $fields;
		}

		JLoader::import('joomla.user.helper');

		$javascript_fetch    = '';
		$javascript_validate = '';

		if (!isset($cache['useredit']) || !$cache['useredit'])
		{
			$userEdit = false;
		}
		else
		{
			$userEdit = $cache['useredit'];
		}

		for ($i = 0; $i < $maxSlaves; $i ++)
		{
			if (array_key_exists('slaveusers', $cache['subcustom']))
			{
				$allSlaves = $cache['subcustom']['slaveusers'];
			}
			else
			{
				$allSlaves = array();
			}

			if (array_key_exists($i, $allSlaves))
			{
				$current = $allSlaves[ $i ];
			}
			else
			{
				$current = '';
			}

			$html = '<input type="text" class="slaves" name="subcustom[slaveusers][' . $i . ']" id="slaveuser' . $i . '" value="' . htmlentities($current) . '" />';

			$userExists = false;

			if (!empty($current))
			{
				$userExists = JUserHelper::getUserId($current) > 0;
			}

			// Setup the field
			$field = array(
				'id'              => 'slaveuser' . $i,
				'label'           => JText::sprintf('PLG_AKEEBASUBS_SLAVESUBS_ADDONUSER_LBL', $i + 1),
				'elementHTML'     => $html,
				'invalidLabel'    => JText::_('PLG_AKEEBASUBS_SLAVESUBS_INVALID_LBL'),
				'isValid'         => empty($current) || $userExists,
				'validationClass' => 'slavesubsValidation'
			);

			// Add the field to the return output
			$fields[] = $field;

			// Add Javascript
			$javascript_fetch .= <<< JS
result.slaveusers[$i] = $('#slaveuser$i').val();

JS;
			$javascript_validate .= <<< JS

$('#slaveuser$i').parents('div.control-group').removeClass('error has-error success has-success');
if(!response.subcustom_validation.slaveuser$i) {
	$('#slaveuser$i').parents('div.control-group').addClass('error has-error');
	$('#slaveuser{$i}_invalid').css('display','inline-block');
	thisIsValid = false;
} else {
	$('#slaveuser$i').parents('div.control-group').removeClass('error has-error');
	$('#slaveuser{$i}_invalid').css('display','none');
	thisIsValid = true;
}

JS;
		}

		if (!$userEdit)
		{
			$javascript = <<< JS

;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
(function($) {
	$(document).ready(function(){
		addToSubValidationFetchQueue(plg_akeebasubs_slavesubs_fetch);
		addToSubValidationQueue(plg_akeebasubs_slavesubs_validate);
	});
})(akeeba.jQuery);

function plg_akeebasubs_slavesubs_fetch()
{
	var result = {
		slaveusers: {}
	};

	(function($) {
$javascript_fetch
	})(akeeba.jQuery);

	return result;
}

function plg_akeebasubs_slavesubs_validate(response)
{
	var thisIsValid = true;
	(function($) {
$javascript_validate

	})(akeeba.jQuery);

	return thisIsValid;
}

JS;
			$document   = JFactory::getDocument();
			$document->addScriptDeclaration($javascript);
		}
		else
		{
			\Akeeba\Subscriptions\Admin\Helper\Validator::addSelector('input.slaves');
		}

		return $fields;
	}

	/**
	 * Performs validation of the custom fields, i.e. check that a valid
	 * username (or no username) is set on each one of them.
	 *
	 * @param   object  $data
	 *
	 * @return  array  subscription_custom_validation, valid
	 */
	public function onValidatePerSubscription($data)
	{
		// Initialise the validation respone
		$response = array(
			'valid'                          => true,
			'subscription_custom_validation' => array()
		);

		// Make sure we have a subscription level ID
		if ($data->id <= 0)
		{
			return $response;
		}

		// Fetch the custom data
		$subcustom = $data->subcustom;

		if (!array_key_exists($data->id, $this->maxSlaves))
		{
			return $response;
		}

		$maxSlaves = $this->maxSlaves[ $data->id ];

		if ($maxSlaves <= 0)
		{
			return $response;
		}

		if (!array_key_exists('slaveusers', $subcustom))
		{
			return $response;
		}

		JLoader::import('joomla.user.helper');

		for ($i = 0; $i < $maxSlaves; $i ++)
		{
			if (!array_key_exists($i, $subcustom['slaveusers']))
			{
				$response['subscription_custom_validation'][ 'slaveuser' . $i ] = true;
				continue;
			}

			$current = $subcustom['slaveusers'][ $i ];

			if (empty($current))
			{
				$response['subscription_custom_validation'][ 'slaveuser' . $i ] = true;
			}
			elseif ($current == JFactory::getUser()->username)
			{
				$response['subscription_custom_validation'][ 'slaveuser' . $i ] = false;
			}
			elseif ($current == $data->username)
			{
				$response['subscription_custom_validation'][ 'slaveuser' . $i ] = false;
			}
			else
			{
				$response['subscription_custom_validation'][ 'slaveuser' . $i ] = JUserHelper::getUserId($current) > 0;
			}

			$response['valid'] = $response['valid'] &&
			                     $response['subscription_custom_validation'][ 'slaveuser' . $i ];
		}

		return $response;
	}

	/**
	 * This is called whenever a new subscription is created or an existing
	 * subscription is modified. We are using it to create slave subscriptions
	 * where necessary and "mirror" the parameters of the master subscription
	 * to the slave subscriptions when slave subscriptions already exist.
	 *
	 * @param   Subscriptions  $row   The subscription which we act upon
	 * @param   array          $info  Update information
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if (self::$dontFire)
		{
			return;
		}

		// Get the parameters of the row
		$params = $row->params;

		// No params? No need to check anything else!
		if (empty($params))
		{
			return;
		}

		// Nothing in the params array? No need to check anything else!
		if (empty($params))
		{
			return;
		}

		// Create new slave subscriptions if the subscription level allows us to
		if (!isset($this->maxSlaves[ $row->akeebasubs_level_id ]))
		{
			$this->maxSlaves[ $row->akeebasubs_level_id ] = 0;
		}

		// Do we have slave users at all?
		if (!array_key_exists('slaveusers', $params))
		{
			return;
		}

		JLoader::import('joomla.user.helper');

		$slavesubs_ids = array();
		$data          = $row instanceof Subscriptions ? $row->toArray() : (array) $row;

		// Let's look inside modified fields, is this a new slave, a removed one or I'm just renewing his subscription?
		// Simply create new subscription, the user specified slaves while creating his subscription
		if ($info['status'] == 'new')
		{
			$slaveusers = $params['slaveusers'];

			// Do we have at least one slave user?
			if (empty($slaveusers))
			{
				return;
			}

			// Create new slave subscriptions
			JLoader::import('joomla.user.helper');
			$slavesubs_ids = array();

			if ($row instanceof Subscriptions)
			{
				$data = $row->toArray();
			}
			else
			{
				$data = (array) $row;
			}

			foreach ($slaveusers as $slaveUsername)
			{
				if (empty($slaveUsername))
				{
					continue;
				}

				$user_id = JUserHelper::getUserId($slaveUsername);

				//double check that the user is valid
				if ($user_id <= 0)
				{
					continue;
				}

				// Save the new subscription record
				$result          = $this->createSlaveSub($slaveUsername, $data, $params);
				$slavesubs_ids[] = $result;
			}

			$params['slavesubs_ids'] = $slavesubs_ids;

			$newdata = array_merge($data, array(
				'params'      => $params,
				'_dontNotify' => true,
			));

			/** @var Subscriptions $table */
			$table     = $this->container->factory->model('Subscriptions')->tmpInstance();

			self::$dontFire = true;
			$table->save($newdata);
			self::$dontFire = false;
		}
		// Modified subscription, let's figure out what to do with slave subscriptions
		else
		{
			$current  = $params;
			$previous = $info['previous']->params;

			if (!isset($previous['slaveusers']) || empty($previous['slaveusers']))
			{
				$previous['slaveusers'] = array();
			}

			// Let's get the full list of involved people
			$list = array_merge($current['slaveusers'], $previous['slaveusers']);

			$dirty = false;

			foreach ($list as $slave)
			{
				if (empty($slave))
				{
					continue;
				}

				$result = false;

				if (in_array($slave, $previous['slaveusers']) && in_array($slave, $current['slaveusers']))
				{
					// Slave is still here, just sync with the parent subscription
					$index = array_search($slave, $previous['slaveusers']);

					if (isset($previous['slavesubs_ids'][ $index ]))
					{
						//we have a valid slave so copy from parent to slave
						$result = $this->copySubscriptionInformation($row, $previous['slavesubs_ids'][ $index ]);
						$dirty  = true;
					}
				}
				elseif (in_array($slave, $current['slaveusers']) && !in_array($slave, $previous['slaveusers']))
				{
					// Added user, create a new subscription for him
					$result = $this->createSlaveSub($slave, $data, $params);
					$dirty  = true;
				}
				elseif (!in_array($slave, $current['slaveusers']) && in_array($slave, $previous['slaveusers']))
				{
					// Before he was active, now it's no more; let's fire this slave (aka expire his subscription)
					$index = array_search($slave, $previous['slaveusers']);

					if (isset($previous['slavesubs_ids'][ $index ]))
					{
						$this->expireSlaveSub($previous['slavesubs_ids'][ $index ]);
						$dirty = true;
					}
				}

				if ($result)
				{
					$slavesubs_ids[] = $result;
				}
			}

			/*
			// Do not try to save the subscription unless we made a change in slave subscribers
			if (!$dirty)
			{
				return;
			}
			*/

			$params['slavesubs_ids'] = $slavesubs_ids;
			$newdata                 = array_merge($data, array('params' => $params, '_dontNotify' => true));

			/** @var Subscriptions $table */
			$table     = $this->container->factory->model('Subscriptions')->tmpInstance();

			self::$dontFire = true;
			$table->save($newdata);
			self::$dontFire = false;
		}
	}

	/**
	 * This is called once per user whenever the admin uses the Run the Integrations
	 * button in the back-end. We loop the user's subscriptions and run
	 * onAKSubscriptionChange on them.
	 *
	 * @param   int  $user_id  The user ID we're acting upon
	 */
	public function onAKUserRefresh($user_id)
	{
		// Get all of the user's subscriptions
		/** @var Subscriptions $subsModel */
		$subsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
		$subscriptions = $subsModel
			->user_id($user_id)
			->get(true);

		$info = array();

		/** @var Subscriptions $row */
		foreach ($subscriptions as $row)
		{
			$this->onAKSubscriptionChange($row, $info);
		}
	}

	/**
	 * This is called whenever a new slave subscription is created.
	 * We are using it to create slave subscriptions where necessary
	 * and "mirror" the parameters of the master subscription
	 *
	 * @param   string  $username  The Slave User which we create a subscription for
	 * @param   array   $data      Information from the master subscription
	 * @param   array   $params    Parameters from the master subscription with current modifications
	 *
	 * @return  int  The new subscription ID
	 */
	private function createSlaveSub($username, $data, $params)
	{
		$user_id = JUserHelper::getUserId($username);

		if ($user_id <= 0)
		{
			return false;
		}

		if (isset($params['slavesubs_ids']))
		{
			unset($params['slavesubs_ids']);
		}

		if (isset($params['slaveusers']))
		{
			unset($params['slaveusers']);
		}

		//store the ID of the parent subscription
		$parentsub_id           = $data ['akeebasubs_subscription_id'];
		$params['parentsub_id'] = $parentsub_id;

		$newdata                = array_merge($data, [
			'params' => $params,
			'_dontNotify' => true
		]);

		$newdata = array_merge($data, array(
			'akeebasubs_subscription_id' => 0,
			'user_id'                    => $user_id,
			'net_amount'                 => 0,
			'tax_amount'                 => 0,
			'gross_amount'               => 0,
			'tax_percent'                => 0,
			'params'                     => $params,
			'akeebasubs_coupon_id'       => 0,
			'akeebasubs_upgrade_id'      => 0,
			'akeebasubs_affiliate_id'    => 0,
			'affiliate_comission'        => 0,
			'prediscount_amount'         => 0,
			'discount_amount'            => 0,
			'contact_flag'               => 0,
		));

		// Save the new subscription record
		/** @var Subscriptions $table */
		$table = $this->container->factory->model('Subscriptions')->tmpInstance();
		$table->akeebasubs_subscription_id = 0;

		self::$dontFire = true;
		$table->save($newdata);
		self::$dontFire = false;

		return $table->akeebasubs_subscription_id;
	}

	/**
	 * This is called whenever a slave subscription is expired.
	 *
	 * @param   int  $subId  The Slave subscription ID which we are expiring
	 *
	 */
	private function expireSlaveSub($subId)
	{
		self::$dontFire = true;

		/** @var Subscriptions $table */
		$table = $this->container->factory->model('Subscriptions')->tmpInstance();

		// Set expiration one minute before, so it will be automatically unpublished
		$expire = $this->container->platform->getDate('-1 minutes');
		$data   = array('publish_down' => $expire->toSql());
		$table->save($data);

		self::$dontFire = false;
	}

	/**
	 * Copies the subscription information from row $from to $to.
	 *
	 * @param   Subscriptions  $from  Row to copy from
	 * @param   Subscriptions  $to    Row to copy to
	 *
	 * @return  Subscriptions
	 */
	private function copySubscriptionInformation($from, &$to)
	{
		$forbiddenProperties = array(
			'akeebasubs_subscription_id',
			'user_id',
			'net_amount',
			'tax_amount',
			'gross_amount',
			'tax_percent',
			'akeebasubs_coupon_id',
			'akeebasubs_upgrade_id',
			'akeebasubs_affiliate_id',
			'affiliate_comission',
			'prediscount_amount',
			'discount_amount',
			'contact_flag'
		);

		$asArray = $from->toArray();
		$properties = array_keys($asArray);

		foreach ($properties as $k => $v)
		{
			// Do not copy forbidden properties
			if (in_array($k, $forbiddenProperties))
			{
				continue;
			}
			// Special handling for params
			elseif ($k == 'params')
			{
				$params = $from->params;

				// Unset params that should not be copied from parent sub to child sub
				if (isset($params['slavesubs_ids']))
				{
					unset($params['slavesubs_ids']);
				}

				if (isset($params['slaveusers']))
				{
					unset($params['slaveusers']);
				}

				if (isset($params['parentsub_id']))
				{
					unset($params['parentsub_id']);
				}

				$to->params = $params;
			}
			// Copy over everything else
			else
			{
				$to->setFieldValue($k, $from->getFieldValue($k));

			}
		}

		// Return the subscription that was modified
		return $to;
	}

	/**
	 * Loads the maximum slave subscriptions assignments for each subscription
	 * level.
	 */
	private function loadLevelAssignments()
	{
		$this->maxSlaves = array();

		/** @var Levels $model */
		$model     = $this->container->factory->model('Levels')->tmpInstance();
		$levels    = $model->get(true);
		$slavesKey = 'slavesubs_maxSlaves';

		if ($levels->count())
		{
			foreach ($levels as $level)
			{
				if (isset($level->params[ $slavesKey ]))
				{
					$this->maxSlaves[ $level->akeebasubs_level_id ] = $level->params[ $slavesKey ];
				}
			}
		}
	}
}
