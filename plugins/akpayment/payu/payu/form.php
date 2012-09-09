<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="key" value="<?php echo htmlentities($data->merchant) ?>" />
	<input type="hidden" name="amount" value="<?php echo htmlentities($subscription->gross_amount) ?>" />
	<input type="hidden" name="txnid" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	
	<input type="hidden" name="hash" value="<?php echo $data->checksum ?>" />
	<input type="hidden" name="firstname" id="firstname" value="<?php echo htmlentities($data->firstname) ?>" />
	<input type="hidden" name="email" id="email" value="<?php echo htmlentities($data->email) ?>" />
	<input type="hidden" name="phone" value="<?php echo htmlentities($data->phno) ?>" />
	<input type="hidden" name="productinfo" value="<?php echo $level->title ?>" />
	<input type="hidden" name="surl" value="<?php echo $data->postback ?>" />
	<input type="hidden" name="furl" value="<?php echo $data->postback ?>" />
	
	
	<input type="submit" class="btn" />
</form>
</p>