<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHtml::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('params');
?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="coupon" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="akeebasubs_coupon_id" value="<?php echo $this->item->akeebasubs_coupon_id ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">
<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_COUPON_BASIC_TITLE')?></h3>
	
	<div class="control-group">
		<label for="title_field" class="control-label">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_TITLE'); ?>
		</label>
		<div class="controls">
			<input type="text" size="30" id="title_field" name="title" value="<?php echo  $this->escape($this->item->title) ?>" />
		</div>
	</div>
	<div class="control-group">
		<label for="coupon_field" class="control-label">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_COUPON'); ?>
		</label>
		<div class="controls">
			<input type="text" size="25" id="coupon_field" name="coupon" value="<?php echo  $this->escape($this->item->coupon) ?>" />
		</div>
	</div>
	<div class="control-group">
		<label for="type_field" class="control-label">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_TYPE'); ?>
		</label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::coupontypes('type',$this->item->type) ?>			
		</div>
	</div>
	<div class="control-group">
		<label for="value_field" class="control-label">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_VALUE'); ?>
		</label>
		<div class="controls">
			<input type="text" size="20" id="value_field" name="value" value="<?php echo  $this->escape($this->item->value) ?>" />
		</div>
	</div>
	<div class="control-group">
		<label for="enabled" class="control-label">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</div>
	</div>
	<div class="control-group">
		<label for="hits_field" class="control-label">
			<?php echo  JText::_('COM_AKEEBASUBS_COMMON_HITS'); ?>
		</label>
		<div class="controls">
			<input type="text" size="5" id="hits_field" name="hits" value="<?php echo  $this->escape($this->item->hits) ?>" />
		</div>
	</div>
	
</div>

<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_COUPON_FINETUNING_TITLE')?></h3>
	
	<div class="control-group">
		<label for="publish_up" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_COUPON_PUBLISH_UP')?></label>
		<div class="controls">
			<span class="akeebasubs-nofloat-input">
				<?php echo JHTML::_('calendar', $this->item->publish_up, 'publish_up', 'publish_up'); ?>
			</span>
		</div>
	</div>
	
	<div class="control-group">
		<label for="publish_down" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_COUPON_PUBLISH_DOWN')?></label>
		<div class="controls">
			<span class="akeebasubs-nofloat-input">
				<?php echo JHTML::_('calendar', $this->item->publish_down, 'publish_down', 'publish_down'); ?>
			</span>			
		</div>
	</div>
	
	<div class="control-group">
		<label for="userid_visible" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
		<div class="controls">
			<input type="hidden" name="user" id="userid" value="<?php echo $this->item->user?>" />
			<input type="text" class="input-medium" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user ? JFactory::getUser($this->item->user)->username : '' ?>" disabled="disabled" />
			<button onclick="return false;" class="btn btn-mini modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
			<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
		</div>
	</div>
	
	<div class="control-group">
		<label for="usergroups_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_USERGROUPS'); ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::usergroups('usergroups[]', empty($this->item->usergroups) ? '-1' : explode(',', $this->item->usergroups), array('multiple' => 'multiple', 'size' => 3)); ?>
		</div>
	</div>
	
	<div class="control-group">
		<label for="subscriptions_field" class="control-label"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::levels('subscriptions[]', empty($this->item->subscriptions) ? '-1' : explode(',',$this->item->subscriptions), array('multiple' => 'multiple', 'size' => 3)) ?>
		</div>
	</div>
	
	<div class="control-group">
		<label for="hitslimit_field" class="control-label"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_HITSLIMIT'); ?></label>
		<div class="controls">
			<input type="text" size="5" id="hitslimit_field" name="hitslimit" value="<?php echo  $this->escape($this->item->hitslimit) ?>" />
		</div>
	</div>
	
	<div class="control-group">
		<label for="userhits_field" class="control-label">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_USERHITSLIMIT'); ?>
		</label>
		<div class="controls">
			<input type="text" size="5" id="userhits_field" name="userhits" value="<?php echo  $this->escape($this->item->userhits) ?>" />
		</div>
	</div>
</div>
</div>
</form>
	
<script type="text/javascript">
function jSelectUser_userid(id, username)
{
	document.getElementById('userid').value = id;
	document.getElementById('userid_visible').value = username;
	try {
		document.getElementById('sbox-window').close();	
	} catch(err) {
		SqueezeBox.close();
	}
}
window.addEvent("domready", function() {
	$$("button.modal").each(function(el) {
		el.addEvent("click", function(e) {
			try {
				new Event(e).stop();
			} catch(anotherMTUpgradeIssue) {
				try {
					e.stop();
				} catch(WhateverIsWrongWithYouIDontCare) {
					try {
						DOMEvent(e).stop();
					} catch(NoBleepinWay) {
						alert('If you see this message, your copy of Joomla! is FUBAR');
					}
				}
			}
			SqueezeBox.fromElement($('userselect'), {
				parse: 'rel'
			});
		});
	});
});
</script>