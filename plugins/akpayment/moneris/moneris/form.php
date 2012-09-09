<?php defined('_JEXEC') or die(); ?>

<h3>
	<?php echo JText::_('PLG_AKPAYMENT_MONERIS_READYTOPAY') ?>
</h3>
<p>
	<?php echo JText::_('PLG_AKPAYMENT_MONERIS_INSTRUCTIONS') ?>
</p>

<form action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=moneris') ?>" method="post">
<input type="hidden" id="ak_moneris_id" name="ak_moneris_id" value="<?php echo $subscription->akeebasubs_subscription_id ?>" />
<table id="ak_moneris_ccFields" width="100%" border="0" cellspacing="3">
	<tr>
		<td width="33%">
			<?php echo JText::_('PLG_AKPAYMENT_MONERIS_CCNUMBER') ?>
		</td>
		<td>
			<input type="text" name="ak_moneris_ccnumber" id="ak_moneris_ccnumber" value="" size="20" maxlength="20" />
		</td>
	</tr>
	<tr>
		<td width="33%">
			<?php echo JText::_('PLG_AKPAYMENT_MONERIS_EXPIRY') ?>
		</td>
		<td>
			<?php echo $this->selectMonth() ?> / <?php echo $this->selectYear() ?>
		</td>
	</tr>
	<tr>
		<td width="33%">
			<?php echo JText::_('PLG_AKPAYMENT_MONERIS_CVV') ?>
		</td>
		<td>
			<input type="text" name="ak_moneris_cvv" id="ak_moneris_cvv" value="" size="4" maxlength="4" />
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<button type="submit" class="btn">
				<?php echo JText::_('PLG_AKPAYMENT_MONERIS_PAYNOW') ?>
			</button>
		</td>
	</tr>
</table>

</form>