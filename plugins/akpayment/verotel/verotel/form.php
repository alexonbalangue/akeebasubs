<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="verotel_id" value="<?php echo $data->verotel_id ?>" />
	<input type="hidden" name="website_id" value="<?php echo $data->website_id ?>" />
	<input type="hidden" name="verotel_usercode" value="<?php echo $data->verotel_usercode ?>" />
	<input type="hidden" name="verotel_passcode" value="<?php echo $data->verotel_passcode ?>" />
	<input type="hidden" name="verotel_custom1" value="<?php echo $data->verotel_custom1 ?>" />
	<input type="submit" />
</form>
</p>