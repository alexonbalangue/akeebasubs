<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die;

$this->loadHelper('select');

?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal" enctype="multipart/form-data">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="import" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="row-fluid">
		<div class="span6">
			<h3><?php echo JText::_('COM_AKEEBASUBS_IMPORT_DETAILS')?></h3>

			<div class="control-group">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_DELIMITERS')?></label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::csvdelimiters('csvdelimiters', 1, array('class'=>'minwidth')) ?>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_FIRSTLINE')?></label>
				<div class="controls">
					<?php echo JHTML::_('select.booleanlist', 'skipfirst')?>
				</div>
			</div>
			<div class="control-group">
				<label class="control-label"><?php echo JText::_('COM_AKEEBASUBS_IMPORT_FILE')?></label>
				<div class="controls">
					<input type="file" name="csvfile" />
				</div>
			</div>
		</div>
	</div>
</form>