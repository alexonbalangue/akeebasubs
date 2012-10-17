<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addJS('media://com_akeebasubs/js/signup.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/autosubmit.js?'.AKEEBASUBS_VERSIONHASH);

require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/format.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';

if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}

if(isset($this->item)) {
	$akeebasubs_subscription_level = $this->item->akeebasubs_level_id;
} else {
	$akeebasubs_subscription_level = null;
}

$field_data = array(
	'name'				=> !empty($this->userparams->name) ? $this->userparams->name : $this->cache['name'],
	'email'				=> !empty($this->userparams->email) ? $this->userparams->email : $this->cache['email'],
	'email2'			=> !empty($this->userparams->email2) ? $this->userparams->email2 : $this->cache['email2'],
	'address1'			=> !empty($this->userparams->address1) ? $this->userparams->address1 : $this->cache['address1'],
	'address2'			=> !empty($this->userparams->address2) ? $this->userparams->address2 : $this->cache['address2'],
	'city'				=> !empty($this->userparams->city) ? $this->userparams->city : $this->cache['city'],
	'state'				=> !empty($this->userparams->state) ? $this->userparams->state : $this->cache['state'],
	'zip'				=> !empty($this->userparams->zip) ? $this->userparams->zip : $this->cache['zip'],
	'country'			=> !empty($this->userparams->country) && ($this->userparams->country != 'XX') ? $this->userparams->country : $this->cache['country'],
	'businessname'		=> !empty($this->userparams->businessname) ? $this->userparams->businessname : $this->cache['businessname'],
	'occupation'		=> !empty($this->userparams->occupation) ? $this->userparams->occupation : $this->cache['occupation'],
	'vatnumber'			=> !empty($this->userparams->vatnumber) ? $this->userparams->vatnumber : $this->cache['vatnumber'],
);

$group_classes = array(
	'username'			=> '',
	'password'			=> '',
	'password2'			=> '',
	'name'				=> $this->validation->validation->name ? '' : 'error',
	'email'				=> $this->validation->validation->email ? '' : 'error',
	'email2'			=> $this->validation->validation->email2 ? '' : 'error',
	'address1'			=> $this->validation->validation->address1 ? '' : 'error',
	'city'				=> $this->validation->validation->city ? '' : 'error',
	'state'				=> $this->validation->validation->state ? '' : 'error',
	'zip'				=> $this->validation->validation->zip ? '' : 'error',
	'country'			=> $this->validation->validation->country ? '' : 'error',
	'businessname'		=> $this->validation->validation->businessname ? '' : 'error',
	'occupation'		=> !empty($field_data['occupation']) ? '' : 'error',
	'vatnumber'			=> $this->validation->validation->vatnumber ? '' : 'warning',
);
if(JFactory::getUser()->guest) {
	$group_classes['username'] = ($this->cache['username']) ? (($this->validation->validation->username ) ? 'success' : 'error') : '';
	$group_classes['password'] = !$this->cache['password'] ? 'error' : '';
	$group_classes['password2'] = (!$this->cache['password2'] || ($this->cache['password2'] != $this->cache['password'])) ? 'error' : '';
}

?>

<div class="form form-horizontal">
	
<fieldset>
<?php if(JFactory::getUser()->guest):?>
	<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></legend>

	<div class="control-group <?php echo $group_classes['username'] ?>">
		<label for="username" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?>
		</label>
		<div class="controls">
			<input type="text" name="username" id="username" value="<?php echo $this->escape($this->cache['username'])?>" />
			<span id="username_valid" class="help-inline" <?php if($group_classes['username'] != 'success'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_VALID')?>
			</span>
			<span id="username_invalid" class="help-inline" <?php if($group_classes['username'] != 'error'):?>style="display:none"<?php endif ?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_INVALID')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['password'] ?>">
		<label for="password" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?>
		</label>
		<div class="controls">
			<input type="password" name="password" id="password" value="<?php echo $this->escape($this->cache['password'])?>" />
			<span id="password_invalid" class="help-inline" style="<?php if($group_classes['password'] != 'error'): ?>display:none<?php endif; ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')?>
			</span>
		</div>
	</div>

	<div class="control-group <?php echo $group_classes['password2'] ?>">
		<label for="password2" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?>
		</label>
		<div class="controls">
			<input type="password" name="password2" id="password2" value="<?php echo $this->escape($this->cache['password2'])?>" />
			<span id="password2_invalid" class="help-inline" style="<?php if($group_classes['password2'] != 'error'): ?>display:none<?php endif; ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')?>
			</span>
		</div>
	</div>
<?php else: ?>
	<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_EXISTINGACCOUNT')?></legend>

	<div class="control-group">
		<label for="username" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?>
		</label>
		<div class="controls">
			<input type="text" name="username" id="username" disabled="disabled" value="<?php echo $this->escape($this->userparams->username)?>" />
		</div>
	</div>
<br/>
<?php endif; ?>
</fieldset>

<fieldset>

	<div class="control-group <?php echo $group_classes['name'] ?>">
		<label for="name" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NAME')?>
		</label>
		<div class="controls">
			<input type="text" name="name" id="name" value="<?php echo $this->escape($field_data['name']);?>" />
			<span id="name_empty" class="help-inline" <?php if($group_classes['name'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NAME_INVALID')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['email'] ?>">
		<label for="email" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')?>
		</label>
		<div class="controls">
			<input type="text" name="email" id="email" value="<?php echo $this->escape($field_data['email']);?>" />
			<span id="email_invalid" class="help-inline" <?php if($group_classes['email'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_EMAIL')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['email2'] ?>">
		<label for="email2" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL2')?>
		</label>
		<div class="controls">
			<input type="text" name="email2" id="email2" value="<?php echo $this->escape($field_data['email2']);?>" />
			<span id="email2_invalid" class="help-inline" <?php if($group_classes['email2'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_EMAIL2')?>
			</span>
		</div>
	</div>

<?php
jimport('joomla.plugin.helper');
JPluginHelper::importPlugin('akeebasubs');
$app = JFactory::getApplication();
$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($this->userparams, array_merge($this->cache,array('subscriptionlevel' => $akeebasubs_subscription_level))));
if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):
if(array_key_exists('isValid', $field)) {
	$customField_class = $field['isValid'] ? (array_key_exists('validLabel', $field) ? 'success' : '') : 'error';
} else {
	$customField_class = '';
}
?>
	<div class="control-group <?php echo $customField_class ?>">
		<label for="<?php echo $field['id']?>" class="control-label">
			<?php echo $field['label']?>
		</label>
		<div class="controls">
			<?php echo $field['elementHTML']?>
			<?php if(array_key_exists('validLabel', $field)):?>
			<span id="<?php echo $field['id']?>_valid" class="help-inline"
				  style="<?php if(!$field['isValid']):?>display:none<?php endif?>">
					  <?php echo $field['validLabel']?>
			</span>
			<?php endif;?>
			<?php if(array_key_exists('invalidLabel', $field)):?>
			<span id="<?php echo $field['id']?>_invalid" class="help-inline"
				  style="<?php if($field['isValid']):?>display:none<?php endif?>">
					  <?php echo $field['invalidLabel']?>
			</span>
			<?php endif;?>
		</div>
	</div>

<?php endforeach; endforeach;?>

<?php if(AkeebasubsHelperCparams::getParam('personalinfo',1)):?>

	<div class="control-group <?php echo $group_classes['address1'] ?>">
		<label for="address1" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')?>
		</label>
		<div class="controls">
			<input type="text" name="address1" id="address1"
				   value="<?php echo $this->escape($field_data['address1']);?>" />
			<span id="address1_empty" class="help-inline" <?php if($group_classes['address1'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
	<div class="control-group">
		<label for="address2" class="control-label">
			<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')?>
		</label>
		<div class="controls">
			<input type="text" name="address2" id="address2"
				   value="<?php echo $this->escape($field_data['address2']);?>" />
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['city'] ?>">
		<label for="city" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_CITY')?>
		</label>
		<div class="controls">
			<input type="text" name="city" id="city"
				   value="<?php echo $this->escape($field_data['city']);?>" />
			<span id="city_empty" class="help-inline" <?php if($group_classes['city'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['city'] ?>" id="stateField">
		<label for="state" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_STATE')?>
		</label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::states($field_data['state'], 'state', array('id'=>'state')) ?>
			<span id="state_empty" class="help-inline" <?php if($group_classes['city'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['zip'] ?>">
		<label for="zip" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')?>
		</label>
		<div class="controls">
			<input type="text" name="zip" id="zip"
				   value="<?php echo $this->escape($field_data['zip']);?>" />
			<span id="zip_empty" class="help-inline" <?php if($group_classes['zip'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['country'] ?>">
		<label for="country" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?>
		</label>
		<div class="controls">
			<?php echo AkeebasubsHelperSelect::countries($field_data['country'], 'country', array('id'=>'country')) ?>
			<span id="country_empty" class="help-inline" <?php if($group_classes['country'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
</fieldset>
<fieldset>
	
<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS')?></legend>

<div class="control-group">
	<label for="isbusiness" class="control-label">
		* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')?>
	</label>
	<div class="controls">
		<?php echo JHTML::_('select.booleanlist', 'isbusiness', array('id'=>'isbusiness'), !empty($this->userparams->isbusiness) ? $this->userparams->isbusiness : (@array_key_exists('isbusiness',$this->cache) ? $this->cache['isbusiness'] : 0)); ?>
	</div>
</div>


<div id="businessfields">
	
	<div class="control-group <?php echo $group_classes['businessname'] ?>">
		<label for="businessname" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')?>
		</label>
		<div class="controls">
			<input type="text" name="businessname" id="businessname"
				   value="<?php echo $this->escape($field_data['businessname']);?>" />
			<span id="businessname_empty" class="help-inline" <?php if($group_classes['businessname'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['occupation'] ?>">
		<label for="occupation" class="control-label">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_OCCUPATION')?>
		</label>
		<div class="controls">
			<input type="text" name="occupation" id="occupation"
				   value="<?php echo $this->escape($field_data['occupation']);?>" />
			<span id="occupation_empty" class="help-inline" <?php if($group_classes['occupation'] != 'error'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
	
	<div class="control-group <?php echo $group_classes['vatnumber'] ?>" id="vatfields">
		<label for="vatnumber" class="control-label" id="vatlabel">
			* <?php echo AkeebasubsHelperCparams::getParam('noneuvat', 0) ? JText::_('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER_ALTLABEL') : JText::_('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER')?>
		</label>
		<div class="controls">
			<div class="input-prepend">
				<span class="add-on" id="vatcountry">EU</span>
				<input type="text" name="vatnumber" id="vatnumber" class="input-small" size="16"
					value="<?php echo $this->escape($field_data['vatnumber']);?>" />
			</div>
			<span id="vat-status-invalid" class="help-inline" <?php if($group_classes['vatnumber'] != 'warning'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_VAT_INVALID')?>
			</span>
			<span id="vat-status-valid" class="help-inline" <?php if($group_classes['vatnumber'] != 'success'):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_VAT_VALID')?>
			</span>
		</div>
	</div>
	
</div>

</fieldset>
<?php endif;?>

</fieldset>
</div>

<?php
$aks_validate_url = JURI::base().'index.php';
$aks_personal_info = AkeebasubsHelperCparams::getParam('personalinfo',1)?'true':'false';
$aks_noneuvat = AkeebasubsHelperCparams::getParam('noneuvat',0)?'true':'false';
$script = <<<ENDSCRIPT
var akeebasubs_validate_url = "$aks_validate_url";
var akeebasubs_valid_form = false;
var akeebasubs_personalinfo = $aks_personal_info;
var akeebasubs_noneuvat = $aks_noneuvat;
ENDSCRIPT;
JFactory::getDocument()->addScriptDeclaration($script);