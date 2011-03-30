<? defined('KOOWA') or die('Restricted access'); ?>

<?= @helper('behavior.tooltip'); ?>
<?= @helper('behavior.mootools'); ?>
<?= @helper('behavior.modal'); ?>

<!--  --
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://com_akeebasubs/css/backend.css" />
<!--  -->

<form action="<?= @route('id='.$subscription->id) ?>" method="post" class="adminform" name="adminForm">
<input type="hidden" name="_visual" value="1" />

	<fieldset id="subscriptions-basic">
		<legend><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_LBL_SUB')?></legend>
		
		<label for="levelid" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL')?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.levels', array('name' => 'akeebasubs_level_id', 'selected' => $subscription->akeebasubs_level_id) ) ?>
		<br/>
		
		<label for="userid_visible" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
		<input type="hidden" name="user_id" id="userid" value="<?=$subscription->user_id?>" />
		<input type="text" name="xxx_userid" id="userid_visible" value="<?=$subscription->username?>" disabled="disabled" />
		<button onclick="$('userselect').fireEvent('click'); return false;">Select</button>
		<a class="modal" style="display: none" id="userselect" href="index.php?option=com_akeebasubs&view=jusers&limit=10&tmpl=component&search=" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
		<br/>

		<label for="enabled" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_ENABLED')?></label>
		<?= @helper('select.booleanlist', array('name' => 'enabled', 'selected' => $subscription->enabled)); ?>
		<br/>

		<label for="publish_up" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_PUBLISH_UP')?></label>
		<?php echo JHTML::_('calendar', $subscription->publish_up, 'publish_up', 'publish_up'); ?>
		<br/>

		<label for="publish_down" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_PUBLISH_DOWN')?></label>
		<?php echo JHTML::_('calendar', $subscription->publish_down, 'publish_down', 'publish_down'); ?>
		<br/>
		
		<label for="notes" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_NOTES')?></label><br/>
		<textarea name="notes" id="notes" cols="40" rows="10" style="margin-left: 5em;"><?=$subscription->notes?></textarea>

	</fieldset>
	<fieldset>
		<legend><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_LBL_PAYMENT')?></legend>
		
		<label for="processor" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR')?></label>
		<input type="text" name="processor" id="processor" value="<?=$subscription->processor?>"/>
		<br/>

		<label for="processor_key" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR_KEY')?></label>
		<input type="text" name="processor_key" id="processor_key" value="<?=$subscription->processor_key?>"/>
		<br/>

		<label for="state" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_STATE')?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.paystates', array('name' => 'state', 'selected' => $subscription->state) ) ?>
		<br/>
		
		<label for="net_amount" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_NET_AMOUNT')?></label>
		<input type="text" name="net_amount" id="net_amount" value="<?=$subscription->net_amount?>"/>
		<br/>

		<label for="tax_amount" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_TAX_AMOUNT')?></label>
		<input type="text" name="tax_amount" id="tax_amount" value="<?=$subscription->tax_amount?>"/>
		<br/>
		
		<label for="gross_amount" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_GROSS_AMOUNT')?></label>
		<input type="text" name="gross_amount" id="gross_amount" value="<?=$subscription->gross_amount?>"/>
		<br/>
		
		<label for="created_on" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON')?></label>
		<?php echo JHTML::_('calendar', $subscription->created_on, 'created_on', 'created_on'); ?>
		<br/>
		
		<label for="params" class="main"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_PARAMS')?></label><br/>
		<textarea name="params" id="params" cols="40" rows="10" style="margin-left: 5em;"><?=$subscription->params?></textarea>		
	</fieldset>
</form>

<script type="text/javascript">
function jSelectUser(id, username)
{
	document.getElementById('userid').value = id;
	document.getElementById('userid_visible').value = username;
	document.getElementById('sbox-window').close();	
}
</script>