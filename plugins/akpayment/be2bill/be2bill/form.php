<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
    <input type="hidden" name="IDENTIFIER" value="<?php echo $data->identifier?>" />
    <input type="hidden" name="HASH" value="<?php echo $data->hash?>" />
    <input type="hidden" name="OPERATIONTYPE" value="payment" />
    <input type="hidden" name="CLIENTIDENT" value="<?php echo $data->clientident ?>" />
    <input type="hidden" name="ORDERID" value="<?php echo $data->orderid ?>" />
    <input type="hidden" name="AMOUNT" value="<?php echo $data->amount ?>" />
    <input type="hidden" name="VERSION" value="2.0" />

    <input type="submit" class="btn" value="Complete subscription" />
</form>
</p>