<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);

JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0.0', 'ge')) {
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
<form action="index.php" method="post" name="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="invoicetemplate" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="akeebasubs_invoicetemplate_id" value="<?php echo $this->item->akeebasubs_invoicetemplate_id ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

<fieldset id="coupons-basic">
	<legend><?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATE_BASIC_TITLE')?></legend>
	
	<label for="title_field" class="main title">
			<?php echo  JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_TITLE'); ?>
	</label>
	<input type="text" size="30" id="title_field" name="title" class="title" value="<?php echo  $this->escape($this->item->title) ?>" />
	<div class="akeebasubs-clear"></div>

	<label for="enabled" class="main" class="mainlabel">
		<?php echo JText::_('JPUBLISHED'); ?>
	</label>
	<span class="akeebasubs-booleangroup">
		<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	</span>
	<div class="akeebasubs-clear"></div>

	<label for="levels_field" class="main"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>
	<?php echo AkeebasubsHelperSelect::levels('levels[]', empty($this->item->levels) ? '0' : explode(',',$this->item->levels), array('multiple' => 'multiple', 'size' => 4, 'include_all' => true, 'include_none' => true)) ?>
</fieldset>

<fieldset id="coupons-basic">
	<legend><?php echo JText::_('COM_AKEEBASUBS_INVOICETEMPLATES_FIELD_TEMPLATE')?></legend>
	<?php echo $editor->display( 'template',  $this->item->template, '97%', '500', '50', '20', false ) ; ?>
</fieldset>


<div class="akeebasubs-clear"></div>
</form>