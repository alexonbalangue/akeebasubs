<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

/** @var \Akeeba\Subscriptions\Site\View\Subscribe\Html $this */

defined('_JEXEC') or die();

use \Akeeba\Subscriptions\Admin\Helper\ComponentParams;

$this->addJavascriptFile('media://com_akeebasubs/js/autosubmit.js');

?>
<?php if(ComponentParams::getParam('stepsbar',1)):?>
<?php echo $this->loadAnyTemplate('site:com_akeebasubs/Level/steps',array('step'=>'payment')); ?>
<?php endif; ?>

<?php echo $this->form ?>