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
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="subscription" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_subscription_id" value="<?php echo $this->item->akeebasubs_subscription_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

	<fieldset id="subscriptions-basic">
		<legend><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LBL_SUB')?></legend>
		
		<label for="levelid" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL')?></label>
		<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->akeebasubs_level_id, 'akeebasubs_level_id') ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="userid_visible" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
		<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
		<input type="text" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
		<button onclick="return false;" class="modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
		<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
		<div class="akeebasubs-clear"></div>

		<label for="enabled" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ENABLED')?></label>
		<span class="akeebasubs-booleangroup">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</span>
		<div class="akeebasubs-clear"></div>

		<label for="publish_up" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PUBLISH_UP')?></label>
		<span class="akeebasubs-nofloat-input">
		<?php echo JHTML::_('calendar', $this->item->publish_up, 'publish_up', 'publish_up'); ?>
		</span>
		<div class="akeebasubs-clear"></div>

		<label for="publish_down" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PUBLISH_DOWN')?></label>
		<span class="akeebasubs-nofloat-input">
		<?php echo JHTML::_('calendar', $this->item->publish_down, 'publish_down', 'publish_down'); ?>
		</span>
		<div class="akeebasubs-clear"></div>
		
		<label for="notes" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_NOTES')?></label>
		<div class="akeebasubs-clear"></div>
		<textarea name="notes" id="notes" cols="40" rows="10" style="margin-left: 5em;"><?php echo $this->item->notes?></textarea>

	</fieldset>
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LBL_PAYMENT')?></legend>
		
		<label for="processor" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR')?></label>
		<input type="text" name="processor" id="processor" value="<?php echo $this->item->processor?>"/>
		<div class="akeebasubs-clear"></div>

		<label for="processor_key" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR_KEY')?></label>
		<input type="text" name="processor_key" id="processor_key" value="<?php echo $this->item->processor_key?>"/>
		<div class="akeebasubs-clear"></div>

		<label for="state" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE')?></label>
		<?php echo AkeebasubsHelperSelect::paystates($this->item->state,'state') ?>
		<div class="akeebasubs-clear"></div>
		
		<label for="net_amount" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_NET_AMOUNT')?></label>
		<input type="text" name="net_amount" id="net_amount" value="<?php echo $this->item->net_amount?>"/>
		<div class="akeebasubs-clear"></div>

		<label for="tax_amount" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_TAX_AMOUNT')?></label>
		<input type="text" name="tax_amount" id="tax_amount" value="<?php echo $this->item->tax_amount?>"/>
		<div class="akeebasubs-clear"></div>
		
		<label for="gross_amount" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_GROSS_AMOUNT')?></label>
		<input type="text" name="gross_amount" id="gross_amount" value="<?php echo $this->item->gross_amount?>"/>
		<div class="akeebasubs-clear"></div>
		
		<label for="created_on" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON')?></label>
		<span class="akeebasubs-nofloat-input">
		<?php echo JHTML::_('calendar', $this->item->created_on, 'created_on', 'created_on'); ?>
		</span>
		<div class="akeebasubs-clear"></div>
		
		<label for="akeebasubs_invoice_id" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_INVOICE_ID')?></label>
		<input type="text" name="akeebasubs_invoice_id" id="akeebasubs_invoice_id" value="<?php echo $this->item->akeebasubs_invoice_id?>"/>
		<div class="akeebasubs-clear"></div>
		
		<label for="params" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PARAMS')?></label>
		<div class="akeebasubs-clear"></div>
		<textarea name="params" id="params" cols="40" rows="10" style="margin-left: 5em;"><?php echo $this->item->params?></textarea>		
	</fieldset>
</form>

<script type="text/javascript">
function jSelectUser_userid(id, username)
{
	document.getElementById('userid').value = id;
	document.getElementById('userid_visible').value = username;
	try {
		document.getElementById('sbox-window').close();	
	} catch(err) {
		SqueezeBox.close();
	}
}

window.addEvent("domready", function() {
	$$("button.modal").each(function(el) {
		el.addEvent("click", function(e) {
			new Event(e).stop();
			SqueezeBox.fromElement($('userselect'), {
				parse: 'rel'
			});
		});
	});
});
</script>