<?php defined('_JEXEC') or die(); ?>

<h3><?php echo JText::_('PLG_AKPAYMENT_PAYPALPAYMENTSPRO_FORM_HEADER') ?></h3>
<div class="row-fluid">
<div class="span12">
<br />
<form action="<?php echo $data->URL ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="METHOD" value="<?php echo $data->METHOD ?>" />
	<input type="hidden" name="USER" value="<?php echo $data->USER ?>" />
	<input type="hidden" name="PWD" value="<?php echo $data->PWD ?>" />
	<input type="hidden" name="SIGNATURE" value="<?php echo $data->SIGNATURE ?>" />
	<input type="hidden" name="VERSION" value="<?php echo $data->VERSION ?>" />
	<input type="hidden" name="PAYMENTACTION" value="<?php echo $data->PAYMENTACTION ?>" />
	<input type="hidden" name="IPADDRESS" value="<?php echo $data->IPADDRESS ?>" />
	<input type="hidden" name="FIRSTNAME" value="<?php echo $data->FIRSTNAME ?>" />
	<input type="hidden" name="LASTNAME" value="<?php echo $data->LASTNAME ?>" />
	<input type="hidden" name="STREET" value="<?php echo $data->STREET ?>" />
	<?php if(! empty($data->STREET2)) { ?>
	<input type="hidden" name="STREET2" value="<?php echo $data->STREET2 ?>" />
	<?php } ?>
	<input type="hidden" name="CITY" value="<?php echo $data->CITY ?>" />
	<?php if(! empty($data->STATE)) { ?>
	<input type="hidden" name="STATE" value="<?php echo $data->STATE ?>" />
	<?php } ?>
	<input type="hidden" name="COUNTRYCODE" value="<?php echo $data->COUNTRYCODE ?>" />
	<input type="hidden" name="ZIP" value="<?php echo $data->ZIP ?>" />
	<input type="hidden" name="AMT" value="<?php echo $data->AMT ?>" />
	<input type="hidden" name="ITEMAMT" value="<?php echo $data->ITEMAMT ?>" />
	<input type="hidden" name="TAXAMT" value="<?php echo $data->TAXAMT ?>" />
	<input type="hidden" name="CURRENCYCODE" value="<?php echo $data->CURRENCYCODE ?>" />
	<input type="hidden" name="DESC" value="<?php echo $data->DESC ?>" />
	<?php if(! empty($data->INVNUM)) { ?>
	<input type="hidden" name="INVNUM" value="<?php echo $data->INVNUM ?>" />
	<?php } ?>
	<?php if(! empty($data->PROFILEREFERENCE)) { ?>
	<input type="hidden" name="PROFILEREFERENCE" value="<?php echo $data->PROFILEREFERENCE ?>" />
	<?php } ?>
	<?php if(! empty($data->BILLINGPERIOD)) { ?>
	<input type="hidden" name="BILLINGPERIOD" value="<?php echo $data->BILLINGPERIOD ?>" />
	<?php } ?>
	<?php if(! empty($data->BILLINGFREQUENCY)) { ?>
	<input type="hidden" name="BILLINGFREQUENCY" value="<?php echo $data->BILLINGFREQUENCY ?>" />
	<?php } ?>
	<input type="hidden" name="NOTIFYURL" value="<?php echo $data->NOTIFYURL ?>" />
	<div class="control-group">
		<label for="CREDITCARDTYPE" class="control-label" style="width: 190px; margin-right: 20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYPALPAYMENTSPRO_FORM_CCTYPE') ?>
		</label>
		<div class="controls">
			<select id="CREDITCARDTYPE" name="CREDITCARDTYPE" class="input-medium">
				<option value="Visa">Visa</option>
				<option value="MasterCard">Master Card</option>
				<option value="Discover">Discover</option>
				<option value="Amex">American Express</option>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label for="ACCT" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYPALPAYMENTSPRO_FORM_CC') ?>
		</label>
		<div class="controls">
			<input type="text" name="ACCT" id="ACCT" class="input-large" />
		</div>
	</div>
	<div class="control-group">
		<label for="EXPDATE" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYPALPAYMENTSPRO_FORM_EXPDATE') ?>
		</label>
		<div class="controls">
			<?php echo $this->selectExpirationDate() ?>
		</div>
	</div>
	<div class="control-group">
		<label for="CVV2" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_PAYPALPAYMENTSPRO_FORM_CVV') ?>
		</label>
		<div class="controls">
			<input type="text" name="CVV2" id="CVV2" class="input-mini" />
		</div>
	</div>
	<div class="control-group">
		<label for="CVV2" class="control-label" style="width:190px; margin-right:20px;">
		</label>
		<div class="controls">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but23.gif" style="width:68px; height:23px;" border="0" name="submit" alt="Paypal Payments Pro" />
		</div>
	</div>
</form>
</div>
</div>