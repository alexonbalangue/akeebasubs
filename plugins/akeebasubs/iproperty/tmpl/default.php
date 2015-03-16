<?php defined('_JEXEC') or die(); ?>
<div class="control-group">
	<label for="params_iproperty_maxlistings" class="control-label">
		<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXLISTINGS_LBL'); ?>
	</label>
	<div class="controls">
		<input type="text" class="input-small"
			   name="params[iproperty_maxlistings]"
			   id="params_iproperty_maxlistings"
			   value="<?php echo (int)$this->getParamValue($level->akeebasubs_level_id, 'maxlistings') ?>" />
		<span class="help-block">
			<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXLISTINGS_DESC') ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label for="params_iproperty_maxflistings" class="control-label">
		<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXFLISTINGS_LBL'); ?>
	</label>
	<div class="controls">
		<input type="text" class="input-small"
			   name="params[iproperty_maxflistings]"
			   id="params_iproperty_maxflistings"
			   value="<?php echo (int)$this->getParamValue($level->akeebasubs_level_id, 'maxflistings') ?>" />
		<span class="help-block">
			<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXFLISTINGS_DESC') ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label for="params_iproperty_maxagents" class="control-label">
		<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXAGENTS_LBL'); ?>
	</label>
	<div class="controls">
		<input type="text" class="input-small"
			   name="params[iproperty_maxagents]"
			   id="params_iproperty_maxagents"
			   value="<?php echo (int)$this->getParamValue($level->akeebasubs_level_id, 'maxagents') ?>" />
		<span class="help-block">
			<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXAGENTS_DESC') ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label for="params_iproperty_maxfagents" class="control-label">
		<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXFAGENTS_LBL'); ?>
	</label>
	<div class="controls">
		<input type="text" class="input-small"
			   name="params[iproperty_maxfagents]"
			   id="params_iproperty_maxfagents"
			   value="<?php echo (int)$this->getParamValue($level->akeebasubs_level_id, 'maxfagents') ?>" />
		<span class="help-block">
			<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXFAGENTS_DESC') ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label for="params_iproperty_maximgs" class="control-label">
		<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXIMGS_LBL'); ?>
	</label>
	<div class="controls">
		<input type="text" class="input-small"
			   name="params[iproperty_maximgs]"
			   id="params_iproperty_maximgs"
			   value="<?php echo (int)$this->getParamValue($level->akeebasubs_level_id, 'maximgs') ?>" />
		<span class="help-block">
			<?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXIMGS_DESC') ?>
		</span>
	</div>
</div>

<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_IPROPERTY_MAXIMGS_DESC'); ?></p>
</div>