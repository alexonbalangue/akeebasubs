<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="affpayment" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_affpayment_id" value="<?php echo $this->item->akeebasubs_affpayment_id ?>" />
	<input type="hidden" name="created_by" value="<?php echo empty($this->item->created_by) ? JFactory::getUser()->id : $this->item->created_by; ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENTS_BASIC_TITLE')?></legend>
	
	<label for="affiliate" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENT_USER_ID')?></label>
	<?php echo AkeebasubsHelperSelect::affiliates($this->item->akeebasubs_affiliate_id) ?>
	<div class="akeebasubs-clear"></div>
	
	<label for="amount" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENT_AMOUNT')?></label>
	<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
	<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
	<?php endif; ?>
	<input type="text" name="amount" id="amount" value="<?php echo $this->escape($this->item->amount)?>" />
	<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
	<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
	<?php endif; ?>
	<div class="akeebasubs-clear"></div>
	
	<label for="created_on" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENT_CREATED')?></label>
	<?php echo JHTML::_('calendar', $this->item->created_on, 'created_on', 'created_on'); ?>	
	<div class="akeebasubs-clear"></div>
</fieldset>

<div class="akeebasubs-clear"></div>
</form>