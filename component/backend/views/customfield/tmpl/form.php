<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$this->loadHelper('select');
$this->loadHelper('cparams');

?>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="customfields" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_customfield_id" value="<?php echo $this->item->akeebasubs_customfield_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="well">
		<div class="control-group">
			<label class="control-label" for="title"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TITLE') ?></label>
			<div class="controls">
				<input
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TITLE') ?>"
					type="text" name="title" id="title" value="<?php echo $this->item->title ?>" />
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_TITLE') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="slug"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_SLUG') ?></label>
			<div class="controls">
				<input
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_SLUG') ?>"
					type="text" name="slug" id="slug" value="<?php echo $this->item->slug ?>" />
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_SLUG') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="show"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_SHOW') ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::fieldshow('show', $this->item->show) ?>
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_SHOW') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="akeebasubs_level_id"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_LEVEL') ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::levels('akeebasubs_level_id[]', empty($this->item->akeebasubs_level_id) ? '-1' : explode(',', $this->item->akeebasubs_level_id), array('multiple' => 'multiple', 'size' => 5)) ?>
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_LEVEL') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="type"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TYPE') ?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::fieldtypes('type', $this->item->type) ?>
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_TYPE') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="options"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_OPTIONS') ?></label>
			<div class="controls">
				<textarea name="options" id="options" cols="50" rows="7" class="input-xxlarge"><?php echo $this->item->options ?></textarea>
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_OPTIONS') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="default"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_DEFAULT') ?></label>
			<div class="controls">
				<input
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_DEFAULT') ?>"
					type="text" name="default" id="default" value="<?php echo $this->item->default ?>" />
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_DEFAULT') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="allow_empty"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_ALLOW_EMPTY') ?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'allow_empty', null, $this->item->allow_empty); ?>
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_ALLOW_EMPTY') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="valid_label"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_VALID_LABEL') ?></label>
			<div class="controls">
				<input
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_VALID_LABEL') ?>"
					type="text" name="valid_label" id="valid_label" value="<?php echo $this->item->valid_label ?>" />
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_VALID_LABEL') ?></p>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="invalid_label"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_INVALID_LABEL') ?></label>
			<div class="controls">
				<input
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_INVALID_LABEL') ?>"
					type="text" name="invalid_label" id="invalid_label" value="<?php echo $this->item->invalid_label ?>" />
				<p class="help-block"><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_HELP_INVALID_LABEL') ?></p>
			</div>
		</div>
	</div>
</form>

</div>
</div>