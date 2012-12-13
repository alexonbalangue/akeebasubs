<?php defined('_JEXEC') or die(); ?>

<h3><?php echo JText::_('PLG_AKPAYMENT_SCNETINTEGRATED_FORM_HEADER') ?></h3>
<div class="row-fluid">
<div class="span12">
<br />
<form action="<?php echo $data->callback ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="arg0" value="<?php echo $data->arg0 ?>" />
	<input type="hidden" name="arg1" value="<?php echo $data->arg1 ?>" />
	<div class="control-group">
		<label for="arg6" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_SCNETINTEGRATED_FORM_CCHOLDER') ?>
		</label>
		<div class="controls">
			<input type="text" name="arg6" id="arg6" class="input-large" value="<?php echo $data->arg13 ?>" />
		</div>
	</div>
	<div class="control-group">
		<label for="arg2" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_SCNETINTEGRATED_FORM_CC') ?>
		</label>
		<div class="controls">
			<input type="text" name="arg2" id="arg2" class="input-large" />
		</div>
	</div>
	<div class="control-group">
		<label for="arg3" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_SCNETINTEGRATED_FORM_EXPDATE') ?>
		</label>
		<div class="controls">
			<?php echo $this->selectExpirationDate() ?>
		</div>
	</div>
	<div class="control-group">
		<label for="arg4" class="control-label" style="width:190px; margin-right:20px;">
			<?php echo JText::_('PLG_AKPAYMENT_SCNETINTEGRATED_FORM_CVV') ?>
		</label>
		<div class="controls">
			<input type="text" name="arg4" id="arg4" class="input-mini" />
		</div>
	</div>
	<input type="hidden" name="arg5" value="<?php echo $data->arg5 ?>" />
	<input type="hidden" name="arg7" value="<?php echo $data->arg7 ?>" />
	<input type="hidden" name="arg8" value="<?php echo $data->arg8 ?>" />
	<?php if(! empty($data->arg9)) { ?>
	<input type="hidden" name="arg9" value="<?php echo $data->arg9 ?>" />
	<?php } ?>
	<input type="hidden" name="arg10" value="<?php echo $data->arg10 ?>" />
	<input type="hidden" name="arg12" value="<?php echo $data->arg12 ?>" />
	<input type="hidden" name="arg13" value="<?php echo $data->arg13 ?>" />
	<input type="hidden" name="arg14" value="<?php echo $data->arg14 ?>" />
	<input type="hidden" name="arg16" value="<?php echo $data->arg16 ?>" />
	<input type="hidden" name="arg22" value="<?php echo $data->arg22 ?>" />
	<input type="hidden" name="arg23" value="<?php echo $data->arg23 ?>" />
	<div class="control-group">
		<label for="pay" class="control-label" style="width:190px; margin-right:20px;">
		</label>
		<div class="controls">
			<input type="submit" class="btn" value="Pay Now" />
		</div>
	</div>
</form>
</div>
</div>