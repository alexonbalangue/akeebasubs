<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->$p['url']) ?>"  method="post" id="paymentForm">
	<input type="hidden" id="<?php echo $p['merchant_id'] ?>" name="<?php echo $p['merchant_id'] ?>" value="<?php echo $data->$p['merchant_id'] ?>" />
	<input type="hidden" id="<?php echo $p['merchant_key'] ?>" name="<?php echo $p['merchant_key'] ?>" value="<?php echo $data->$p['merchant_key'] ?>" />
	<input type="hidden" id="<?php echo $p['price_total'] ?>" name="<?php echo $p['price_total'] ?>" value="<?php echo $data->$p['price_total'] ?>" />
	<input type="hidden" id="<?php echo $p['item_id'] ?>" name="<?php echo $p['item_id'] ?>" value="<?php echo $data->$p['item_id'] ?>" />
	<input type="hidden" id="<?php echo $p['item_desc'] ?>" name="<?php echo $p['item_desc'] ?>" value="<?php echo $data->$p['item_desc'] ?>" />
	<input type="hidden" id="<?php echo $p['item_quantity'] ?>" name="<?php echo $p['item_quantity'] ?>" value="<?php echo $data->$p['item_quantity'] ?>" />
	<input type="hidden" id="<?php echo $p['item_price_unit'] ?>" name="<?php echo $p['item_price_unit'] ?>" value="<?php echo $data->$p['item_price_unit'] ?>" />
	<input type="hidden" id="<?php echo $p['price_taxes'] ?>" name="<?php echo $p['price_taxes'] ?>" value="<?php echo $data->$p['price_taxes'] ?>" />
	<input type="hidden" id="<?php echo $p['subscription_id'] ?>" name="<?php echo $p['subscription_id'] ?>" value="<?php echo $data->$p['subscription_id'] ?>" />
	<input type="hidden" id="<?php echo $p['customer_id'] ?>" name="<?php echo $p['customer_id'] ?>" value="<?php echo $data->$p['customer_id'] ?>" />
	<input type="hidden" id="<?php echo $p['customer_email'] ?>" name="<?php echo $p['customer_email'] ?>" value="<?php echo $data->$p['customer_email'] ?>" />
	<input type="hidden" id="<?php echo $p['language'] ?>" name="<?php echo $p['language'] ?>" value="<?php echo $data->$p['language'] ?>" />
	<input type="hidden" id="<?php echo $p['order_id'] ?>" name="<?php echo $p['order_id'] ?>" value="<?php echo $data->$p['order_id'] ?>" />
	<input type="hidden" id="<?php echo $p['bill_first_name'] ?>" name="<?php echo $p['bill_first_name'] ?>" value="<?php echo $data->$p['bill_first_name'] ?>" />
	<input type="hidden" id="<?php echo $p['bill_last_name'] ?>" name="<?php echo $p['bill_last_name'] ?>" value="<?php echo $data->$p['bill_last_name'] ?>" />
	<input type="hidden" id="<?php echo $p['bill_address'] ?>" name="<?php echo $p['bill_address'] ?>" value="<?php echo $data->$p['bill_address'] ?>" />
	<input type="hidden" id="<?php echo $p['bill_city'] ?>" name="<?php echo $p['bill_city'] ?>" value="<?php echo $data->$p['bill_city'] ?>" />
	<input type="hidden" id="<?php echo $p['bill_postal_code'] ?>" name="<?php echo $p['bill_postal_code'] ?>" value="<?php echo $data->$p['bill_postal_code'] ?>" />
	<input type="hidden" id="<?php echo $p['bill_country'] ?>" name="<?php echo $p['bill_country'] ?>" value="<?php echo $data->$p['bill_country'] ?>" />
	<?php if($kuser->isbusiness) { ?>
	<input type="hidden" id="<?php echo $p['bill_company'] ?>" name="<?php echo $p['bill_company'] ?>" value="<?php echo $data->$p['bill_company'] ?>" />
	<?php } ?>
	<?php if($data->$p['bill_state']) { ?>
	<input type="hidden" id="<?php echo $p['bill_state'] ?>" name="<?php echo $p['bill_state'] ?>" value="<?php echo $data->$p['bill_state'] ?>" />
	<?php } ?>
	<input type="submit" class="btn" />
</form>
</p>