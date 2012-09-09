<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
?>

<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER') ?></h3>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY') ?></p>
<p align="center">
<form action="<?php echo htmlentities($data->url) ?>"  method="post" id="paymentForm">
	<input type="hidden" name="UPAY_SITE_ID" value="<?php echo htmlentities($data->merchant) ?>" />
	<input type="hidden" name="EXT_TRANS_ID" value="<?php echo htmlentities($subscription->akeebasubs_subscription_id) ?>" />
	<input type="hidden" name="EXT_TRANS_ID_LABEL" value="<?php echo htmlentities($level->title . ' - [ ' . $user->username . ' ]') ?>" />
	<input type="hidden" name="SUCCESS_LINK" value="<?php echo $data->success ?>" />
	<input type="hidden" name="AMT" value="<?php echo htmlentities($subscription->gross_amount) ?>" />

	<input type="submit" class="btn" />
</form>
</p>