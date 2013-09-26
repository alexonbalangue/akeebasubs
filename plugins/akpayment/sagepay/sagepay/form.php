<?php
defined('_JEXEC') or die();

require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';

$nameParts = explode(' ', $data->user->name, 2);
$firstName = $nameParts[0];
if(count($nameParts) > 1) {
	$lastName = $nameParts[1];
} else {
	$lastName = '';
}

$cparamShowCountries = AkeebasubsHelperCparams::getParam('showcountries', '');
$cparamHideCountries = AkeebasubsHelperCparams::getParam('hidecountries', '');
?>

<h3><?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_HEADER') ?></h3>

<div id="payment-errors" class="alert alert-error" style="display: none;"></div>

<form id="" action="<?php echo $data->url ?>" method="post" class="form form-horizontal">
	<div class="form-horizontal">
		<div class="control-group" id="control-group-card-holder">
			<label for="card-holder" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_CARDHOLDER') ?>
			</label>
			<div class="controls">
				<input type="text" name="card-holder" id="card-holder" class="input-large" value="<?php echo $data->cardholder ?>" />
			</div>
		</div>
		<div class="control-group" id="control-group-card-type">
			<label for="card-type" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_CARDTYPE') ?>
			</label>
			<div class="controls">
				<?php echo $this->selectCardType(); ?>
			</div>
		</div>
		<div class="control-group" id="control-group-card-number">
			<label for="card-number" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_CC') ?>
			</label>
			<div class="controls">
				<input type="text" name="card-number" id="card-number" class="input-large" />
			</div>
		</div>
		<div class="control-group" id="control-group-card-expiry">
			<label for="card-expiry" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_EXPDATE') ?>
			</label>
			<div class="controls">
				<?php echo $this->selectMonth() ?><span> / </span><?php echo $this->selectYear() ?>
			</div>
		</div>
		<div class="control-group" id="control-group-card-cvc">
			<label for="card-cvc" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_CVC') ?>
			</label>
			<div class="controls">
				<input type="text" name="card-cvc" id="card-cvc" class="input-mini" maxlength="4" />
			</div>
		</div>

		<h3><?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING')?></h3>

		<div class="control-group" id="control-group-billing-first-name">
			<label for="billing-first-name" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_FIRST_NAME') ?>
			</label>
			<div class="controls">
				<input type="text" name="billing-first-name" id="billing-first-name" class="input-large" value="<?php echo $firstName?>" />
			</div>
		</div>

		<div class="control-group" id="control-group-billing-last-name">
			<label for="billing-last-name" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_LAST_NAME') ?>
			</label>
			<div class="controls">
				<input type="text" name="billing-last-name" id="billing-last-name" class="input-large" value="<?php echo $lastName?>" />
			</div>
		</div>

		<div class="control-group" id="control-group-billing-address1">
			<label for="billing-address1" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_ADDRESS1') ?>
			</label>
			<div class="controls">
				<input type="text" name="billing-address1" id="billing-address1" class="input-large" value="<?php echo $data->kuser->address1?>" />
			</div>
		</div>

		<div class="control-group" id="control-group-billing-address2">
			<label for="billing-address2" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_ADDRESS2') ?>
			</label>
			<div class="controls">
				<input type="text" name="billing-address2" id="billing-address2" class="input-large" value="<?php echo $data->kuser->address2?>" />
			</div>
		</div>
		<div class="control-group" id="control-group-billing-city">
			<label for="billing-city" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_CITY') ?>
			</label>
			<div class="controls">
				<input type="text" name="billing-city" id="billing-city" class="input-large" value="<?php echo $data->kuser->city?>" />
			</div>
		</div>
		<div class="control-group" id="control-group-billing-zip">
			<label for="billing-zip" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_ZIP') ?>
			</label>
			<div class="controls">
				<input type="text" name="billing-zip" id="billing-zip" class="input-large" value="<?php echo $data->kuser->zip?>" />
			</div>
		</div>
		<div class="control-group" id="control-group-billing-country">
			<label for="billing-country" class="control-label" style="width:190px; margin-right:20px;">
				<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_BILLING_COUNTRY') ?>
			</label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::countries($data->kuser->country, 'billing-country', array('id'=>'billing-country', 'show' => $cparamShowCountries, 'hide' => $cparamHideCountries)) ?>
			</div>
		</div>
	</div>

	<input type="hidden" name="currency" id="currency" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="amount" id="amount" value="<?php echo $data->amount ?>" />
	<input type="hidden" name="description" id="description" value="<?php echo $data->description ?>" />

	<div class="control-group">
		<label for="pay" class="control-label" style="width:190px; margin-right:20px;">
		</label>
		<div class="controls">
			<input type="submit" id="payment-button" class="btn" value="<?php echo JText::_('PLG_AKPAYMENT_SAGEPAY_FORM_PAYBUTTON') ?>" />
		</div>
	</div>
</form>