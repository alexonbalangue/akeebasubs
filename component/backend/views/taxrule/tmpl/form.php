<? defined('KOOWA') or die('Restricted access'); ?>

<?= @helper('behavior.tooltip'); ?>
<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<form action="<?= @route('id='.$taxrule->id) ?>" method="post" class="-koowa-form">
<input type="hidden" name="_visual" value="1" />

	<label for="country" class="main"><?= @text('COM_AKEEBASUBS_TAXRULES_COUNTRY'); ?></label>
	<?=@helper('com://admin/akeebasubs.template.helper.listbox.countries', array('name' => 'country', 'selected' => $taxrule->country) ) ?>
	<br/>
	
	<label for="state" class="main"><?= @text('COM_AKEEBASUBS_TAXRULES_STATE'); ?></label>
	<?=@helper('com://admin/akeebasubs.template.helper.listbox.states', array('name' => 'state', 'selected' => $taxrule->state) ) ?>
	<br/>

	<label for="city" class="main"><?= @text('COM_AKEEBASUBS_TAXRULES_CITY'); ?></label>
	<input type="text" name="city" id="city" value="<?=$taxrule->city?>" />
	<br/>

	<label for="vies" class="main" class="main"><?= @text('COM_AKEEBASUBS_TAXRULES_VIES'); ?></label>
	<?= @helper('select.booleanlist', array('name' => 'vies', 'selected' => $taxrule->vies)); ?>
	<br/>

	<label for="taxrate" class="main"><?= @text('COM_AKEEBASUBS_TAXRULES_TAXRATE'); ?></label>
	<input type="text" name="taxrate" id="taxrate" value="<?=$taxrule->taxrate?>" /> <strong>%</strong>
	<br/>

	<label for="enabled" class="main" class="main"><?= @text('enabled'); ?></label>
	<?= @helper('select.booleanlist', array('name' => 'enabled', 'selected' => $taxrule->enabled)); ?>

</form>