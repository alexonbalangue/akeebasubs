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
	<input type="hidden" name="instId" value="<?php echo htmlentities($data->instid) ?>" />
	<input type="hidden" name="cartId" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	<input type="hidden" name="amount" value="<?php echo htmlentities($subscription->gross_amount) ?>" />
	<input type="hidden" name="currency_code" value="<?php echo htmlentities($data->currency) ?>" />
	<input type="hidden" name="desc" value="<?php echo htmlentities($level->akeebasubs_level_id) ?> - <?php echo htmlentities($level->title . ' - [ ' . $user->username . ' ]') ?>" />
	
	<input type="hidden" name="name" value="<?php echo htmlentities($data->firstname) ?> <?php echo htmlentities($data->lastname) ?>" />
	<input type="hidden" name="address1" value="<?php echo $kuser->address." ".$kuser->address2.", ".$kuser->city.", ".$kuser->state ?>" />
	<input type="hidden" name="postcode" value="<?php echo $kuser->zip ?>" />
	<input type="hidden" name="country" value="<?php echo $kuser->country ?>" />
	<input type="hidden" name="email" value="<?php echo $kuser->email ?>" />

	<input type="hidden" name="MC_callback" value="<?php echo $data->postback ?>" />
	
	<input type="hidden" name="testMode" value="<?php echo $data->test?>" />

	<input type="submit" />
</form>
</p>