<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="p24_session_id" value="<?php echo $data->p24_session_id ?>" />
	<input type="hidden" name="p24_id_sprzedawcy" value="<?php echo $data->p24_id_sprzedawcy ?>" />
	<input type="hidden" name="p24_kwota" value="<?php echo $data->p24_kwota ?>" />
	<input type="hidden" name="p24_opis" value="<?php echo $data->p24_opis ?>" />
	<input type="hidden" name="p24_klient" value="<?php echo $data->p24_klient ?>" />
	<input type="hidden" name="p24_adres" value="<?php echo $data->p24_adres ?>" />
	<input type="hidden" name="p24_kod" value="<?php echo $data->p24_kod ?>" />
	<input type="hidden" name="p24_miasto" value="<?php echo $data->p24_miasto ?>" />
	<input type="hidden" name="p24_kraj" value="<?php echo $data->p24_kraj ?>" />
	<input type="hidden" name="p24_email" value="<?php echo $data->p24_email ?>" />
	<input type="hidden" name="p24_return_url_ok" value="<?php echo $data->p24_return_url_ok ?>" />
	<input type="hidden" name="p24_return_url_error" value="<?php echo $data->p24_return_url_error ?>" />
	<input type="submit" class="btn" />
</form>
</p>