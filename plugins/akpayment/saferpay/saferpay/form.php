<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($paymentData->URL) ?>"  method="post" id="paymentForm">
	<input name="DATA" type="hidden" value="<?php echo htmlentities($paymentData->DATA); ?>"> 
	<input name="SIGNATURE" type="hidden" value="<?php echo htmlentities($paymentData->SIGNATURE); ?>"> 
	<input type="submit" class="btn" />
</form>
</p>