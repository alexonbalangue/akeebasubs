<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="version" value="1" />
	<input type="hidden" name="shopID" value="<?php echo $data->shopID ?>" />
	<input type="hidden" name="priceAmount" value="<?php echo $data->priceAmount ?>" />
	<input type="hidden" name="priceCurrency" value="<?php echo $data->priceCurrency ?>" />
	<input type="hidden" name="description" value="<?php echo $data->description ?>" />
	<input type="hidden" name="referenceID" value="<?php echo $data->referenceID ?>" />
	<input type="hidden" name="signature" value="<?php echo $data->signature ?>" />
	<input type="submit" class="btn" />
</form>
</p>