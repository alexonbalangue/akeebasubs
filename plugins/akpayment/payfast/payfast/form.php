<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="merchant_id" value="<?php echo $data->merchant_id ?>" />
	<input type="hidden" name="merchant_key" value="<?php echo $data->merchant_key ?>" />
	<input type="hidden" name="return_url" value="<?php echo $data->return_url ?>" />
	<input type="hidden" name="cancel_url" value="<?php echo $data->cancel_url ?>" />
	<input type="hidden" name="notify_url" value="<?php echo $data->notify_url ?>" />
	<input type="hidden" name="name_first" value="<?php echo $data->name_first ?>" />
	<input type="hidden" name="name_last" value="<?php echo $data->name_last ?>" />
	<input type="hidden" name="email_address" value="<?php echo $data->email_address ?>" />
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	<input type="hidden" name="item_name" value="<?php echo $data->item_name ?>" />
	<input type="hidden" name="custom_str1" value="<?php echo $data->custom_str1 ?>" />
	<input type="hidden" name="signature" value="<?php echo $data->signature ?>" />
	<input type="submit" class="btn" />
</form>
</p>