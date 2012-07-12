<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('PLG_AKPAYMENT_IFTHEN_PAYMENT_DESC');
$t2 = JText::_('PLG_AKPAYMENT_IFTHEN_ENTIDADE_LABEL');
$t3 = JText::_('PLG_AKPAYMENT_IFTHEN_REFERENCIA_LABEL');
$t4 = JText::_('PLG_AKPAYMENT_IFTHEN_VALOR_LABEL');
$t5 = JText::_('PLG_AKPAYMENT_IFTHEN_COMPLETE_SUBSCRIPTION');
?>

<h3><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_PAYMENT_DESC') ?></h3>
<div>
<label><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_ENTIDADE_LABEL') ?>:</label>
<span><b><?php echo $data->entidade ?></b></span>
<br/>
<label><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_REFERENCIA_LABEL') ?>:</label>
<span><b><?php echo $data->referencia ?></b></span>
<br/>
<label><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_VALOR_LABEL') ?>:</label>
<span><b><?php echo str_replace('.', ',', $data->valor) . ' ' . $data->currency ?></b></span>
</div>
<br/>
<form action="<?php echo htmlentities($data->url) ?>" method="POST">
	<input type="hidden" name="subscription" value="<?php echo $data->subscription_id ?>" />
	<input type="hidden" name="reference" value="<?php echo $data->referencia ?>" />
	<input type="submit" value="<?php echo JText::_('PLG_AKPAYMENT_IFTHEN_COMPLETE_SUBSCRIPTION') ?>" />
</form>