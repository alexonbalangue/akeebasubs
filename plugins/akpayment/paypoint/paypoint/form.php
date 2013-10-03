<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="https://www.secpay.com/java-bin/ValCard"  method="post" id="paymentForm">
	<input type="hidden" name="merchant" value="<?php echo $data->merchant ?>">
	<input type="hidden" name="trans_id" value="<?php echo $data->trans_id ?>">
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>">
	<input type="hidden" name="currency" value="<?php echo $data->currency ?>">
	<input type="hidden" name="bill_name" value="<?php echo $data->bill_name ?>">
	<?php if(! empty($data->bill_addr_1)) { ?>
	<input type="hidden" name="bill_addr_1" value="<?php echo $data->bill_addr_1 ?>">
	<?php } ?>
	<?php if(! empty($data->bill_addr_2)) { ?>
	<input type="hidden" name="bill_addr_2" value="<?php echo $data->bill_addr_2 ?>">
	<?php } ?>
	<input type="hidden" name="bill_city" value="<?php echo $data->bill_city ?>">
	<?php if(! empty($data->bill_company)) { ?>
	<input type="hidden" name="bill_company" value="<?php echo $data->bill_company ?>">
	<?php } ?>
	<input type="hidden" name="bill_country" value="<?php echo $data->bill_country ?>">
	<?php if(! empty($data->bill_post_code)) { ?>
	<input type="hidden" name="bill_post_code" value="<?php echo $data->bill_post_code ?>">
	<?php } ?>
	<?php if(! empty($data->bill_state)) { ?>
	<input type="hidden" name="bill_state" value="<?php echo $data->bill_state ?>">
	<?php } ?>
	<input type="hidden" name="bill_email" value="<?php echo $data->bill_email ?>">
	<input type="hidden" name="test_status" value="<?php echo $data->test_status ?>">
	<input type="hidden" name="order" value="prod=<?php echo $data->prod ?>,item_amount=<?php echo $data->amount ?>">
	<input type="hidden" name="usage_type" value="E">
	<input type="hidden" name="cb_post" value="true">
	<input type="hidden" name="ssl_cb" value="<?php echo $data->ssl_cb ?>">
	<input type="hidden" name="md_flds" value="trans_id:amount:callback">
	<?php if(! empty($data->merchant_logo)) { ?>
	<input type="hidden" name="options" value="merchant_logo=<img src='<?php echo $data->merchant_logo ?>' height='100px' width='750px' class='floatright'>">
	<?php } ?>
	<input type="hidden" name="callback" value="<?php echo $data->callback ?>">
	<input type="hidden" name="digest" value="<?php echo $data->digest ?>">
	<input type="submit" class="btn" />
</form>
</p>