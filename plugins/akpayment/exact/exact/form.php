<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
	<input name="x_login" type="hidden" value="<?php echo $data->x_login; ?>"> 
	<input name="x_fp_sequence" type="hidden" value="<?php echo $data->x_fp_sequence; ?>">
	<input name="x_fp_timestamp" type="hidden" value="<?php echo $data->x_fp_timestamp; ?>">
	<input name="x_currency_code" type="hidden" value="<?php echo $data->x_currency_code; ?>">
	<input name="x_amount" type="hidden" value="<?php echo $data->x_amount; ?>">
	<?php if(isset($data->x_tax)) { ?>
	<input type="hidden" name="x_tax" value="<?php echo $data->x_tax ?>" />
	<?php } ?>
	<input name="x_show_form" type="hidden" value="<?php echo $data->x_show_form; ?>">
	<input name="x_line_item" type="hidden" value="<?php echo $data->x_line_item; ?>">
	<input name="x_po_num" type="hidden" value="<?php echo $data->x_po_num; ?>">
	<input name="x_first_name" type="hidden" value="<?php echo $data->x_first_name; ?>">
	<input name="x_last_name" type="hidden" value="<?php echo $data->x_last_name; ?>">
	<?php if(isset($data->x_company)) { ?>
	<input type="hidden" name="x_company" value="<?php echo $data->x_company ?>" />
	<?php } ?>
	<input name="x_address" type="hidden" value="<?php echo $data->x_address; ?>">
	<?php if(isset($data->x_state)) { ?>
	<input type="hidden" name="x_state" value="<?php echo $data->x_state ?>" />
	<?php } ?>
	<input name="x_city" type="hidden" value="<?php echo $data->x_city; ?>">
	<input name="x_zip" type="hidden" value="<?php echo $data->x_zip; ?>">
	<input name="x_country" type="hidden" value="<?php echo $data->x_country; ?>">
	<input name="x_email" type="hidden" value="<?php echo $data->x_email; ?>">
	<input name="x_relay_response" type="hidden" value="<?php echo $data->x_relay_response; ?>">
	<input name="x_relay_url" type="hidden" value="<?php echo $data->x_relay_url; ?>">
	<input name="x_test_request" type="hidden" value="<?php echo $data->x_test_request; ?>">
	<input name="x_fp_hash" type="hidden" value="<?php echo $data->x_fp_hash; ?>">
	<input type="submit" class="btn" />
</form>
</p>