<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$login_url = version_compare(JVERSION, '1.6.0', 'ge') ? 'index.php?option=com_users&task=user.login' : 'option=com_user&task=login';
?>

<form action="<?php echo rtrim(JURI::base(),'/') ?>/<?php echo JRoute::_($login_url) ?>" method="post">
	<input type="hidden" name="return" value="<?php echo base64_encode(str_replace('&amp;','&',rtrim(JURI::base(),'/').JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&slug='.FOFInput::getString('slug','',$this->input))))?>" />
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