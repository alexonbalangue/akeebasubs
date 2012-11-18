<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
	<input type="hidden" name="MrchLogin" value="<?php echo $data->MrchLogin ?>" />
	<input type="hidden" name="InvId" value="<?php echo $data->InvId ?>" />
	<input type="hidden" name="OutSum" value="<?php echo $data->OutSum ?>" />
	<input type="hidden" name="IncCurrLabel" value="<?php echo $data->IncCurrLabel ?>" />
	<input type="hidden" name="Desc" value="<?php echo $data->Desc ?>" />
	<input type="hidden" name="Culture" value="<?php echo $data->Culture ?>" />
	<input type="hidden" name="SignatureValue" value="<?php echo $data->SignatureValue ?>" />
	<input type="submit" class="btn" />
</form>
</p>