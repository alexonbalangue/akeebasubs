<?php defined('_JEXEC') or die(); ?>
<div class="row-fluid">
	<div class="span6">
		<div class="control-group">
			<label for="params_easydiscuss_addranks" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_ADDRANKS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'add-RANKS') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_ADDRANKS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
	<div class="span6">
		<div class="control-group">
			<label for="params_easydiscuss_removeranks" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_REMOVERANKS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'remove-RANKS') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_REMOVERANKS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span6">
		<div class="control-group">
			<label for="params_easydiscuss_addranks" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_ADDBADGES_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'add-BADGES') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_ADDBADGES_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
	<div class="span6">
		<div class="control-group">
			<label for="params_easydiscuss_removeranks" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_REMOVEBADGES_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'remove-BADGES') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_REMOVEBADGES_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_EASYDISCUSS_USAGENOTE'); ?></p>
</div>