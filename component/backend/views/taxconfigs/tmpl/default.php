<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$this->loadHelper('select');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="taxconfigs" />
<input type="hidden" id="task" name="task" value="apply" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="form-horizontal">
	<div class="control-group">
		<label class="control-label" for="novatcalc">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_NOVATCALC') ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::booleanlist('novatcalc', array(), 0) ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_NOVATCALC_INFO'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="akeebasubs_level_id">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_LEVEL') ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::levels('akeebasubs_level_id', 0, array('class'=>'input-medium', 'include_all' => 1)); ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_LEVEL_INFO'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="country">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_COUNTRY') ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::countries(); ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_COUNTRY_INFO'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="taxrate">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_TAXRATE') ?></label>
		<div class="controls">
			<div class="input-append">
				<input type="text" name="taxrate" id="taxrate" class="input-small" value="0" />
				<span class="add-on">%</span>
			</div>

			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_TAXRATE_INFO'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="viesreg">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_VIESREG') ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::booleanlist('viesreg', array(), 0) ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_VIESREG_INFO'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="showvat">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_SHOWVAT') ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::booleanlist('showvat', array(), 0) ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_SHOWVAT_INFO'); ?>
			</span>
		</div>
	</div>

	<div class="form-actions">
		<button class="btn btn-primary btn-large">
			<i class="icon-white icon-check"></i>
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_SUBMIT') ?>
		</button>
		<a href="index.php?option=com_akeebasubs" class="btn btn-danger">
			<?php echo JText::_('COM_AKEEBASUBS_TAXCONFIGS_LBL_CANCEL') ?>
		</a>
	</div>
</div>

</form>