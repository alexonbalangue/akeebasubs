<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->URL) ?>" method="post" id="paymentForm">
	<input type="hidden" name="MERCHANT_ID" value="<?php echo $data->MERCHANT_ID ?>" />
	<input type="hidden" name="ORDER_ID" value="<?php echo $data->ORDER_ID ?>" />
	<?php if(!empty($data->ACCOUNT)) { ?>
	<input type="hidden" name="ACCOUNT" value="<?php echo $data->ACCOUNT ?>" />
	<?php } ?>
	<input type="hidden" name="AMOUNT" value="<?php echo $data->AMOUNT ?>" />
	<input type="hidden" name="CURRENCY" value="<?php echo $data->CURRENCY ?>" />
	<input type="hidden" name="TIMESTAMP" value="<?php echo $data->TIMESTAMP ?>" />
	<input type="hidden" name="AUTO_SETTLE_FLAG" value="<?php echo $data->AUTO_SETTLE_FLAG ?>" />
	<input type="hidden" name="COMMENT1" value="<?php echo $data->COMMENT1 ?>" />
	<input type="hidden" name="SHA1HASH" value="<?php echo $data->SHA1HASH ?>" />
	<input type="submit" class="btn" />
</form>
</p>