<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="upgrade" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_upgrade_id" value="<?php echo $this->item->akeebasubs_upgrade_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
 
	<div class="row-fluid">
	<div class="span6">
		<h3><?php echo JText::_('COM_AKEEBASUBS_UPGRADE_BASIC_TITLE')?></h3>
		
		<div class="control-group">
			<label for="title_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TITLE'); ?></label>
			<div class="controls">
				<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
			</div>
		</div>
		
		<div class="control-group">
			<label for="enabled" class="control-label" class="mainlabel">
				<?php echo JText::_('JPUBLISHED'); ?>
			</label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
			</div>
		</div>
	</div>

	<div class="span6">
		<h3><?php echo JText::_('COM_AKEEBASUBS_UPGRADE_DISCOUNT_TITLE')?></h3>
		
		<div class="control-group">
			<label for="from_id" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID'); ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->from_id, 'from_id'); ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="to_id" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID'); ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->to_id, 'to_id'); ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="min_presence_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE'); ?></label>
			<div class="controls">
				<input type="text" size="5" id="min_presence_field" name="min_presence" value="<?php echo $this->escape($this->item->min_presence) ?>" />
			</div>
		</div>
		
		<div class="control-group">
			<label for="max_presence_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE'); ?></label>
			<div class="controls">
				<input type="text" size="5" id="max_presence_field" name="max_presence" value="<?php echo $this->escape($this->item->max_presence) ?>" />
			</div>
		</div>
		
		<div class="control-group">
			<label for="type" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TYPE'); ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::upgradetypes('type', $this->item->type) ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="value_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_VALUE'); ?></label>
			<div class="controls">
				<input type="text" size="10" id="value_field" name="value" value="<?php echo $this->escape($this->item->value) ?>" />
			</div>
		</div>
		
		<div class="control-group">
			<label for="combine" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_COMBINE'); ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'combine', null, $this->item->combine); ?>
			</div>
		</div>
	</div>
	</div>
</form>
