<? defined('KOOWA') or die('Restricted access'); ?>

<?= @helper('behavior.tooltip'); ?>
<?= @helper('behavior.mootools'); ?>
<?= @helper('behavior.modal'); ?>
<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<form action="<?= @route('id='.$coupon->id) ?>" method="post" class="-koowa-form">
<input type="hidden" name="_visual" value="1" />

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?=@text('COM_AKEEBASUBS_COUPON_BASIC_TITLE')?></legend>
	
	<label for="title_field" class="main title"><?= @text('COM_AKEEBASUBS_COUPON_FIELD_TITLE'); ?></label>
	<input type="text" size="30" id="title_field" name="title" class="title" value="<?= @escape($coupon->title) ?>" />
	<br/>
	
	<label for="coupon_field" class="main"><?= @text('COM_AKEEBASUBS_COUPON_FIELD_COUPON'); ?></label>
	<input type="text" size="25" id="coupon_field" name="coupon" value="<?= @escape($coupon->coupon) ?>" />
	<br/>

	<label for="type_field" class="main"><?= @text('COM_AKEEBASUBS_COUPON_FIELD_TYPE'); ?></label>
	<?=@helper('admin::com.akeebasubs.template.helper.listbox.coupontypes', array('name' => 'type', 'selected' => $coupon->type, 'deselect' => false) ) ?>
	<br />					

	<label for="value_field" class="main"><?= @text('COM_AKEEBASUBS_COUPON_FIELD_VALUE'); ?></label>
	<input type="text" size="20" id="value_field" name="value" value="<?= @escape($coupon->value) ?>" />
	<br/>

	<label for="enabled" class="main" class="mainlabel"><?= @text('Published'); ?></label>
	<?= @helper('select.booleanlist', array('name' => 'enabled', 'selected' => $coupon->enabled)); ?>
	<br/>

	<label for="hits_field" class="main"><?= @text('hits'); ?></label>
	<input type="text" size="5" id="hits_field" name="hits" value="<?= @escape($coupon->hits) ?>" />
	<br/>

<!-- hits -->
	
</fieldset>

<fieldset id="coupons-finetuning" style="width: 48%; float: left;">
	<legend><?=@text('COM_AKEEBASUBS_COUPON_FINETUNING_TITLE')?></legend>
	
	<label for="publish_up" class="main"><?=@text('COM_AKEEBASUBS_COUPON_PUBLISH_UP')?></label>
	<?php echo JHTML::_('calendar', $coupon->publish_up, 'publish_up', 'publish_up'); ?>
	<br/>

	<label for="publish_down" class="main"><?=@text('COM_AKEEBASUBS_COUPON_PUBLISH_DOWN')?></label>
	<?php echo JHTML::_('calendar', $coupon->publish_down, 'publish_down', 'publish_down'); ?>
	<br/>
	
	<label for="userid_visible" class="main"><?=@text('COM_AKEEBASUBS_COUPON_FIELD_USER')?></label>
	<input type="hidden" name="user" id="userid" value="<?=$coupon->user?>" />
	<input type="text" name="xxx_userid" id="userid_visible" value="<?=JFactory::getUser(empty($coupon->user) ? 0 : $coupon->user)->username?>" disabled="disabled" />
	<button onclick="$('userselect').fireEvent('click'); return false;">Select</button>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_akeebasubs&view=jusers&tmpl=component" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<br/>

	<label for="subscriptions_field" class="main"><?= @text('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>
	<?=@helper('admin::com.akeebasubs.template.helper.listbox.levels', array('name' => 'subscriptions[]', 'selected' => empty($coupon->subscriptions) ? '-1' : explode(',',$coupon->subscriptions), 'deselect' => true, 'attribs' => array('multiple' => 'multiple', 'size' => 3) ) ) ?>
	<br />
	
	<label for="hitslimit_field" class="main"><?= @text('COM_AKEEBASUBS_COUPON_FIELD_HITSLIMIT'); ?></label>
	<input type="text" size="5" id="hitslimit_field" name="hitslimit" value="<?= @escape($coupon->hitslimit) ?>" />
	<br/>

<!-- hitslimit -->

</fieldset>

<div class="akeebasubs-clear"></div>

</form>

<script type="text/javascript">
function jSelectUser(id, username)
{
	document.getElementById('userid').value = id;
	document.getElementById('userid_visible').value = username;
	document.getElementById('sbox-window').close();	
}
</script>