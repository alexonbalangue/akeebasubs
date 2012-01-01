<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>
<div id="akeebasubs-steps">
	<div id="akeebasubs-steps-header">
		<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIBE_STEPHEADER');?>
	</div>
	<div id="akeebasubs-steps-bar">
		<span id="akeebasubs-steps-subscribe" class="step <?php echo $step == 'subscribe' ? 'active' : ''?>">
			<span class="numbers">1</span>
			<span class="text"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIBE_STEP_SUBSCRIBE')?></span>
		</span>
		<span id="akeebasubs-steps-payment" class="step <?php echo $step == 'payment' ? 'active' : ''?>">
			<span class="numbers">2</span>
			<span class="text"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIBE_STEP_PAYMENT')?></span>
		</span>
		<span id="akeebasubs-steps-done" class="step <?php echo $step == 'done' ? 'active' : ''?>">
			<span class="numbers">3</span>
			<span class="text"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIBE_STEP_DONE')?></span>
		</span>
	</div>
</div>