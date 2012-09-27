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

<form action="<?php echo rtrim(JURI::base(),'/') ?>/<?php echo $login_url ?>" method="post" class="form form-horizontal">
	<input type="hidden" name="return" value="<?php echo base64_encode($redirectURL)?>" />
	<input type="hidden" name="remember" value="1" />
	<?php echo JHtml::_('form.token'); ?>
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

		<div class="form-actions">
			<input type="submit" class="btn btn-primary" value="<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN')?>" />
			<span>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LOGIN_ORCONTINUE')?>
			</span>
		</div>
		
		
		
	</fieldset>
</form>