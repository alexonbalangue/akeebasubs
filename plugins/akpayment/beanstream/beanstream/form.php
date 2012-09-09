<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="merchant_id" value="<?php echo $data->merchant_id ?>" />
	<input type="hidden" name="trnOrderNumber" value="<?php echo $data->trnOrderNumber ?>" />
	<input type="hidden" name="trnAmount" value="<?php echo $data->trnAmount ?>" />
	<input type="hidden" name="declinedPage" value="<?php echo $data->declinedPage ?>" />
	<input type="hidden" name="approvedPage" value="<?php echo $data->approvedPage ?>" />
	<input type="hidden" name="ordName" value="<?php echo $data->ordName ?>" />
	<input type="hidden" name="ordEmailAddress" value="<?php echo $data->ordEmailAddress ?>" />
	<input type="hidden" name="ordAddress1" value="<?php echo $data->ordAddress1 ?>" />
	<input type="hidden" name="ordAddress2" value="<?php echo $data->ordAddress2 ?>" />
	<input type="hidden" name="ordCity" value="<?php echo $data->ordCity ?>" />
	<input type="hidden" name="ordProvince" value="<?php echo $data->ordProvince ?>" />
	<input type="hidden" name="ordPostalCode" value="<?php echo $data->ordPostalCode ?>" />
	<input type="hidden" name="ordCountry" value="<?php echo $data->ordCountry ?>" />
	<input type="hidden" name="trnLanguage" value="<?php echo $data->trnLanguage ?>" />
	<input type="submit" class="btn" />
</form>
</p>