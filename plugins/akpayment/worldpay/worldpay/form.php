<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="instId" value="<?php echo $data->instid ?>" />
	<input type="hidden" name="cartId" value="<?php echo $subscription->akeebasubs_subscription_id ?>" />
	<input type="hidden" name="amount" value="<?php echo $subscription->gross_amount ?>" />
	<input type="hidden" name="currency" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="desc" value="<?php echo $level->akeebasubs_level_id ?> - <?php echo $level->title . ' - [ ' . $user->username . ' ]' ?>" />
	
	<?php if(!empty($kuser->address1)): ?>
	<input type="hidden" name="name" value="<?php echo htmlentities($data->firstname) ?> <?php echo htmlentities($data->lastname) ?>" />
	<input type="hidden" name="address1" value="<?php echo $kuser->address1 ?>" />
	<input type="hidden" name="address2" value="<?php echo $kuser->address2 ?>" />
	<input type="hidden" name="city" value="<?php echo $kuser->city ?>" />
	<input type="hidden" name="postcode" value="<?php echo $kuser->zip ?>" />
	<input type="hidden" name="country" value="<?php echo $kuser->country ?>" />
	<input type="hidden" name="email" value="<?php echo $kuser->email ?>" />
	<?php endif; ?>

	<input type="hidden" name="MC_callback" value="<?php echo $data->postback ?>" />
	
	<?php if($data->test): ?>
	<input type="hidden" name="testMode" value="100" />
	<?php endif; ?>

	<input type="submit" class="btn" />
</form>
</p>