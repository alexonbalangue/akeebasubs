<?php defined('_JEXEC') or die(); ?>
<div class="row-fluid">
	<div class="control-group">
		<label for="params_slavesubs_maxSlaves" class="control-label">
			<?php echo JText::_('PLG_AKEEBASUBS_SLAVESUBS_MAXSLAVES_TITLE'); ?>
		</label>
		<div class="controls">
			<?php echo JHtml::_('select.integerlist', 0, 10, 1, 'params[slavesubs_maxSlaves]', array('class' => 'input-small'), $level->params->slavesubs_maxSlaves); ?>
			<span class="help-block">
				<?php echo JText::_('PLG_AKEEBASUBS_SLAVESUBS_MAXSLAVES_DESCRIPTION') ?>
			</span>
		</div>
	</div>
</div>