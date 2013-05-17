<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="user" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_user_id" value="<?php echo $this->item->akeebasubs_user_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<?php $userEditorLink = 'index.php?option=com_users&task=user.edit&id='; ?>
	<?php if($this->item->user_id): ?>
<div class="row-fluid">
<div class="span12">
	<a class="btn btn-inverse" href="<?php echo $userEditorLink.$this->item->user_id?>">
		<i class="icon-pencil icon-white"></i>
		<?php echo JText::_('COM_AKEEBASUBS_USER_EDITTHISINJUSERMANAGER')?>
	</a>
</div>
</div>
	<?php endif; ?>


<div class="row-fluid">

<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_USER_BASIC_TITLE')?></h3>

	<?php JLoader::import('joomla.user.user'); ?>

	<div class="control-group">
		<label for="userid_visible" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER')?></label>
		<div class="controls">
			<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
			<input type="text" class="input-medium" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
			<button onclick="return false;" class="btn btn-mini modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
			<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
		</div>
	</div>

	<div class="control-group">
		<label for="address1" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ADDRESS1')?></label>
		<div class="controls">
			<input type="text" name="address1" id="address1" value="<?php echo $this->escape($this->item->address1)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="address2" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ADDRESS2')?></label>
		<div class="controls">
			<input type="text" name="address2" id="address2" value="<?php echo $this->escape($this->item->address2)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="city" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_CITY')?></label>
		<div class="controls">
			<input type="text" name="city" id="city" value="<?php echo $this->escape($this->item->city)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="zip" class="control-label">
			<?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ZIP')?>
		</label>
		<div class="controls">
			<input type="text" class="input-small" name="zip" id="zip" value="<?php echo $this->escape($this->item->zip)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="state" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_STATE')?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::states($this->item->state, 'state'); ?>
		</div>
	</div>

	<div class="control-group">
		<label for="state" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_COUNTRY')?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::countries($this->item->country, 'country'); ?>
		</div>
	</div>
</div>

<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_USER_BASIC_BUSINESS')?></h3>

	<div class="control-group">
		<label for="isbusiness" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_ISBUSINESS')?></label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'isbusiness', null, $this->item->isbusiness); ?>
		</div>
	</div>

	<div class="control-group">
		<label for="businessname" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_BUSINESSNAME')?></label>
		<div class="controls">
			<input type="text" name="businessname" id="businessname" class="longer" value="<?php echo $this->escape($this->item->businessname)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="occupation" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_OCCUPATION')?></label>
		<div class="controls">
			<input type="text" name="occupation" id="occupation" class="longer" value="<?php echo $this->escape($this->item->occupation)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="vatnumber" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_VATNUMBER')?></label>
		<div class="controls">
			<input type="text" name="vatnumber" id="vatnumber" class="longer" value="<?php echo $this->escape($this->item->vatnumber)?>" />
		</div>
	</div>

	<div class="control-group">
		<label for="viesregistered" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_USERS_FIELD_VIESREGISTERED')?></label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::viesregistered('viesregistered', $this->item->viesregistered); ?>
		</div>
	</div>
</div>

</div>

<div class="row-fluid">

<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_USER_NOTES_TITLE')?></h3>

	<textarea rows="10" cols="40" id="notes" name="notes" style="width: 95%;"><?php echo $this->item->notes ?></textarea>
</div>

<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_USER_CUSTOMPARAMS_TITLE')?></h3>

	<?php
	JLoader::import('joomla.plugin.helper');
	JPluginHelper::importPlugin('akeebasubs');
	$app = JFactory::getApplication();
	$params = @json_decode($this->item->params);
	if(empty($params)) $params = new stdClass();
	$userparams = (object)array('params' => $params);
	$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($userparams, array('subscriptionlevel'=>-1, 'custom'=>array())));
	if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
	if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):?>
	<div class="control-group">
		<label for="<?php echo $field['id']?>" class="control-label"><?php echo $field['label']?></label>
		<div class="controls">
			<?php echo $field['elementHTML']?>
		</div>
	</div>

	<?php endforeach; endforeach;?>
</div>

</div>

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
			try {
				new Event(e).stop();
			} catch(anotherMTUpgradeIssue) {
				try {
					e.stop();
				} catch(WhateverIsWrongWithYouIDontCare) {
					try {
						DOMEvent(e).stop();
					} catch(NoBleepinWay) {
						alert('If you see this message, your copy of Joomla! is FUBAR');
					}
				}
			}
			SqueezeBox.fromElement($('userselect'));
			SqueezeBox.fromElement($('userselect'), {
				parse: 'rel'
			});
		});
	});
});
</script>