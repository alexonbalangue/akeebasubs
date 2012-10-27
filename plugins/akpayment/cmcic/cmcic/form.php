<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" >
	<input type="hidden" name="version" value="<?php echo $data->version ?>" />
	<input type="hidden" name="TPE" value="<?php echo $data->TPE ?>" />
	<input type="hidden" name="date" value="<?php echo $data->date ?>" />
	<input type="hidden" name="montant" value="<?php echo $data->montant ?>" />
	<input type="hidden" name="reference" value="<?php echo $data->reference ?>" />
	<input type="hidden" name="lgue" value="<?php echo $data->lgue ?>" />
	<input type="hidden" name="societe" value="<?php echo $data->societe ?>" />
	<input type="hidden" name="url_retour" value="<?php echo $data->url_retour ?>" />
	<input type="hidden" name="url_retour_ok" value="<?php echo $data->url_retour_ok ?>" />
	<input type="hidden" name="url_retour_err" value="<?php echo $data->url_retour_err ?>" />
	<input type="hidden" name="MAC" value="<?php echo $data->MAC ?>" />
	<input type="submit" class="btn" />
</form>
</p>