<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');
JHTML::_('behavior.mootools');
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>
<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="upgrade" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_upgrade_id" value="<?php echo $this->item->akeebasubs_upgrade_id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

	<fieldset id="upgrade-basic">
		<legend><?php echo JText::_('COM_AKEEBASUBS_UPGRADE_BASIC_TITLE')?></legend>
		
		<label for="title_field" class="main title"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TITLE'); ?></label>
		<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
		<div class="akeebasubs-clear"></div>
		
		<label for="enabled" class="main" class="mainlabel">
			<?php if(version_compare(JVERSION,'1.6.0','ge')): ?>
			<?php echo JText::_('JPUBLISHED'); ?>
			<?php else: ?>
			<?php echo JText::_('PUBLISHED'); ?>
			<?php endif; ?>
		</label>
		<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	</fieldset>

	<fieldset id="upgrade-discount">
		<legend><?php echo JText::_('COM_AKEEBASUBS_UPGRADE_DISCOUNT_TITLE')?></legend>
		
		<label for="from_id_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID'); ?></label>
		<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->from_id, 'from_id'); ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="to_id_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID'); ?></label>
		<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->to_id, 'to_id'); ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="min_presence_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE'); ?></label>
		<input type="text" size="5" id="min_presence_field" name="min_presence" value="<?php echo $this->escape($this->item->min_presence) ?>" />
		<div class="akeebasubs-clear"></div>

		<label for="max_presence_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE'); ?></label>
		<input type="text" size="5" id="max_presence_field" name="max_presence" value="<?php echo $this->escape($this->item->max_presence) ?>" />
		<div class="akeebasubs-clear"></div>

		<label for="type_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TYPE'); ?></label>
		<?php echo AkeebasubsHelperSelect::coupontypes('type', $this->item->type) ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="value_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_VALUE'); ?></label>
		<input type="text" size="10" id="value_field" name="value" value="<?php echo $this->escape($this->item->value) ?>" />
	</fieldset>
</form>