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
	<input type="hidden" name="currency" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="session_id" value="<?php echo $data->session_id ?>" />
	<input type="hidden" name="token" value="<?php echo $data->token ?>" />
	<input type="hidden" name="test_mode" value="<?php echo $data->test_mode ?>" />
	<input type="hidden" name="display_text" value="<?php echo $data->display_text ?>" />
	<input type="hidden" name="language" value="<?php echo $data->language ?>" />
	<input type="hidden" name="txt1" value="<?php echo $data->txt1 ?>" />
	<input type="hidden" name="txt2" value="<?php echo $data->txt2 ?>" />
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	<?php if(isset($data->service_name)) { ?>
	<input type="hidden" name="service_name" value="<?php echo $data->service_name ?>" />
	<?php } ?>
	<input type="submit" class="btn" />
</form>
</p>