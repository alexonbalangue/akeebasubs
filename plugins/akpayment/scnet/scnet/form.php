<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="gate" value="<?php echo $data->gate ?>" />
	<input type="hidden" name="adminemail" value="<?php echo $data->adminemail ?>" />
	<input type="hidden" name="process" value="<?php echo $data->process ?>" />
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	<input type="hidden" name="returl" value="<?php echo $data->returl ?>" />
	<input type="hidden" name="invoice_id" value="<?php echo $data->invoice_id ?>" />
	<input type="hidden" name="items" value="<?php echo $data->items ?>" />
	<input type="hidden" name="cust" value="<?php echo $data->cust ?>" />
	<input type="hidden" name="street" value="<?php echo $data->street ?>" />
	<input type="hidden" name="city" value="<?php echo $data->city ?>" />
	<input type="hidden" name="country" value="<?php echo $data->country ?>" />
	<input type="hidden" name="postcode" value="<?php echo $data->postcode ?>" />
	<input type="hidden" name="currency" value="<?php echo $data->currency ?>" />
	<?php if(!empty($data->image)) { ?>
	<input type="hidden" name="image" value="<?php echo $data->image ?>" />
	<?php } ?>
	<?php if(!empty($data->backgroundimage)) { ?>
	<input type="hidden" name="backgroundimage" value="<?php echo $data->backgroundimage ?>" />
	<?php } ?>
	<?php if(!empty($data->tablebgcolour)) { ?>
	<input type="hidden" name="tablebgcolour" value="<?php echo $data->tablebgcolour ?>" />
	<?php } ?>
	<input type="submit" class="btn" />
</form>
</p>