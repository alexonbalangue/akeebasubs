<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo $data->url ?>"  method="post" id="paymentForm">
	<input type="hidden" name="version" id="version" value="<?php echo $data->version ?>" />
	<input type="hidden" name="TPE" id="TPE" value="<?php echo $data->TPE ?>" />
	<input type="hidden" name="date" id="date" value="<?php echo $data->date ?>" />
	<input type="hidden" name="montant" id="montant" value="<?php echo $data->montant ?>" />
	<input type="hidden" name="reference" id="reference" value="<?php echo $data->reference ?>" />
	<input type="hidden" name="MAC" id="MAC" value="<?php echo $data->MAC ?>" />
	<input type="hidden" name="url_retour" id="url_retour" value="<?php echo $data->url_retour ?>" />
	<input type="hidden" name="url_retour_ok" id="url_retour_ok" value="<?php echo $data->url_retour_ok ?>" />
	<input type="hidden" name="url_retour_err" id="url_retour_err" value="<?php echo $data->url_retour_err ?>" />
	<input type="hidden" name="lgue" id="lgue" value="<?php echo $data->lgue ?>" />
	<input type="hidden" name="societe" id="societe" value="<?php echo $data->societe ?>" />
	<input type="hidden" name="texte-libre" id="texte-libre" value="" />
	<input type="hidden" name="mail" id="mail" value="<?php echo $data->mail ?>" />
	<input type="hidden" name="nbrech" id="nbrech" value="" />
	<input type="hidden" name="dateech1" id="dateech1" value="" />
	<input type="hidden" name="montantech1" id="montantech1" value="" />
	<input type="hidden" name="dateech2" id="dateech2" value="" />
	<input type="hidden" name="montantech2" id="montantech2" value="" />
	<input type="hidden" name="dateech3" id="dateech3" value="" />
	<input type="hidden" name="montantech3" id="montantech3" value="" />
	<input type="hidden" name="dateech4" id="dateech4" value="" />
	<input type="hidden" name="montantech4" id="montantech4" value="" />
	<input type="submit" class="btn" />
</form>
</p>