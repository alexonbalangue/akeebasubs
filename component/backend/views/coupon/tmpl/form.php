<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);

JHtml::_('behavior.tooltip');
JHtml::_('behavior.mootools');
JHtml::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('params');
?>
<form action="index.php" method="post" name="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="coupon" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="akeebasubs_coupon_id" value="<?php echo $this->item->akeebasubs_coupon_id ?>" />
<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_COUPON_BASIC_TITLE')?></legend>
	
	<label for="title_field" class="main title">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_TITLE'); ?>
	</label>
	<input type="text" size="30" id="title_field" name="title" class="title" value="<?php echo  $this->escape($this->item->title) ?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="coupon_field" class="main">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_COUPON'); ?>
	</label>
	<input type="text" size="25" id="coupon_field" name="coupon" value="<?php echo  $this->escape($this->item->coupon) ?>" />
	<div class="akeebasubs-clear"></div>

	<label for="type_field" class="main">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_TYPE'); ?>
	</label>
	<?php echo AkeebasubsHelperSelect::coupontypes('type',$this->item->type) ?>
	<div class="akeebasubs-clear"></div>

	<label for="value_field" class="main">
			<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_VALUE'); ?>
	</label>
	<input type="text" size="20" id="value_field" name="value" value="<?php echo  $this->escape($this->item->value) ?>" />
	<div class="akeebasubs-clear"></div>

	<label for="enabled" class="main" class="mainlabel">
		<?php echo JText::_('JPUBLISHED'); ?>
	</label>
	<span class="akeebasubs-booleangroup">
		<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	</span>
	<div class="akeebasubs-clear"></div>

	<label for="hits_field" class="main">
		<?php echo  JText::_('COM_AKEEBASUBS_COMMON_HITS'); ?>
	</label>
	<input type="text" size="5" id="hits_field" name="hits" value="<?php echo  $this->escape($this->item->hits) ?>" />
	<div class="akeebasubs-clear"></div>

</fieldset>

<fieldset id="coupons-finetuning" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_COUPON_FINETUNING_TITLE')?></legend>
	
	<label for="publish_up" class="main"><?php echo JText::_('COM_AKEEBASUBS_COUPON_PUBLISH_UP')?></label>
	<span class="akeebasubs-nofloat-input">
		<?php echo JHTML::_('calendar', $this->item->publish_up, 'publish_up', 'publish_up'); ?>
	</span>
	<div class="akeebasubs-clear"></div>

	<label for="publish_down" class="main"><?php echo JText::_('COM_AKEEBASUBS_COUPON_PUBLISH_DOWN')?></label>
	<span class="akeebasubs-nofloat-input">
		<?php echo JHTML::_('calendar', $this->item->publish_down, 'publish_down', 'publish_down'); ?>
	</span>
	<div class="akeebasubs-clear"></div>
	
	<label for="userid_visible" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
	<input type="hidden" name="user" id="userid" value="<?php echo $this->item->user?>" />
	<input type="text" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user ? JFactory::getUser($this->item->user)->username : '' ?>" disabled="disabled" />
	<button onclick="return false;" class="modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<div class="akeebasubs-clear"></div>

	<label for="usergroups_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_COUPON_FIELD_USERGROUPS'); ?></label>
	<?php echo AkeebasubsHelperSelect::usergroups('usergroups[]', empty($this->item->usergroups) ? '-1' : explode(',', $this->item->usergroups), array('multiple' => 'multiple', 'size' => 3)); ?>
	<br />

	<label for="subscriptions_field" class="main"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>
	<?php echo AkeebasubsHelperSelect::levels('subscriptions[]', empty($this->item->subscriptions) ? '-1' : explode(',',$this->item->subscriptions), array('multiple' => 'multiple', 'size' => 3)) ?>
	<br />
	
	<label for="hitslimit_field" class="main"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_HITSLIMIT'); ?></label>
	<input type="text" size="5" id="hitslimit_field" name="hitslimit" value="<?php echo  $this->escape($this->item->hitslimit) ?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="userhits_field" class="main">
		<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_USERHITSLIMIT'); ?>
	</label>
	<input type="text" size="5" id="userhits_field" name="userhits" value="<?php echo  $this->escape($this->item->userhits) ?>" />
	<div class="akeebasubs-clear"></div>


</fieldset>

<div class="akeebasubs-clear"></div>

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
			new Event(e).stop();
			SqueezeBox.fromElement($('userselect'), {
				parse: 'rel'
			});
		});
	});
});
</script>