<?php defined('_JEXEC') or die(); ?>

<h3>
	<?php echo JText::_('PLG_AKPAYMENT_ESELECTPLUS_READYTOPAY') ?>
</h3>
<p>
	<?php echo JText::_('PLG_AKPAYMENT_ESELECTPLUS_INSTRUCTIONS') ?>
</p>

<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="eselectplusForm">
<input type="hidden" id="ps_store_id" name="ps_store_id" value="<?php echo $data->ps_store_id ?>" />
<input type="hidden" id="hpp_key" name="hpp_key" value="<?php echo $data->hpp_key ?>" />
<input type="hidden" id="charge_total" name="charge_total" value="<?php echo $data->charge_total ?>" />
<input type="hidden" id="id1" name="id1" value="<?php echo $data->id1 ?>" />
<input type="hidden" id="description1" name="description1" value="<?php echo $data->description1 ?>" />
<input type="hidden" id="quantity1" name="quantity1" value="<?php echo $data->quantity1 ?>" />
<input type="hidden" id="price1" name="price1" value="<?php echo $data->price1 ?>" />
<input type="hidden" id="subtotal1" name="subtotal1" value="<?php echo $data->subtotal1 ?>" />
<input type="hidden" id="rvarSubscriptionID" name="rvarSubscriptionID" value="<?php echo $data->rvarSubscriptionID ?>" />
<input type="hidden" id="cust_id" name="cust_id" value="<?php echo $data->cust_id ?>" />
<input type="hidden" id="order_id" name="order_id" value="<?php echo $data->order_id ?>" />
<input type="hidden" id="lang" name="lang" value="<?php echo $data->lang ?>" />
<input type="hidden" id="gst" name="gst" value="<?php echo $data->gst ?>" />
<input type="hidden" id="cvd_indicator" name="cvd_indicator" value="<?php echo $data->cvd_indicator ?>" />
<input type="hidden" id="bill_first_name" name="bill_first_name" value="<?php echo $data->bill_first_name ?>" />
<input type="hidden" id="bill_last_name" name="bill_last_name" value="<?php echo $data->bill_last_name ?>" />
<?php if($data->bill_company_name) { ?>
<input type="hidden" id="bill_company_name" name="bill_company_name" value="<?php echo $data->bill_company_name ?>" />
<?php } ?>
<input type="hidden" id="bill_city" name="bill_city" value="<?php echo $data->bill_city ?>" />
<?php if($data->bill_state_or_province) { ?>
<input type="hidden" id="bill_state_or_province" name="bill_state_or_province" value="<?php echo $data->bill_state_or_province ?>" />
<?php } ?>
<input type="hidden" id="bill_postal_code" name="bill_postal_code" value="<?php echo $data->bill_postal_code ?>" />
<input type="hidden" id="bill_country" name="bill_country" value="<?php echo $data->bill_country ?>" />
<table id="ak_eselectplus_ccFields" width="100%" border="0" cellspacing="3">
	<tr>
		<td width="33%">
			<?php echo JText::_('PLG_AKPAYMENT_ESELECTPLUS_CCNUMBER') ?>
		</td>
		<td>
			<input type="text" name="cc_num" id="cc_num" value="" size="20" maxlength="20" />
		</td>
	</tr>
	<tr>
		<td width="33%">
			<?php echo JText::_('PLG_AKPAYMENT_ESELECTPLUS_EXPIRY') ?>
		</td>
		<td>
			<?php echo $this->selectMonth() ?> / <?php echo $this->selectYear() ?>
		</td>
	</tr>
	<tr>
		<td width="33%">
			<?php echo JText::_('PLG_AKPAYMENT_ESELECTPLUS_CVD') ?>
		</td>
		<td>
			<input type="text" name="cvd_value" id="cvd_value" value="" size="4" maxlength="4" />
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<button type="submit">
				<?php echo JText::_('PLG_AKPAYMENT_ESELECTPLUS_PAYNOW') ?>
			</button>
		</td>
	</tr>
</table>

</form>