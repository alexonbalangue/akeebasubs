<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

?>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="levelgroup" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_levelgroup_id" value="<?php echo $this->item->akeebasubs_levelgroup_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="control-group">
		<label for="title_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_LEVELGROUPS_FIELD_TITLE'); ?></label>
		<div class="controls">
			<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
		</div>
	</div>
	<div class="control-group">
		<label for="enabled" class="control-label">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</div>
	</div>
</form>
	
</div>
</div>