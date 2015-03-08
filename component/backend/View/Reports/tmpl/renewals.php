<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

/** @var \FOF30\View\DataView\Form $this */

defined('_JEXEC') or die;

/** @var \Akeeba\Subscriptions\Admin\Model\RenewalsForReports $model */
$model = $this->getModel('RenewalsForReports');

// Since I'm manually handling the model, I have to manually set View params, too
$this->lists->order = $model->getState('filter_order', 'id', 'cmd');
$this->lists->order_dir = $model->getState('filter_order_Dir', 'DESC', 'cmd');

$viewTemplate = $this->getRenderedForm();

// Injecting a new input field doing a string replace. Bad bad programmer!
$viewTemplate = str_replace('</form>', '<input type="hidden" name="layout" value="renewals" /></form>', $viewTemplate);

echo $viewTemplate;