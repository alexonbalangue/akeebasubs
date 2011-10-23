<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="sid" value="<?php echo htmlentities($data->sid)?>" />
	<input type="hidden" name="total" value="<?php echo htmlentities($subscription->gross_amount) ?>" />
	<input type="hidden" name="cart_order_id" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	
	<input type="hidden" name="id_type" value="1" />
	<input type="hidden" name="c_prod" value="<?php echo htmlentities($level->akeebasubs_level_id) ?>" />
	<input type="hidden" name="c_name" value="<?php echo htmlentities($level->title) ?>" />
	<input type="hidden" name="c_description" value="<?php echo htmlentities($level->title . ' - [ ' . $user->username . ' ]') ?>" />
	<input type="hidden" name="c_price" value="<?php echo htmlentities($subscription->net_amount) ?>" />
	
	<?php if($data->params->get('demo',0)): ?>
	<input type="hidden" name="demo" value="Y" />
	<?php endif;?>
	<input type="hidden" name="fixed" value="Y" />
	<input type="hidden" name="lang" value="<?php echo $data->params->get('lang','en') ?>" />
	<input type="hidden" name="merchant_order_id" value="<?php echo htmlentities($subscription->id) ?>" />
	<input type="hidden" name="pay_method" value="<?php echo htmlentities( strtoupper($data->params->get('pay_method','cc')) ) ?>" />
	<?php if($data->params->get('skip_landing',0)): ?>
	<input type="hidden" name="skip_landing" value="1" />
	<?php endif; ?>
	<input type="hidden" name="x_receipt_link_url" value="<?php echo htmlentities($data->x_receipt_link_url) ?>" />
	
	<input type="hidden" name="card_holder_name" value="<?php echo htmlentities($data->name) ?>" />
	<input type="hidden" name="street_address" value="<?php echo $kuser->address ?>">
	<input type="hidden" name="street_address2" value="<?php echo $kuser->address2 ?>">
	<input type="hidden" name="city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="zip" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="country" value="<?php echo $kuser->country ?>">
	<input type="hidden" name="email" value="<?php echo $data->email ?>">

	<input type="submit" />
</form>
</p>