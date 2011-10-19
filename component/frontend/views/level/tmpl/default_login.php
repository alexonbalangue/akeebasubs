<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();
?>

<form action="<?php echo JURI::base() ?>index.php" method="post">
	<input type="hidden" name="option" value="<?php echo version_compare(JVERSION,'1.6.0','ge') ? 'com_users' : 'com_user'?>" />
	<input type="hidden" name="task" value="<?php echo version_compare(JVERSION,'1.6.0','ge') ? 'user.login' : 'login'?>" />
	<input type="hidden" name="return" value="<?php echo base64_encode(str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&slug='.FOFInput::getString('slug','',$this->input))))?>" />
	<input type="hidden" name="remember" value="1" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?></legend>
		<label for="username" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_USERNAME')?></label>
		<input type="text" class="main" name="username" value="" />
		<br/>
		<label for="<?php echo version_compare(JVERSION,'1.6.0','ge') ? 'password' : 'passwd'?>" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_PASSWORD')?></label>
		<input type="password" class="main" name="<?php echo version_compare(JVERSION,'1.6.0','ge') ? 'password' : 'passwd'?>" value="" />
		<br/>
		<input type="submit" value="<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?>" />
		<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_ORCONTINUE')?>
	</fieldset>
</form>