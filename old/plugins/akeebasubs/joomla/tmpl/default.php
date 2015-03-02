<?php defined('_JEXEC') or die(); ?>
<div class="row-fluid">
	<div class="span6">
		<div class="control-group">
			<label for="params_joomla_addgroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_ADDGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'add') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_ADDGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
	<div class="span6">
		<div class="control-group">
			<label for="params_joomla_removegroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_REMOVEGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'remove') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_REMOVEGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<option id="paramsjoomla_addgroups_noselect" value=""><?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_NONE') ?></option>
<option id="paramsjoomla_removegroups_noselect" value=""><?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_NONE') ?></option>
<script type="text/javascript">
(function($) {
	$(document).ready(function(){
		$('#paramsjoomla_addgroups').prepend($('#paramsjoomla_addgroups_noselect'))
		$('#paramsjoomla_removegroups').prepend($('#paramsjoomla_removegroups_noselect'))
	})
	
})(akeeba.jQuery);
</script>
<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_JOOMLA_USAGENOTE'); ?></p>
</div>