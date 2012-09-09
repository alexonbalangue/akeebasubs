<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="id_carteira" value="<?php echo htmlentities($data->merchant) ?>" />
	<input type="hidden" name="id_transacao" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	<input type="hidden" name="nome" value="<?php echo htmlentities($level->title) ?>" />
	<input type="hidden" name="descricao" value="<?php echo htmlentities($level->title . ' - [ ' . $user->username . ' ]') ?>" />
	<input type="hidden" name="url_retorno" value="<?php echo $data->success ?>" />
	<input type="hidden" name="valor" value="<?php echo htmlentities( (int)($subscription->gross_amount * 100) ) ?>" />
	<input type="hidden" name="pagador_nome" value="<?php echo htmlentities( $user->name ) ?>" />
	<input type="hidden" name="pagador_email" value="<?php echo htmlentities( $user->email ) ?>" />

	<input type="submit" class="btn" />
</form>
</p>
