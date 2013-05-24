<?php defined('_JEXEC') or die(); ?>
<div class="row-fluid">
	<div class="span6">
		<div class="control-group">
			<label for="params_mailchimp_addgroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_ADDLISTS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'add') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_ADDLISTS_DESCRIPTION2'); ?>
				</span>
			</div>
		</div>
	</div>
	<div class="span6">
		<div class="control-group">
			<label for="params_mailchimp_removegroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_REMOVELISTS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getSelectField($level, 'remove') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_REMOVELISTS_DESCRIPTION2'); ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span8">
		<div class="control-group">
			<label for="params_mailchimp_addgroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_ADDGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getMCGroupSelectField($level, 'add') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_ADDGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span8">
		<div class="control-group">
			<label for="params_mailchimp_removegroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_REMOVEGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getMCGroupSelectField($level, 'remove') ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_REMOVEGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span8">
		<div class="control-group">
			<label for="params_mailchimp_customfields" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_MERGETAG_TITLE'); ?>
			</label>
			<div class="controls">
				<?php echo $this->getMergeTagSelectField($level) ?>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_MERGETAG_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_MAILCHIMP_USAGENOTE'); ?></p>
</div>