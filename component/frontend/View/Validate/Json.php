<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Validate;

defined('_JEXEC') or die;

class Json extends \FOF30\View\DataView\Json
{
    protected function onBeforeGetpayment($tpl = null)
    {
        $this->setLayout('paymentlist');

        $result = $this->loadTemplate($tpl, true);

        echo $result;
    }
}