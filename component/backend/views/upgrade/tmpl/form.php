<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="upgrade" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_upgrade_id" value="<?php echo $this->item->akeebasubs_upgrade_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />
 
	<div class="row-fluid">
	<div class="span12">
	<div class="well">
		<legend><?php echo JText::_('COM_AKEEBASUBS_UPGRADE_BASIC_TITLE')?></legend>
		
		<label for="title_field" class="main title"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TITLE'); ?></label>
		<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
		<div class="akeebasubs-clear"></div>
		
		<label for="enabled" class="main" class="mainlabel">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	</div>
	</div>
	</div>

	<div class="row-fluid">
	<div class="span12">
	<div class="well">
		<legend><?php echo JText::_('COM_AKEEBASUBS_UPGRADE_DISCOUNT_TITLE')?></legend>
		
		<label for="from_id" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID'); ?></label>
		<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->from_id, 'from_id'); ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="to_id" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID'); ?></label>
		<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->to_id, 'to_id'); ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="min_presence_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE'); ?></label>
		<input type="text" size="5" id="min_presence_field" name="min_presence" value="<?php echo $this->escape($this->item->min_presence) ?>" />
		<div class="akeebasubs-clear"></div>

		<label for="max_presence_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE'); ?></label>
		<input type="text" size="5" id="max_presence_field" name="max_presence" value="<?php echo $this->escape($this->item->max_presence) ?>" />
		<div class="akeebasubs-clear"></div>

		<label for="type" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TYPE'); ?></label>
		<?php echo AkeebasubsHelperSelect::upgradetypes('type', $this->item->type) ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="value_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_VALUE'); ?></label>
		<input type="text" size="10" id="value_field" name="value" value="<?php echo $this->escape($this->item->value) ?>" />

		<div class="akeebasubs-clear"></div>
		<label for="combine" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_COMBINE'); ?></label>
		<?php echo JHTML::_('select.booleanlist', 'combine', null, $this->item->combine); ?>
	</div>
	</div>
	</div>
</form>
