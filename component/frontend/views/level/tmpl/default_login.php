<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

// The form action URL, points to com_users' login task
$login_url = 'index.php?option=com_users&task=user.login';

// A reference back to ourselves
$redirectURL = JURI::getInstance()->toString();

// Should I use two factor authentication in Joomla! 3.2 and later?
$useTwoFactorAuth = false;

if (version_compare(JVERSION, '3.2.0', 'ge'))
{
	require_once JPATH_ADMINISTRATOR . '/components/com_users/helpers/users.php';
	$tfaMethods = UsersHelper::getTwoFactorMethods();
	$useTwoFactorAuth = count($tfaMethods) > 1;

	if ($useTwoFactorAuth)
	{
		JHtml::_('behavior.keepalive');
		JHtml::_('bootstrap.tooltip');
	}
}

?>

<form action="<?php echo rtrim(JURI::base(),'/') ?>/<?php echo $login_url ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="return" value="<?php echo base64_encode($redirectURL)?>" />
	<input type="hidden" name="remember" value="1" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?></legend>

		<div class="control-group">
			<label for="username" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_USERNAME')?>
			</label>
			<div class="controls">
				<input type="text" name="username" value="" />
			</div>
		</div>

		<div class="control-group">
			<label for="password" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_PASSWORD')?>
			</label>
			<div class="controls">
				<input type="password" name="password" value="" />
			</div>
		</div>

		<?php if ($useTwoFactorAuth): ?>
		<div class="control-group">
			<label for="secretkey" class="control-label">
				<?php echo JText::_('JGLOBAL_SECRETKEY')?>
			</label>
			<div class="controls">
				<input type="text" name="secretkey" value="" class="input-small" />
				<span class="btn width-auto hasTooltip" title="<?php echo JText::_('JGLOBAL_SECRETKEY_HELP'); ?>">
					<span class="icon-help"></span>
				</span>
			</div>
		</div>
		<?php endif; ?>

		<div class="form-actions">
			<input type="submit" class="btn btn-primary" value="<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?>" />
			<span>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_ORCONTINUE')?>
			</span>
		</div>



	</fieldset>
</form>