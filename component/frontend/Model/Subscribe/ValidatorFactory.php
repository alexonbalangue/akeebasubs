<?php
/**
 * Created by PhpStorm.
 * User: nikosdion
 * Date: 24/4/15
 * Time: 11:43
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Site\Model\Subscribe\Validation;
use FOF30\Container\Container;

class ValidatorFactory
{
	/** @var   array   Already created validator objects */
	private $validators = [];

	/** @var   Container  The container */
	private $container = null;

	/** @var   StateData  The state data from the submitted form / validation request */
	private $state = null;

	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container of the component
	 * @param   StateData  $state      The state data from the submitted form / validation request
	 */
	public function __construct(Container $container, StateData $state)
	{
		$this->container = $container;
		$this->state     = $state;
	}

	/**
	 * Gets a validator object by type. If you request the same object type again the same object will be returned.
	 *
	 * @param   string  $type  The validator type
	 *
	 * @return  Validation\Base
	 *
	 * @throws  \InvalidArgumentException  If the validator type is not found
	 */
	public function getValidator($type)
	{
		$className = '\\Akeeba\\Subscriptions\\Site\\Model\\Subscribe\\Validation\\' . ucfirst($type);

		if (!class_exists($className))
		{
			throw new \InvalidArgumentException;
		}

		if (!isset($this->validators[$type]))
		{
			$this->validators[$type] = new $className($this->container, $this->state, $this);
		}

		return $this->validators[$type];
	}

	/**
	 * Reset the validator manager. It expunges all validators from memory.
	 */
	public function reset()
	{
		$this->validators = [];
	}
}