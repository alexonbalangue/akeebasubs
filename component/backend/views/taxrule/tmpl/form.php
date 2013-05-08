<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$editor = JFactory::getEditor();

$this->loadHelper('cparams');
$this->loadHelper('select');
?>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="taxrule" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_taxrule_id" value="<?php echo $this->item->akeebasubs_taxrule_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="control-group">
		<label for="akeebasubs_level_id" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_LEVEL'); ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::levels('akeebasubs_level_id', $this->item->akeebasubs_level_id, array('class'=>'input-medium', 'include_all' => 1)); ?>
		</div>
	</div>
	<div class="control-group">
		<label for="country" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_COUNTRY'); ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::countries($this->item->country, 'country') ?>
		</div>
	</div>

	<div class="control-group">
		<label for="state" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_STATE'); ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::states($this->item->state, 'state') ?>
		</div>
	</div>

	<div class="control-group">
		<label for="city" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_CITY'); ?></label>
		<div class="controls">
			<input type="text" name="city" id="city" value="<?php echo $this->item->city?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="vies" class="control-label" class="main"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_VIES'); ?></label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'vies', null, $this->item->vies); ?>
		</div>
	</div>

	<div class="control-group">
		<label for="taxrate" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_TAXRATE'); ?></label>
		<div class="controls">
			<input type="text" name="taxrate" id="taxrate" value="<?php echo$this->item->taxrate?>" /> <strong>%</strong>
		</div>
	</div>

	<div class="control-group">
		<label for="enabled" class="control-label" class="main">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</div>
	</div>
</form>

</div>
</div>