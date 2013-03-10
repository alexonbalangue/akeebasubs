<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $url ?>"  method="post" id="paymentForm">
	
	<input type="hidden" name="merchantnumber" value="<?php echo $data->merchant ?>" />
	<input type="hidden" name="accepturl" value="<?php echo $data->success ?>" />
	<input type="hidden" name="cancelurl" value="<?php echo $data->cancel ?>" />
	<input type="hidden" name="callbackurl" value="<?php echo $data->postback ?>" />
	<input type="hidden" name="orderid" value="<?php echo $data->orderid ?>" />
	
	<?php /** @see http://tech.epay.dk/Currency-codes_60.html */ ?>
	<input type="hidden" name="currency" value="<?php echo $data->currency ?>" />
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	
	
	<?php /** @see http://tech.epay.dk/Specification_85.html#paymenttype */ ?>
	<input type="hidden" name="paymenttype" value="<?php echo $data->cardtypes ?>" />
	<input type="hidden" name="instantcapture" value="<?php echo $data->instantcapture ?>" />
	<input type="hidden" name="instantcallback" value="<?php echo $data->instantcallback ?>" />
	
	<input type="hidden" name="language" value="<?php echo $data->language ?>" />
	<input type="hidden" name="ordertext" value="<?php echo $data->ordertext ?>" />
	
	<input type="hidden" name="windowstate" value="<?php echo $data->windowstate ?>" />
	<input type="hidden" name="ownreceipt" value="<?php echo $data->ownreceipt ?>" />
	<input type="hidden" name="hash" value="<?php echo $data->md5 ?>" />
	<!--  -->
	<input type="image" src="http://tech.epay.dk/kb_upload/image/epay_logos/uk.gif" border="0" name="submit" alt="Epay Payments" id="epaydksubmit" />
</form>
</p>