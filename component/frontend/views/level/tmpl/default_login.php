<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$login_url = 'index.php?option=com_users&task=user.login';

$rootURL = rtrim(JURI::base(),'/');
$subpathURL = JURI::base(true);
if(!empty($subpathURL) && ($subpathURL != '/')) {
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

$redirectURL = $rootURL. str_replace('&amp;','&',
	JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&slug='.FOFInput::getString('slug','',$this->input)))
?>

<form action="<?php echo rtrim(JURI::base(),'/') ?>/<?php echo $login_url ?>" method="post">
	<input type="hidden" name="return" value="<?php echo base64_encode($redirectURL)?>" />
	<input type="hidden" name="remember" value="1" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?></legend>
		<label for="username" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_USERNAME')?></label>
		<input type="text" class="main" name="username" value="" />
		<br/>
		<label for="password" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_PASSWORD')?></label>
		<input type="password" class="main" name="password" value="" />
		<br/>
		<input type="submit" value="<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?>" />
		<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_ORCONTINUE')?>
	</fieldset>
</form>