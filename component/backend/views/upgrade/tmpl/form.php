<? defined('KOOWA') or die('Restricted access'); ?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<form action="<?= @route('id='.$upgrade->id) ?>" method="post" class="-koowa-form">
	<input type="hidden" name="_visual" value="1" />

	<fieldset id="upgrade-basic">
		<legend><?=@text('COM_AKEEBASUBS_UPGRADE_BASIC_TITLE')?></legend>
		
		<label for="title_field" class="main title"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_TITLE'); ?></label>
		<input type="text" size="20" id="title_field" name="title" class="title" value="<?= @escape($upgrade->title) ?>" />
		<br/>
		
		<label for="enabled" class="main" class="mainlabel"><?= @text('Published'); ?></label>
		<?= @helper('select.booleanlist', array('name' => 'enabled', 'selected' => $upgrade->enabled)); ?>
	</fieldset>

	<fieldset id="upgrade-discount">
		<legend><?=@text('COM_AKEEBASUBS_UPGRADE_DISCOUNT_TITLE')?></legend>
		
		<label for="from_id_field" class="main"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID'); ?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.levels',array('name' => 'from_id', 'selected' => $upgrade->from_id, 'deselect' => true))?>
		<br/>
		
		<label for="to_id_field" class="main"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID'); ?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.levels',array('name' => 'to_id', 'selected' => $upgrade->to_id, 'deselect' => true))?>
		<br/>
		
		<label for="min_presence_field" class="main"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE'); ?></label>
		<input type="text" size="5" id="min_presence_field" name="min_presence" value="<?= @escape($upgrade->min_presence) ?>" />
		<br/>

		<label for="max_presence_field" class="main"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE'); ?></label>
		<input type="text" size="5" id="max_presence_field" name="max_presence" value="<?= @escape($upgrade->max_presence) ?>" />
		<br/>

		<label for="type_field" class="main"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_TYPE'); ?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.coupontypes',array('name' => 'type', 'selected' => $upgrade->type, 'deselect' => true))?>
		<br/>
		
		<label for="value_field" class="main"><?= @text('COM_AKEEBASUBS_UPGRADES_FIELD_VALUE'); ?></label>
		<input type="text" size="10" id="value_field" name="value" value="<?= @escape($upgrade->value) ?>" />
	</fieldset>
</form>