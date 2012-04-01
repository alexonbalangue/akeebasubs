<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="eshopId" value="<?php echo $data->eshopId ?>" />
	<input type="hidden" name="orderId" value="<?php echo $data->orderId ?>" />
	<input type="hidden" name="serviceName" value="<?php echo $data->serviceName ?>" />
	<input type="hidden" name="recipientAmount" value="<?php echo $data->recipientAmount ?>" />
	<input type="hidden" name="recipientCurrency" value="<?php echo $data->recipientCurrency ?>" />
	<input type="hidden" name="user_email" value="<?php echo $data->user_email ?>" />
	<input type="hidden" name="successUrl" value="<?php echo $data->successUrl ?>" />
	<input type="hidden" name="failUrl" value="<?php echo $data->failUrl ?>" />
    <input type="image" src="https://rbkmoney.ru/img/banner/RBK_pay_113x47.gif" border="0" name="submit" id="rbkmoneysubmit" />
</form>
</p>