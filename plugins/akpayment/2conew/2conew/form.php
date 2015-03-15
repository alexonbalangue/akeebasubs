<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
	<input type="hidden" name="sid" value="<?php echo $data->sid?>" />
	<input type="hidden" name="mode" value="2CO" />

	<input type="hidden" name="li_0_type" value="product" />
	<input type="hidden" name="li_0_product_id" value="<?php echo $level->akeebasubs_level_id ?>" />
	<input type="hidden" name="li_0_name" value="<?php echo $level->title ?>" />
	<input type="hidden" name="li_0_description" value="<?php echo $level->title . ' - [ ' . $user->username . ' ]' ?>" />
	<input type="hidden" name="li_0_quantity" value="1" />
	<?php if($data->recurring == 0): ?>
	<input type="hidden" name="li_0_price" value="<?php echo sprintf('%02.2f',$subscription->gross_amount) ?>" />
	<?php elseif($data->recurring == 1): ?>
	<input type="hidden" name="li_0_price" value="<?php echo sprintf('%02.2f',$subscription->gross_amount) ?>" />
	<input type="hidden" name="li_0_recurrence" value="<?php echo $data->p3 . ' ' . $data->t3 ?>" />
	<input type="hidden" name="li_0_duration" value="forever" />
	<?php elseif($data->recurring == 2): ?>
	<input type="hidden" name="li_0_price" value="<?php echo sprintf('%02.2f',$subscription->recurring_amount) ?>" />
	<input type="hidden" name="li_0_recurrence" value="<?php echo $data->p3 . ' ' . $data->t3 ?>" />
	<input type="hidden" name="li_0_duration" value="forever" />
	<input type="hidden" name="li_0_startup_fee" value="<?php echo $subscription->gross_amount - $subscription->recurring_amount ?>" />
	<?php endif; ?>
	<input type="hidden" name="li_0_tangible" value="N" />

	<?php if($data->params->get('demo',0)): ?>
	<input type="hidden" name="demo" value="Y" />
	<?php endif;?>

	<input type="hidden" name="currency_code" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="lang" value="<?php echo $data->params->get('lang','en') ?>" />
	<input type="hidden" name="merchant_order_id" value="<?php echo $subscription->akeebasubs_subscription_id ?>" />
	<input type="hidden" name="pay_method" value="<?php echo strtoupper($data->params->get('pay_method','cc')) ?>" />
	<input type="hidden" name="x_receipt_link_url" value="<?php echo $data->x_receipt_link_url ?>" />

	<input type="hidden" name="card_holder_name" value="<?php echo $data->name ?>" />
	<input type="hidden" name="street_address" value="<?php echo $kuser->address1 ?>">
	<input type="hidden" name="street_address2" value="<?php echo $kuser->address2 ?>">
	<input type="hidden" name="city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="zip" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="country" value="<?php echo $kuser->country ?>">
	<input type="hidden" name="email" value="<?php echo $data->email ?>">

	<input class="btn" type="submit" />
</form>
</p>