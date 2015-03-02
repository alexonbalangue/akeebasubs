<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
JHtml::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('params');

$editor = JFactory::getEditor();
?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="invoicetemplate" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="akeebasubs_invoicetemplate_id" value="<?php echo $this->item->akeebasubs_invoicetemplate_id ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<h3><?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATE_BASIC_TITLE')?></h3>

	<div class="control-group">
		<label for="title_field" class="control-label">
				<?php echo  JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_TITLE'); ?>
		</label>
		<div class="controls">
			<input type="text" size="30" id="title_field" name="title" class="title" value="<?php echo  $this->escape($this->item->title) ?>" />
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
	<div class="control-group">
		<label for="levels_field" class="control-label"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::levels('levels[]', empty($this->item->levels) ? '0' : explode(',',$this->item->levels), array('multiple' => 'multiple', 'size' => 4, 'include_all' => true, 'include_none' => true)) ?>
		</div>
	</div>
	<div class="control-group">
		<label for="globalformat" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_GLOBALFORMAT'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'globalformat', null, $this->item->globalformat); ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_GLOBALFORMAT_HELP'); ?>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label for="format" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_FORMAT'); ?>
		</label>
		<div class="controls">
			<input type="text" size="30" name="localformat" value="<?php echo $this->escape($this->item->format) ?>" />
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_FORMAT_HELP'); ?>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label for="globalnumbering" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_GLOBALNUMBERING'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'globalnumbering', null, $this->item->globalnumbering); ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_GLOBALNUMBERING_HELP'); ?>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label for="number_reset" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_NUMBER_RESET'); ?>
		</label>
		<div class="controls">
			<input type="text" size="30" name="number_reset" value="<?php echo $this->escape($this->item->number_reset) ?>" />
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_NUMBER_RESET_HELP'); ?>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label for="isbusiness" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS'); ?>
		</label>
		<div class="controls">
			<?php echo akeebasubsHelperSelect::invoicetemplateisbusines('isbusiness', $this->item->isbusiness); ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_ISBUSINESS_HELP'); ?>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label for="country" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_COUNTRY'); ?>
		</label>
		<div class="controls">
			<?php echo akeebasubsHelperSelect::countries($this->item->country, 'country'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_COUNTRY_HELP'); ?>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label for="country" class="control-label" class="mainlabel">
			<?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_NOINVOICE'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'noinvoice', null, $this->item->noinvoice); ?>
			<span class="help-block">
				<?php //echo JText::_(''); ?>
			</span>
		</div>
	</div>

	<h3><?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_TEMPLATE')?></h3>
	<?php echo $editor->display( 'template',  $this->item->template, '97%', '500', '50', '20', false ) ; ?>

<div class="akeebasubs-clear"></div>
</form>