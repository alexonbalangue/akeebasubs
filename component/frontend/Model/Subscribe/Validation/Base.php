<?php
/**
 * Created by PhpStorm.
 * User: nikosdion
 * Date: 24/4/15
 * Time: 11:15
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;


use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use Akeeba\Subscriptions\Site\Model\Subscribe\ValidatorFactory;
use FOF30\Container\Container;

abstract class Base
{
	/** @var  Container  The Container of the component */
	protected $container = null;

	/** @var  StateData  The state data we're operating on */
	protected $state = null;

	/** @var  ValidatorFactory  The validator factory */
	protected $factory = null;

	/** @var  mixed  The (cached) result of this validation class */
	protected static $result = null;

	/**
	 * Public constructor
	 *
	 * @param   Container         $container  The container of the component
	 * @param   StateData         $state      State data to operate on
	 * @param   ValidatorFactory  $factory    The validator factory
	 */
	public function __construct(Container $container, StateData $state, ValidatorFactory $factory)
	{
		$this->container = $container;
		$this->state = $state;
		$this->factory = $factory;
	}

	/**
	 * Resets the validation results. The next run will return fresh validation results.
	 *
	 * @return  $this  for chaining
	 */
	public function reset()
	{
		self::$result = null;

		return $this;
	}

	/**
	 * Gets the (cached) validation results
	 *
	 * @param   bool  $force  When true we reset() before returning the validation results.
	 *
	 * @return  mixed  The (cached) validation results
	 */
	public function execute($force = false)
	{
		if ($force)
		{
			$this->reset();
		}

		if (is_null(self::$result))
		{
			self::$result = $this->getValidationResult();
		}

		return self::$result;
	}

	/**
	 * Get the validation result
	 *
	 * @return  mixed  The validation result. Do not store it to self::$result, let execute() handle it.
	 */
	abstract protected function getValidationResult();
}