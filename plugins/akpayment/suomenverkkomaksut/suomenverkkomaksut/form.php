<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="MERCHANT_ID" value="<?php echo $data->merchant_id ?>" />
	<input type="hidden" name="ORDER_NUMBER" value="<?php echo $data->order_number ?>" />
	<input type="hidden" name="ORDER_DESCRIPTION" value="<?php echo $data->order_description ?>" />
	<input type="hidden" name="CURRENCY" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="RETURN_ADDRESS" value="<?php echo $data->return_address ?>" />
	<input type="hidden" name="CANCEL_ADDRESS" value="<?php echo $data->cancel_address ?>" />
	<input type="hidden" name="NOTIFY_ADDRESS" value="<?php echo $data->notify_address ?>" />
	<input type="hidden" name="TYPE" value="<?php echo $data->type ?>" />
	<input type="hidden" name="CULTURE" value="<?php echo $data->culture ?>" />
	<input type="hidden" name="CONTACT_EMAIL" value="<?php echo $data->contact_email ?>" />
	<input type="hidden" name="CONTACT_FIRSTNAME" value="<?php echo $data->contact_firstname ?>" />
	<input type="hidden" name="CONTACT_LASTNAME" value="<?php echo $data->contact_lastname ?>" />
	<?php if(isset($data->contact_company)) { ?>
	<input type="hidden" name="CONTACT_COMPANY" value="<?php echo $data->contact_company ?>" />
	<?php } ?>
	<input type="hidden" name="CONTACT_ADDR_STREET" value="<?php echo $data->contact_addr_street ?>" />
	<input type="hidden" name="CONTACT_ADDR_ZIP" value="<?php echo $data->contact_addr_zip ?>" />
	<input type="hidden" name="CONTACT_ADDR_CITY" value="<?php echo $data->contact_addr_city ?>" />
	<input type="hidden" name="CONTACT_ADDR_COUNTRY" value="<?php echo $data->contact_addr_country ?>" />
	<input type="hidden" name="INCLUDE_VAT" value="<?php echo $data->include_vat ?>" />
	<input type="hidden" name="ITEMS" value="<?php echo $data->items ?>" />
	<input type="hidden" name="ITEM_TITLE[0]" value="<?php echo $data->item_title_0 ?>" />
	<input type="hidden" name="ITEM_AMOUNT[0]" value="<?php echo $data->item_amount_0 ?>" />
	<input type="hidden" name="ITEM_PRICE[0]" value="<?php echo $data->item_price_0 ?>" />
	<input type="hidden" name="ITEM_TAX[0]" value="<?php echo $data->item_tax_0 ?>" />
	<input type="hidden" name="AUTHCODE" value="<?php echo $data->authcode ?>" />
	<input type="submit" class="btn" />
</form>
</p>