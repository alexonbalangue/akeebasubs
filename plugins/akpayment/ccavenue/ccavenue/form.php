<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="Merchant_Id" value="<?php echo htmlentities($data->merchant) ?>" />
	<input type="hidden" name="Amount" value="<?php echo htmlentities($subscription->gross_amount) ?>" />
	<input type="hidden" name="Order_Id" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	<input type="hidden" name="Redirect_Url" value="<?php echo $data->postback ?>" />
	<input type="hidden" name="Checksum" value="<?php echo $data->checksum ?>" />
	<input type="hidden" name="billing_cust_name" value="<?php echo htmlentities($data->firstname) ?> <?php echo htmlentities($data->lastname) ?>" />
	<input type="hidden" name="billing_cust_address" value="<?php echo $kuser->address1.(empty($kuser->address2)?'':', '.$kuser->address2) ?>">
	<input type="hidden" name="billing_cust_country" value="<?php echo $kuser->country ?>">
	<input type="hidden" name="billing_cust_state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="billing_zip" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="billing_cust_city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="billing_zip_code" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="delivery_cust_name" value="<?php echo htmlentities($data->firstname) ?> <?php echo htmlentities($data->lastname) ?>" />
	<input type="hidden" name="delivery_cust_address" value="<?php echo $kuser->address1.(empty($kuser->address2)?'':', '.$kuser->address2) ?>">
	<input type="hidden" name="delivery_cust_country" value="<?php echo $kuser->country ?>">
	<input type="hidden" name="delivery_cust_state" value="<?php echo $kuser->state ?>">
	<input type="hidden" name="delivery_zip" value="<?php echo $kuser->zip ?>">
	<input type="hidden" name="delivery_cust_city" value="<?php echo $kuser->city ?>">
	<input type="hidden" name="delivery_zip_code" value="<?php echo $kuser->zip ?>">
	
	<input type="submit" class="btn" />
</form>
</p>