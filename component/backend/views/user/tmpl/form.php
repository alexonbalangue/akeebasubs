<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');
JHTML::_('behavior.mootools');
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="user" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_user_id" value="<?php echo $this->item->akeebasubs_user_id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_USER_BASIC_TITLE')?></legend>

<?php $userEditorLink = 'index.php?option=com_users&task=user.edit&id='; ?>
	<?php if($this->item->user_id): ?>
	<a href="<?php echo $userEditorLink.$this->item->user_id?>">
		<span class="akstriangle"></span><span class="akstriangle"></span><span class="akstriangle"></span>
		<?php echo JText::_('COM_AKEEBASUBS_USER_EDITTHISINJUSERMANAGER')?>
	</a>
	<?php endif; ?>
	<div class="akeebasubs-clear"></div>
	
	<?php jimport('joomla.user.user'); ?>
	<label for="userid_visible" class="main"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
	<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
	<input type="text" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
	<button onclick="return false;" class="modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<div class="akeebasubs-clear"></div>
	
	<label for="address1" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ADDRESS1')?></label>
	<input type="text" name="address1" id="address1" class="longer" value="<?php echo $this->escape($this->item->address1)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="address2" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ADDRESS2')?></label>
	<input type="text" name="address2" id="address2" class="longer" value="<?php echo $this->escape($this->item->address2)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="city" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_CITY')?></label>
	<input type="text" name="city" id="city" class="longer" value="<?php echo $this->escape($this->item->city)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="zip" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ZIP')?></label>
	<input type="text" name="zip" id="zip" value="<?php echo $this->escape($this->item->zip)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="state" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_STATE')?></label>
	<?php echo AkeebasubsHelperSelect::states($this->item->state, 'state'); ?>
	<div class="akeebasubs-clear"></div>
	
	<label for="state" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_COUNTRY')?></label>
	<?php echo AkeebasubsHelperSelect::countries($this->item->country, 'country'); ?>
	<div class="akeebasubs-clear"></div>
	
</fieldset>

<fieldset id="coupons-finetuning" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_USER_BASIC_BUSINESS')?></legend>

	<label for="isbusiness" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ISBUSINESS')?></label>
	<?php echo JHTML::_('select.booleanlist', 'isbusiness', null, $this->item->isbusiness); ?>
	<div class="akeebasubs-clear"></div>
	
	<label for="businessname" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_BUSINESSNAME')?></label>
	<input type="text" name="businessname" id="businessname" class="longer" value="<?php echo $this->escape($this->item->businessname)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="occupation" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_OCCUPATION')?></label>
	<input type="text" name="occupation" id="occupation" class="longer" value="<?php echo $this->escape($this->item->occupation)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="vatnumber" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_VATNUMBER')?></label>
	<input type="text" name="vatnumber" id="vatnumber" class="longer" value="<?php echo $this->escape($this->item->vatnumber)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="viesregistered" class="main"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_VIESREGISTERED')?></label>
	<?php echo JHTML::_('select.booleanlist', 'viesregistered', null, $this->item->viesregistered); ?>
	<div class="akeebasubs-clear"></div>
	
</fieldset>

<div class="akeebasubs-clear"></div>

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_USER_NOTES_TITLE')?></legend>

	<textarea rows="10" cols="40" id="notes" name="notes"><?php echo $this->item->notes ?></textarea>

</fieldset>

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_USER_CUSTOMPARAMS_TITLE')?></legend>

	<?php
	jimport('joomla.plugin.helper');
	JPluginHelper::importPlugin('akeebasubs');
	$app = JFactory::getApplication();
	$params = @json_decode($this->item->params);
	if(empty($params)) $params = new stdClass();
	$userparams = (object)array('params' => $params);
	$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($userparams, array('custom'=>array())));
	if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
	if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):?>
	
	<label for="<?php echo $field['id']?>" class="main"><?php echo $field['label']?></label>
	<?php echo $field['elementHTML']?>
	<div class="akeebasubs-clear"></div>
	
	<?php endforeach; endforeach;?>

</fieldset>

<div class="akeebasubs-clear"></div>

</form>

<script type="text/javascript">
function jSelectUser_userid(id, username)
{
	document.getElementById('userid').value = id;
	document.getElementById('userid_visible').value = username;
	try {
		document.getElementById('sbox-window').close();	
	} catch(err) {
		SqueezeBox.close();
	}
}
window.addEvent("domready", function() {
	$$("button.modal").each(function(el) {
		el.addEvent("click", function(e) {
			new Event(e).stop();
			SqueezeBox.fromElement($('userselect'));
		});
	});
});
</script>