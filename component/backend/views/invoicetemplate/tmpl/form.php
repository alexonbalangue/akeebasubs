<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
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
	
	<h3><?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_TEMPLATE')?></h3>
	<?php echo $editor->display( 'template',  $this->item->template, '97%', '500', '50', '20', false ) ; ?>

<div class="akeebasubs-clear"></div>
</form>