<?php defined('_JEXEC') or die(); ?>
<?php
$type = $level->recurring ? 'customer' : 'transaction';
?>

<h3><?php echo JText::_('PLG_AKPAYMENT_BRAINTREE_FORM_HEADER') ?></h3>
<form id="payment-form" action="<?php echo Braintree_TransparentRedirect::url() ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="tr_data" id="tr_data" value="<?php echo htmlentities($data) ?>" />
	<div class="control-group" id="control-group-card-number">
		<label for="braintree_credit_card_number" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_BRAINTREE_FORM_CC') ?>
		</label>
		<div class="controls">
			<input type="text" name="<?php echo $type ?>[credit_card][number]" id="braintree_credit_card_number" class="input-large" />
		</div>
	</div>
	<div class="control-group" id="control-group-card-expiry">
		<label for="card-expiry" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_BRAINTREE_FORM_EXPDATE') ?>
		</label>
		<div class="controls">
			<?php echo $this->selectExpirationDate($type) ?>
		</div>
	</div>
	<div class="control-group">
		<label for="pay" class="control-label" style="width:190px; margin-right:20px;">
		</label>
		<div class="controls">
			<input type="submit" id="payment-button" class="btn" value="<?php echo JText::_('PLG_AKPAYMENT_BRAINTREE_FORM_PAYBUTTON') ?>" />
		</div>
	</div>
</form>