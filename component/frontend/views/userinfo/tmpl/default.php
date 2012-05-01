<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('modules');

$postURL = JURI::base() . 'index.php?option=com_akeebasubs&view=userinfo&task=save';
?>

<div id="akeebasubs" class="userinfo">
	
<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistheader')?>
<noscript>
<hr/>
<h1><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')?></h1>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')?></p>
<hr/>
</noscript>

<?php if(JFactory::getUser()->guest && AkeebasubsHelperCparams::getParam('allowlogin',1)):?>
	<?php echo $this->loadTemplate('login') ?>
<?php endif?>

<form action="<?php echo $postURL ?>" method="post" id="userinfoForm" >
	<input type="hidden" name="_token" value="<?php echo JUtility::getToken()?>" />
	
	<?php echo $this->loadAnyTemplate('level/default_fields') ?>
	
	<br />
	<?php if(JFactory::getUser()->guest):?>
	<button id="update_userinfo" type="submit"><?php echo JText::_('COM_AKEEBASUBS_USERINFO_BUTTON_CREATE_USER')?></button>
	<?php else: ?>
	<button id="update_userinfo" type="submit"><?php echo JText::_('COM_AKEEBASUBS_USERINFO_BUTTON_UPDATE_USER')?></button>
	<?php endif; ?>
</form>
</div>

<?php
$aks_personal_info = AkeebasubsHelperCparams::getParam('personalinfo',1)?'true':'false';
$aks_msg_error_overall = JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL',true);
$script = <<<ENDSCRIPT

window.addEvent('domready', function() {
	(function(\$) {
		\$(document).ready(function(){
			// Commented out until we can resolve some strange validation errors for some users
			// \$('#signupForm').submit(onSignupFormSubmit);
			validatePassword();
			validateName();
			validateEmail();
			if($aks_personal_info) {
				validateAddress();
				validateBusiness();
			}
		});
	})(akeeba.jQuery);
});

function onSignupFormSubmit()
{
	if(akeebasubs_valid_form == false) {
		alert('$aks_msg_error_overall');
	}
	
	return akeebasubs_valid_form;
}
ENDSCRIPT;
JFactory::getDocument()->addScriptDeclaration($script);