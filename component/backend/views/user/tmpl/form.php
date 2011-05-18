<? defined('KOOWA') or die('Restricted access'); ?>

<?= @helper('behavior.tooltip'); ?>
<?= @helper('behavior.mootools'); ?>
<?= @helper('behavior.modal'); ?>
<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<form action="<?= @route('id='.$user->id) ?>" method="post" class="adminform" name="adminForm">
<input type="hidden" name="_visual" value="1" />

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?=@text('COM_AKEEBASUBS_USER_BASIC_TITLE')?></legend>

	<label for="userid_visible" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_USERNAME')?></label>
	<input type="hidden" name="user_id" id="userid" value="<?=$user->user_id?>" />
	<input type="text" name="xxx_userid" id="userid_visible" value="<?=JFactory::getUser(empty($user->user_id) ? 0 : $user->user_id)->username?>" disabled="disabled" />
	<button onclick="$('userselect').fireEvent('click'); return false;">Select</button>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_akeebasubs&view=jusers&tmpl=component" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<br/>
	
	<br/>
	
	<label for="address1" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_ADDRESS1')?></label>
	<input type="text" name="address1" id="address1" class="longer" value="<?=@escape($user->address1)?>" />
	<br/>
	
	<label for="address2" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_ADDRESS2')?></label>
	<input type="text" name="address2" id="address2" class="longer" value="<?=@escape($user->address2)?>" />
	<br/>
	
	<label for="city" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_CITY')?></label>
	<input type="text" name="city" id="city" class="longer" value="<?=@escape($user->city)?>" />
	<br/>
	
	<label for="zip" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_ZIP')?></label>
	<input type="text" name="zip" id="zip" value="<?=@escape($user->zip)?>" />
	<br/>
	
	<label for="state" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_STATE')?></label>
	<?=@helper('admin::com.akeebasubs.template.helper.listbox.states', array('name' => 'state', 'selected' => ( $user->state ) ))?>
	<br/>
	
	<label for="state" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_COUNTRY')?></label>
	<?=@helper('admin::com.akeebasubs.template.helper.listbox.countries', array('name' => 'country', 'selected' => ( $user->country ) ))?>
	<br/>

<!-- hits -->
	
</fieldset>

<fieldset id="coupons-finetuning" style="width: 48%; float: left;">
	<legend><?=@text('COM_AKEEBASUBS_USER_BASIC_BUSINESS')?></legend>

	<label for="isbusiness" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_ISBUSINESS')?></label>
	<?= @helper('select.booleanlist', array('name' => 'isbusiness', 'selected' => $user->isbusiness)); ?>
	<br/>
	
	<label for="businessname" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_BUSINESSNAME')?></label>
	<input type="text" name="businessname" id="businessname" class="longer" value="<?=@escape($user->businessname)?>" />
	<br/>
	
	<label for="occupation" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_OCCUPATION')?></label>
	<input type="text" name="occupation" id="occupation" class="longer" value="<?=@escape($user->occupation)?>" />
	<br/>
	
	<label for="vatnumber" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_VATNUMBER')?></label>
	<input type="text" name="vatnumber" id="vatnumber" class="longer" value="<?=@escape($user->vatnumber)?>" />
	<br/>
	
	<label for="viesregistered" class="main"><?=@text('COM_AKEEBASUBS_USERS_FIELD_VIESREGISTERED')?></label>
	<?= @helper('select.booleanlist', array('name' => 'viesregistered', 'selected' => $user->viesregistered)); ?>
	<br/>
	
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