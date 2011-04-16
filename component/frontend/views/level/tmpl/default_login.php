<?php defined('KOOWA') or die(); ?>

<form action="index.php" method="post">
	<input type="hidden" name="option" value="com_user" />
	<input type="hidden" name="task" value="login" />
	<input type="hidden" name="return" value="<?=base64_encode(str_replace('&amp;','&',@route('view=level&layout=default&slug='.KRequest::get('get.slug','slug',''))))?>" />
	<input type="hidden" name="remember" value="1" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />
	<fieldset>
		<legend><?=@text('COM_AKEEBASUBS_LEVEL_LOGIN')?></legend>
		<label for="username" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_LOGIN_USERNAME')?></label>
		<input type="text" class="main" name="username" value="" />
		<br/>
		<label for="passwd" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_LOGIN_PASSWORD')?></label>
		<input type="password" class="main" name="passwd" value="" />
		<br/>
		<input type="submit" value="<?=@text('COM_AKEEBASUBS_LEVEL_LOGIN')?>" />
		<?=@text('COM_AKEEBASUBS_LEVEL_LOGIN_ORCONTINUE')?>
	</fieldset>
</form>