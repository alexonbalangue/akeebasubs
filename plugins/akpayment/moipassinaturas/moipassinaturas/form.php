<?php
defined('_JEXEC') or die();
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHtml::_('jquery.framework');
} else {
	JHtml::_('behavior.framework');	
}
?>

<h3><?php echo JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_FORM_HEADER') ?></h3>
<div id="payment-errors" class="alert alert-error" style="display: none;"><ul></ul></div>
<div class="form-horizontal">
	<div class="control-group" id="control-group-card-holder">
		<label for="card-holder" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_FORM_CARDHOLDER') ?>
		</label>
		<div class="controls">
			<input type="text" name="card-holder" id="card-holder" class="input-large" value="<?php echo trim($user->name) ?>" />
		</div>
	</div>
	<div class="control-group" id="control-group-card-number">
		<label for="card-number" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_FORM_CC') ?>
		</label>
		<div class="controls">
			<input type="text" name="card-number" id="card-number" class="input-large" />
		</div>
	</div>
	<div class="control-group" id="control-group-card-expiry">
		<label for="card-expiry" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_FORM_EXPDATE') ?>
		</label>
		<div class="controls">
			<?php echo $this->selectMonth() ?><span> / </span><?php echo $this->selectYear() ?>
		</div>
	</div>
</div>
<form id="payment-form" action="<?php echo $callbackUrl ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="sid" id="sid" />
	<div class="control-group">
		<label for="pay" class="control-label" style="width:190px; margin-right:20px;">
		</label>
		<div class="controls">
			<input type="submit" id="payment-button" class="btn" value="<?php echo JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_FORM_PAYBUTTON') ?>" />
		</div>
	</div>
</form>