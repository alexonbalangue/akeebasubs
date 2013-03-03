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
	<input type="hidden" name="view" value="subscription" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_subscription_id" value="<?php echo $this->item->akeebasubs_subscription_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="row-fluid">
	<div class="span6">
	
	<div>
		<h3><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LBL_SUB')?></h3>
		
		<div class="control-group">
			<label for="levelid" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL')?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->item->akeebasubs_level_id, 'akeebasubs_level_id', array('class'=>'minwidth')) ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="userid_visible" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
			<div class="controls">
				<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
				<input type="text" class="input-medium" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
				<button onclick="return false;" class="btn btn-mini modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
				<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
			</div>
		</div>
		
		<div class="control-group">
			<label for="enabled" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ENABLED')?></label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="publish_up" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PUBLISH_UP')?></label>
			<div class="controls">
				<?php echo JHTML::_('calendar', $this->item->publish_up, 'publish_up', 'publish_up'); ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="publish_down" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PUBLISH_DOWN')?></label>
			<div class="controls">
				<?php echo JHTML::_('calendar', $this->item->publish_down, 'publish_down', 'publish_down'); ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="notes" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_NOTES')?></label>
			<div class="controls">
				<textarea name="notes" id="notes" cols="40" rows="5" class="input-xlarge"><?php echo $this->item->notes?></textarea>
			</div>
		</div>
	</div>
	</div>
	
	<div class="span6">
	<div>
		<h3><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LBL_PAYMENT')?></h3>
		
		<div class="control-group">
			<label for="processor" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR')?></label>
			<div class="controls">
				<input type="text" name="processor" id="processor" value="<?php echo $this->item->processor?>"/>
			</div>
		</div>

		<div class="control-group">
			<label for="processor_key" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR_KEY')?></label>
			<div class="controls">
				<input type="text" name="processor_key" id="processor_key" value="<?php echo $this->item->processor_key?>"/>
			</div>
		</div>

		<div class="control-group">
			<label for="state" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE')?></label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::paystates($this->item->state,'state', array('class'=>'minwidth')) ?>
			</div>
		</div>
		
		<div class="control-group">
			<label for="net_amount" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_NET_AMOUNT')?></label>
			<div class="controls">
				<div class="input-<?php echo (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before') ? 'prepend' : 'append' ?>">
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
					<input type="text" class="input-medium" name="net_amount" id="net_amount" value="<?php echo $this->item->net_amount?>"/>
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<div class="control-group">
				<label for="tax_percent" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_TAX_PERCENT')?></label>
			<div class="controls">
				<div class="input-append">
					<input type="text" class="input-mini" name="tax_percent" id="tax_percent" value="<?php echo $this->item->tax_percent ?>"/>
					<span class="add-on">%</span>
				</div>
			</div>
		</div>
		
		<div class="control-group">
				<label for="tax_amount" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_TAX_AMOUNT')?></label>
			<div class="controls">
				<div class="input-<?php echo (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before') ? 'prepend' : 'append' ?>">
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
					<input type="text" class="input-medium" name="tax_amount" id="tax_amount" value="<?php echo $this->item->tax_amount?>"/>
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<div class="control-group">
			<label for="gross_amount" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_GROSS_AMOUNT')?></label>
			<div class="controls">
				<div class="input-<?php echo (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before') ? 'prepend' : 'append' ?>">
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
					<input type="text" class="input-medium" name="gross_amount" id="gross_amount" value="<?php echo $this->item->gross_amount?>"/>
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<div class="control-group">
			<label for="created_on" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON')?></label>
			<div class="controls">
				<?php echo JHTML::_('calendar', $this->item->created_on, 'created_on', 'created_on'); ?>
			</div>
		</div>
		
		<!--
		<div class="control-group">
			<label for="akeebasubs_invoice_id" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_INVOICE_ID')?></label>
			<div class="controls">
				<input type="text" name="akeebasubs_invoice_id" id="akeebasubs_invoice_id" value="<?php echo $this->item->akeebasubs_invoice_id?>"/>
			</div>
		</div>
		-->
	</div>
	</div>
	</div>
	
	<div class="row-fluid">
		<?php
		$hasShownCustomParamsHeader = false;
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onSubscriptionFormRenderPerSubFields', array(array('subscriptionlevel' => $this->item->akeebasubs_level_id,  'subcustom'=>$this->item->params)));
		if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
		if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):?>
		<?php if (!$hasShownCustomParamsHeader): ?>
		<h3><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_CUSTOMPARAMS_TITLE')?></h3>
		<?php $hasShownCustomParamsHeader = true;
		endif; ?>
		<div class="control-group">
			<label for="<?php echo $field['id']?>" class="control-label"><?php echo $field['label']?></label>
			<div class="controls">
				<?php echo $field['elementHTML']?>
			</div>
		</div>

		<?php endforeach; endforeach;?>
	</div>
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
			try {
				new Event(e).stop();
			} catch(anotherMTUpgradeIssue) {
				try {
					e.stop();
				} catch(WhateverIsWrongWithYouIDontCare) {
					try {
						DOMEvent(e).stop();
					} catch(NoBleepinWay) {
						alert('If you see this message, your copy of Joomla! is FUBAR');
					}
				}
			}
			SqueezeBox.fromElement($('userselect'), {
				parse: 'rel'
			});
		});
	});
});
</script>