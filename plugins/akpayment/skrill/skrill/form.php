<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="pay_to_email" value="<?php echo htmlentities($data->merchant) ?>" />
	<?php if($recipient_description = $this->params->get('company','')): ?>
	<input type="hidden" name="recipient_description" value="<?php echo htmlentities($recipient_description) ?>" />
	<?php endif; ?>
	<input type="hidden" name="transaction_id" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	
	<input type="hidden" name="return_url" value="<?php echo $data->success ?>" />
	<input type="hidden" name="cancel_url" value="<?php echo $data->cancel ?>" />
	<input type="hidden" name="status_url" value="<?php echo $data->postback ?>" />
	<input type="hidden" name="language" value="<?php echo $this->params->get('language','EN') ?>" />
	<?php if($confirmation_note = $this->params->get('confnotice','')): ?>
	<input type="hidden" name="confirmation_note" value="<?php echo htmlentities($confirmation_note) ?>" />
	<?php endif; ?>
	<?php if($logo_url = $this->params->get('header','')): ?>
	<input type="hidden" name="logo_url" value="<?php echo htmlentities($header) ?>" />
	<?php endif; ?>
	
	<input type="hidden" name="pay_from_email" value="<?php echo $user->email ?>">
	<input type="hidden" name="firstname" value="<?php echo htmlentities($data->firstname) ?>" />
	<input type="hidden" name="lastname" value="<?php echo htmlentities($data->lastname) ?>" />
	<input type="hidden" name="address" value="<?php echo $kuser->address1 ?>">
	<input type="hidden" name="address2" value="<?php echo $kuser->address2 ?>">
	<input type="hidden" name="city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="postal_code" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="country" value="<?php echo $data->country ?>">

	<input type="hidden" name="amount" value="<?php echo htmlentities($subscription->gross_amount) ?>" />
	<input type="hidden" name="currency" value="<?php echo htmlentities($data->currency) ?>" />
	<?php if($subscription->tax_amount): ?>
	<input type="hidden" name="amount2_description" value="<?php echo htmlentities(JText::_('PLG_AKPAYMENT_SKRILL_LBL_SUBSCRIPTIONPRICE'))?>" />
	<input type="hidden" name="amount2" value="<?php echo htmlentities($subscription->net_amount) ?>" />
	<input type="hidden" name="amount3_description" value="<?php echo htmlentities(JText::_('PLG_AKPAYMENT_SKRILL_LBL_TAXES'))?>" />
	<input type="hidden" name="amount" value="<?php echo htmlentities($subscription->tax_amount) ?>" />
	<?php endif; ?>
	
	<input type="hidden" name="detail1_description" value="<?php echo htmlentities(JText::_('PLG_AKPAYMENT_SKRILL_LBL_SUBSCRIPTIONLEVEL'))?>" />
	<input type="hidden" name="detail1_text" value="<?php echo htmlentities($level->akeebasubs_level_id) ?> - <?php echo htmlentities($level->title)?>" />
	<input type="hidden" name="detail2_description" value="<?php echo htmlentities(JText::_('PLG_AKPAYMENT_SKRILL_LBL_USERNAME'))?>" />
	<input type="hidden" name="detail2_text" value="<?php echo htmlentities($user->username) ?>" />

	<input type="submit" class="btn" />
</form>
</p>