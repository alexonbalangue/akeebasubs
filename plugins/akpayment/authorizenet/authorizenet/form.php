<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="x_version" value="<?php echo $data->x_version ?>" />
	<input type="hidden" name="x_type" value="<?php echo $data->x_type ?>" />
	<input type="hidden" name="x_login" value="<?php echo $data->x_login ?>" />
	<input type="hidden" name="x_amount" value="<?php echo $data->x_amount ?>" />
	<input type="hidden" name="x_tax" value="<?php echo $data->x_tax ?>" />
	<input type="hidden" name="x_currency_code" value="<?php echo $data->x_currency_code ?>" />
	<input type="hidden" name="x_description" value="<?php echo $data->x_description ?>" />
	<input type="hidden" name="x_invoice_num" value="<?php echo $data->x_invoice_num ?>" />
	<input type="hidden" name="x_fp_sequence" value="<?php echo $data->x_fp_sequence ?>" />
	<input type="hidden" name="x_fp_timestamp" value="<?php echo $data->x_fp_timestamp ?>" />
	<input type="hidden" name="x_fp_hash" value="<?php echo $data->x_fp_hash ?>" />
	<input type="hidden" name="x_test_request" value="<?php echo $data->x_test_request ?>" />
	<input type="hidden" name="x_show_form" value="<?php echo $data->x_show_form ?>" />
	<input type="hidden" name="x_first_name" value="<?php echo $data->x_first_name ?>" />
	<input type="hidden" name="x_last_name" value="<?php echo $data->x_last_name ?>" />
	<?php if(isset($data->x_company)) { ?>
	<input type="hidden" name="x_company" value="<?php echo $data->x_company ?>" />
	<?php } ?>
	<input type="hidden" name="x_address" value="<?php echo $data->x_address ?>" />
	<input type="hidden" name="x_city" value="<?php echo $data->x_city ?>" />
	<input type="hidden" name="x_zip" value="<?php echo $data->x_zip ?>" />
	<?php if(isset($data->x_state)) { ?>
	<input type="hidden" name="x_state" value="<?php echo $data->x_state ?>" />
	<?php } ?>
	<input type="hidden" name="x_country" value="<?php echo $data->x_country ?>" />
	<input type="hidden" name="x_email" value="<?php echo $data->x_email ?>" />
	<input type="hidden" name="x_cancel_url" value="<?php echo $data->x_cancel_url ?>" />
	<input type="hidden" name="x_relay_response" value="TRUE" />
	<input type="hidden" name="x_relay_url" value="<?php echo $data->x_relay_url ?>" />
	<input type="submit" class="btn" />
</form>
</p>