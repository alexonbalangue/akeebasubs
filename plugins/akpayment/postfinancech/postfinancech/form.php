<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $formPostURL ?>"  method="post" id="paymentForm">
	<?php foreach($data as $k => $v):
	if(empty($v)) continue;?><input type="hidden" name="<?php echo $k ?>" value="<?php echo $v ?>" />
	<?php endforeach; ?>

	<input type="submit" class="btn" />	
</form>
</p>