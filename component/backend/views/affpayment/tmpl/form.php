<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="affpayment" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_affpayment_id" value="<?php echo $this->item->akeebasubs_affpayment_id ?>" />
	<input type="hidden" name="created_by" value="<?php echo empty($this->item->created_by) ? JFactory::getUser()->id : $this->item->created_by; ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<h3><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENTS_BASIC_TITLE')?></h3>
	
	<div class="control-group">
		<label for="affiliate" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENT_USER_ID')?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::affiliates($this->item->akeebasubs_affiliate_id) ?>
		</div>
	</div>
	<div class="control-group">
		<label for="amount" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENT_AMOUNT')?></label>
		<div class="controls">
			<div class="input-<?php echo (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before') ? 'prepend' : 'append' ?>">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<span class="add-on">
					<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				</span>
				<?php endif; ?>
				<input type="text" name="amount" id="amount" value="<?php echo $this->escape($this->item->amount)?>" />
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<span class="add-on">
					<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="control-group">
		<label for="created_on" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_AFFPAYMENT_CREATED')?></label>
		<div class="controls">
			<?php echo JHTML::_('calendar', $this->item->created_on, 'created_on', 'created_on'); ?>
		</div>
	</div>
</form>