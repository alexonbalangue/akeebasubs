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
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	<?php if($this->params->get('sandbox',0)) { ?>
	<input type="hidden" name="test_transaction" value="<?php echo $data->test_transaction ?>" />
	<input type="hidden" name="test_success_url" value="<?php echo $data->test_success_url ?>" />
	<?php } ?>
	<input type="hidden" name="callback_url" value="<?php echo $data->callback_url ?>" />
	<input type="hidden" name="success_url" value="<?php echo $data->success_url ?>" />
	<input type="hidden" name="cancel_url" value="<?php echo $data->cancel_url ?>" />
	<input type="hidden" name="order_id" value="<?php echo $data->order_id ?>" />
	<input type="hidden" name="description" value="<?php echo $data->description ?>" />
	<input type="hidden" name="billing_fullname" value="<?php echo $data->billing_fullname ?>" />
	<input type="hidden" name="billing_address" value="<?php echo $data->billing_address ?>" />
	<input type="hidden" name="billing_postcode" value="<?php echo $data->billing_postcode ?>" />
	<input type="hidden" name="email_address" value="<?php echo $data->email_address ?>" />
	<input type="submit" class="btn" />
</form>
</p>