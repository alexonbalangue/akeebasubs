<?php
defined('_JEXEC') or die();
if (array_key_exists($level->akeebasubs_level_id, $this->addGroups))
{
	$addgroups = $this->addGroups[$level->akeebasubs_level_id];
}
else
{
	$addgroups = array();
}
if (array_key_exists($level->akeebasubs_level_id, $this->removeGroups))
{
	$removegroups = $this->removeGroups[$level->akeebasubs_level_id];
}
else
{
	$removegroups = array();
}
?>
<div class="row-fluid">
	<div class="span8">
		<div class="control-group">
			<label for="params_sql_addgroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_SQL_ADDGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<textarea rows="7" id="paramssql_addgroups" name="params[sql_addgroups]" style="width: 100%"><?php echo implode("\n", $addgroups) ?></textarea>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_SQL_ADDGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="row-fluid">
	<div class="span8">
		<div class="control-group">
			<label for="params_sql_removegroups" class="control-label">
				<?php echo JText::_('PLG_AKEEBASUBS_SQL_REMOVEGROUPS_TITLE'); ?>
			</label>
			<div class="controls">
				<textarea rows="7" id="paramssql_removegroups" name="params[sql_removegroups]" style="width: 100%"><?php echo implode("\n", $removegroups) ?></textarea>
				<span class="help-block">
					<?php echo JText::_('PLG_AKEEBASUBS_SQL_REMOVEGROUPS_DESCRIPTION2') ?>
				</span>
			</div>
		</div>
	</div>
</div>
<div class="alert alert-warning">
	<p><?php echo JText::_('PLG_AKEEBASUBS_SQL_USAGENOTE'); ?></p>
</div>