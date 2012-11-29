<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
	<input type="hidden" name="key" value="<?php echo $data->key ?>" />
	<input type="hidden" name="signature" value="<?php echo $data->signature ?>" />
	<input type="hidden" name="callback" value="<?php echo $data->callback ?>" />
	<input type="hidden" name="redirect" value="<?php echo $data->redirect ?>" />
	<input type="hidden" name="name" value="<?php echo $data->name ?>" />
	<input type="hidden" name="description" value="<?php echo $data->description ?>" />
	<input type="hidden" name="destinationid" value="<?php echo $data->destinationid ?>" />
	<input type="hidden" name="amount" value="<?php echo $data->amount ?>" />
	<input type="hidden" name="shipping" value="<?php echo $data->shipping ?>" />
	<input type="hidden" name="tax" value="<?php echo $data->tax ?>" />
	<input type="hidden" name="orderid" value="<?php echo $data->orderid ?>" />
	<input type="hidden" name="timestamp" value="<?php echo $data->timestamp ?>" />
	<input type="submit" class="btn" />
</form>
</p>