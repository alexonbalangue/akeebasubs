<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="cmd" value="_xclick" />
	<input type="hidden" name="business" value="<?php echo htmlentities($data->merchant) ?>" />
	<input type="hidden" name="return" value="<?php echo $data->success ?>" />
	<input type="hidden" name="cancel_return" value="<?php echo $data->cancel ?>" />
	<input type="hidden" name="notify_url" value="<?php echo $data->postback ?>" />
	<input type="hidden" name="custom" value="<?php echo htmlentities($subscription->id) ?>" />

	<input type="hidden" name="item_number" value="<?php echo htmlentities($level->id) ?>" />
	<input type="hidden" name="item_name" value="<?php echo htmlentities($level->title . ' - [ ' . $user->username . ' ]') ?>" />
	<input type="hidden" name="amount" value="<?php echo htmlentities($subscription->net_amount) ?>" />
	<input type="hidden" name="tax" value="<?php echo htmlentities($subscription->tax_amount) ?>" />
	<input type="hidden" name="currency_code" value="<?php echo htmlentities($data->currency) ?>" />

	<input type="hidden" name="first_name" value="<?php echo htmlentities($data->firstname) ?>" />
	<input type="hidden" name="last_name" value="<?php echo htmlentities($data->lastname) ?>" />

	<input type="hidden" name="address_override" value="0">
	<input type="hidden" name="address1" value="<?php echo $kuser->address ?>">
	<input type="hidden" name="address2" value="<?php echo $kuser->address2 ?>">
	<input type="hidden" name="city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="zip" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="country" value="<?php echo $kuser->country ?>">

	<input type="hidden" name="no_note" value="1" />
	<input type="hidden" name="no_shipping" value="1" />
	<?php if($cbt = $this->params->get('cbt','')): ?>
	<input type="hidden" name="cbt" value="<?php echo htmlentities($cbt) ?>" />
	<?php endif; ?>
	<?php if($cpp_header_image = $this->params->get('cpp_header_image','')): ?>
	<input type="hidden" name="cpp_header_image" value="<?php echo htmlentities($cpp_header_image)?>" />
	<?php endif; ?>
	<?php if($cpp_headerback_color = $this->params->get('cpp_headerback_color','')): ?>
	<input type="hidden" name="cpp_headerback_color" value="<?php echo htmlentities($cpp_headerback_color)?>" />
	<?php endif; ?>
	<?php if($cpp_headerborder_color = $this->params->get('cpp_headerborder_color','')): ?>
	<input type="hidden" name="cpp_headerborder_color" value="<?php echo htmlentities($cpp_headerborder_color)?>" />
	<?php endif; ?>

	<input type="image" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/btn/btn_paynowCC_LG.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" id="paypalsubmit" />
	<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
</form>
</p>