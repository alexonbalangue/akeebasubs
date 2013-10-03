<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="https://gateway.cardstream.com/hosted/"  method="post" id="paymentForm">
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	<input type="hidden" name="merchantID" value="<?php echo $data->merchantID ?>" />
	<input type="hidden" name="action" value="SALE" />
	<input type="hidden" name="type" value="1" />
	<input type="hidden" name="countryCode" value="<?php echo $data->countryCode ?>" />
	<input type="hidden" name="currencyCode" value="<?php echo $data->currencyCode ?>" />
	<input type="hidden" name="transactionUnique" value="<?php echo $data->transactionUnique ?>" />
	<input type="hidden" name="orderRef" value="<?php echo $data->orderRef ?>" />
	<input type="hidden" name="customerName" value="<?php echo $data->customerName ?>" />
	<input type="hidden" name="customerEmail" value="<?php echo $data->customerEmail ?>" />
	<input type="hidden" name="customerAddress" value="<?php echo $data->customerAddress ?>" />
	<input type="hidden" name="customerPostCode" value="<?php echo $data->customerPostCode ?>" />
	<input type="hidden" name="returnInternalData" value="N" />
	<input type="hidden" name="item1Description" value="<?php echo $data->item1Description ?>" />
	<input type="hidden" name="item1Quantity" value="<?php echo $data->item1Quantity ?>" />
	<input type="hidden" name="item1GrossValue" value="<?php echo $data->item1GrossValue ?>" />
	<input type="hidden" name="taxValue" value="<?php echo $data->taxValue ?>" />
	<input type="hidden" name="redirectURL" value="<?php echo $data->redirectURL ?>" />
	<input type="hidden" name="callbackURL" value="<?php echo $data->callbackURL ?>" />
	<input type="submit" class="btn" />
</form>
</p>