<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="MerchantCode" value="<?php echo htmlentities($data->merchant) ?>" />

	<input type="hidden" name="Charge" value="<?php echo htmlentities($data->charge) ?>">
	<input type="hidden" name="CurrencyCode" value="<?php echo htmlentities($data->currency) ?>" />
	<input type="hidden" name="Installments" value="0" />
	<input type="hidden" name="TransactionType" value="1"/>
	<input type="hidden" name="Param1" value="12345"/>
	<input type="hidden" name="Param2" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>"/>

	<input type="submit" class="btn" />
</form>
</p>