<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Controller\Controller;

class Reports extends Controller
{
	use Mixin\PredefinedTaskList;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->registerTask('overview', 'display');
		$this->registerTask('vies', 'invoices');
		$this->registerTask('vatmoss', 'invoices');

		$this->setPredefinedTaskList(['overview', 'invoices', 'vies', 'vatmoss']);
		$this->cacheableTasks = array();
	}

	public function invoices()
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\Reports $model */
		$model = $this->getModel();
		$model->layout = 'invoices';

		$model->setState('task', $this->getTask());

		// Assign the records and the layout to the view
		$view = $this->getView();
		$view->records = $model->getInvoices();
		$view->params = $model->getInvoiceListParameters();

		$this->layout = $model->layout;

		// Show the view
		$this->display(false);
	}
}