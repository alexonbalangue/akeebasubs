<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die;

$this->loadHelper('select');
JFactory::getDocument()->addScriptDeclaration('
akeeba.jQuery(document).ready(function(){
	akeeba.jQuery("#csvdelimiters").change(function(){
		if(akeeba.jQuery(this).val() == -99){
			akeeba.jQuery("#field_delimiter").show();
			akeeba.jQuery("#field_enclosure").show();
		}
		else{
			akeeba.jQuery("#field_delimiter").hide();
			akeeba.jQuery("#field_enclosure").hide();
		}
	})
});
');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal" enctype="multipart/form-data">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="import" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="alert alert-info">
		<p><?php echo JText::_('COM_AKEEBASUBS_IMPORT_INFO') ?></p>
	</div>

	<div class="alert alert-warning">
		<p><?php echo JText::_('COM_AKEEBASUBS_IMPORT_WARNING') ?></p>
	</div>

	<div class="row-fluid">
		<div class="span6">
			<h3><?php echo JText::_('COM_AKEEBASUBS_IMPORT_DETAILS')?></h3>

			<div class="control-group">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_DELIMITERS')?></label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::csvdelimiters('csvdelimiters', 1, array('class'=>'minwidth')) ?>
					<div class="help-block">
						<?php echo JText::_('COM_AKEEBASUBS_IMPORT_DELIMITERS_DESC'); ?>
					</div>
				</div>
			</div>
			<div class="control-group" id="field_delimiter" style="display:none">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_FIELD_DELIMITERS')?></label>
				<div class="controls">
					<input type="text" name="field_delimiter" value="">
					<div class="help-block">
						<?php echo JText::_('COM_AKEEBASUBS_IMPORT_FIELD_DELIMITERS_DESC'); ?>
					</div>
				</div>
			</div>
			<div class="control-group" id="field_enclosure" style="display:none">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_FIELD_ENCLOSURE')?></label>
				<div class="controls">
					<input type="text" name="field_enclosure" value="">
					<div class="help-block">
						<?php echo JText::_('COM_AKEEBASUBS_IMPORT_FIELD_ENCLOSURE_DESC'); ?>
					</div>
				</div>
			</div>
			<!--<div class="control-group">
				<label class="control-label"><?php /*echo JText::_('COM_AKEEBASUBS_IMPORT_FIRSTLINE')*/?></label>
				<div class="controls">
					<?php /*echo JHTML::_('select.booleanlist', 'skipfirst')*/?>
					<div class="help-block">
						<?php /*echo JText::_('COM_AKEEBASUBS_IMPORT_FIRSTLINE_DESC'); */?>
					</div>
				</div>
			</div>-->
			<div class="control-group">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_FILE')?></label>
				<div class="controls">
					<input type="file" name="csvfile" />
					<div class="help-block">
						<?php echo JText::_('COM_AKEEBASUBS_IMPORT_FILE_DESC'); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>