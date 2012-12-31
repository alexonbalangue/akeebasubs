<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$editor = JFactory::getEditor();

$this->loadHelper('select');
$this->loadHelper('cparams');
?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="state" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_state_id" value="<?php echo $this->item->akeebasubs_state_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	
<div class="row-fluid">
	
		<div class="control-group">
			<label for="country" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_STATES_FIELD_COUNTRY'); ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::countries($this->item->country) ?>
				<p class="help-block">
					<?php echo JText::_('COM_AKEEBASUBS_STATES_FIELD_COUNTRY_HELP'); ?>
				</p>
			</div>
		</div>
		
		<div class="control-group">
			<label for="label_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_STATES_FIELD_LABEL'); ?></label>					
			<div class="controls">
				<input id="slug_field" type="text" name="label" class="label_field" value="<?php echo  $this->item->label; ?>" />
				<p class="help-block">
					<?php echo JText::_( 'COM_AKEEBASUBS_STATES_FIELD_LABEL_HELP' );?>
				</p>
			</div>
		</div>
		
		<div class="control-group">
			<label for="state_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_STATES_FIELD_STATE'); ?></label>					
			<div class="controls">
				<input id="slug_field" type="text" name="state" class="state_field" value="<?php echo  $this->item->state; ?>" />
				<p class="help-block">
					<?php echo JText::_( 'COM_AKEEBASUBS_STATES_FIELD_STATE_HELP' );?>
				</p>
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
</div>

</form>
