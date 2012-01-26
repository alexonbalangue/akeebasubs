<?php defined('_JEXEC') or die(); ?>

<h3><?php echo JText::_('PLG_AKPAYMENT_GOOGLECHECKOUT_MSG_READYTOCHECKOUT') ?></h3>
<p><?php echo JText::_('PLG_AKPAYMENT_GOOGLECHECKOUT_MSG_INFO') ?></p>
<?php echo @$data->cart->CheckoutButtonCode("SMALL"); ?>