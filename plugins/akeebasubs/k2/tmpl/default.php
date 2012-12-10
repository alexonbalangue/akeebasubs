<?php defined('_JEXEC') or die(); ?>
<div class="row-fluid">
	<div class="span6">
		<div class="control-group">
			<label for="params_k2_addgroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_K2_ADDGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('access.usergroup', 'params[k2_addgroups][]', $level->params->k2_addgroups, array('multiple' => 'multiple', 'size' => 8, 'class' => 'input-large'), false) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_K2_ADDGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
	<div class="span6">
		<div class="control-group">
			<label for="params_k2_removegroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_K2_REMOVEGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo JHtml::_('access.usergroup', 'params[k2_removegroups][]', $level->params->k2_removegroups, array('multiple' => 'multiple', 'size' => 8, 'class' => 'input-large'), false) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_K2_REMOVEGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<option id="paramsk2_addgroups_noselect" value=""><?php echo JText::_('PLG_AKEEBASUBS_K2_NONE') ?></option>
<option id="paramsk2_removegroups_noselect" value=""><?php echo JText::_('PLG_AKEEBASUBS_K2_NONE') ?></option>
<script type="text/javascript">
(function($) {
	$(document).ready(function(){
		$('#paramsk2_addgroups').prepend($('#paramsk2_addgroups_noselect'))
		$('#paramsk2_removegroups').prepend($('#paramsk2_removegroups_noselect'))
	})
	
})(akeeba.jQuery);
</script>
<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_K2_USAGENOTE'); ?></p>
</div>