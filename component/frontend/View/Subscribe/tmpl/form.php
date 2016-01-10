<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

/** @var \Akeeba\Subscriptions\Site\View\Subscribe\Html $this */

defined('_JEXEC') or die();

$this->addJavascriptFile('media://com_akeebasubs/js/autosubmit.js');

?>
<?php if($this->container->params->get('stepsbar',1)):?>
<?php echo $this->loadAnyTemplate('site:com_akeebasubs/Level/steps',array('step'=>'payment')); ?>
<?php endif; ?>

<?php echo $this->form ?>