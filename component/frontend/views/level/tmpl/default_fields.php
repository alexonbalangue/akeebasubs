<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

/*
 * WARNING! WARNING! WARNING! WARNING! WARNING! WARNING! WARNING! WARNING! WARNING!
 *
 * ##          ##    ####    #######   ####    ##  ##  ####    ##    ######
 * ##          ##   ##  ##   ##    ##  ## ##   ##  ##  ## ##   ##   ##    ##
 * ##          ##  ##    ##  ##   ##   ##  ##  ##  ##  ##  ##  ##  ##
 * ##   ###   ##   ########  ######    ##   ## ##  ##  ##   ## ##  ##  ####
 *  ##  ###  ##    ##    ##  ##   ##   ##    ####  ##  ##    ####  ##     ##
 *   #### ####     ##    ##  ##    ##  ##     ###  ##  ##     ###   ##    ##
 *    ##   ##      ##    ##  ##    ##  ##      ##  ##  ##      ##    ######
 *
 * WARNING! WARNING! WARNING! WARNING! WARNING! WARNING! WARNING! WARNING! WARNING!
 *
 *
 * !!! FAILURE TO ABIDE BY THIS WARNING'S INSTRUCTIONS WILL BREAK THE COMPONENT !!!
 *
 *
 * DO NOT REMOVE ANY OF THE FIELDS IN THIS FILE. ALL OF THEM MUST BE PRESENT IN
 * THE HTML OF THE PAGE FOR AKEEBA SUBSCRIPTIONS TO WORK. REMOVAL OF ANY OF THE
 * FIELDS PRESENT IN THIS FILE WILL RESULT IN INABILITY TO SUBSCRIBE, STRANGE
 * ERROR MESSAGES AND GENERAL MALFUNCTION OF THE COMPONENT.
 *
 * YOU ARE SUPPOSED TO HIDE THE FIELDS YOU DO NOT WANT DISPLAYED ON THE PAGE
 * USING CSS. THIS IS WHY EACH OF THESE FIELDS HAS A UNIQUE ID ATTRIBUTE. IF
 * YOU ARE IN DOUBT DO NOT TOUCH OR OVERRIDE THIS FILE.
 *
 * IF YOU EVER REMOVE FIELDS FROM THIS FILE YOU WILL HAVE TO MODIFY THE ENTIRE
 * COMPONENT AND ALL PAYMENT PLUGINS.
 *
 * IF YOU DECIDE TO IGNORE THIS STRICT WARNING, DO NOT FILE "BUG" REPORTS OR
 * SEEK ASSISTANCE.
 */

defined('_JEXEC') or die();

AkeebaStrapper::addJSfile('media://com_akeebasubs/js/signup.js?'.AKEEBASUBS_VERSIONHASH);
AkeebaStrapper::addJSfile('media://com_akeebasubs/js/autosubmit.js?'.AKEEBASUBS_VERSIONHASH);

require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/format.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';

JHTML::_('behavior.framework', true);

if(isset($this->item)) {
	$akeebasubs_subscription_level = $this->item->akeebasubs_level_id;
} else {
	$akeebasubs_subscription_level = null;
}

$apply_validation = true;
if (property_exists($this, 'apply_validation'))
{
	$apply_validation = $this->apply_validation == 'true';
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
	'name'				=> $this->validation->validation->name ? '' : 'error has-error',
	'email'				=> $this->validation->validation->email ? '' : 'error has-error',
	'email2'			=> $this->validation->validation->email2 ? '' : 'error has-error',
	'address1'			=> $this->validation->validation->address1 ? '' : 'error has-error',
	'city'				=> $this->validation->validation->city ? '' : 'error has-error',
	'state'				=> $this->validation->validation->state ? '' : 'error has-error',
	'zip'				=> $this->validation->validation->zip ? '' : 'error has-error',
	'country'			=> $this->validation->validation->country ? '' : 'error has-error',
	'businessname'		=> $this->validation->validation->businessname ? '' : 'error has-error',
	'occupation'		=> !empty($field_data['occupation']) ? '' : 'error has-error',
	'vatnumber'			=> $this->validation->validation->vatnumber ? '' : 'warning has-warning',
);

if(JFactory::getUser()->guest)
{
	$group_classes['username']  = ($this->cache['username']) ? (($this->validation->validation->username ) ? 'success has-success' : 'error has-error') : '';
	$group_classes['password']  = !$this->cache['password'] ? 'error has-error' : '';
	$group_classes['password2'] = (!$this->cache['password2'] || ($this->cache['password2'] != $this->cache['password'])) ? 'error has-error' : '';
}

$styleStateField     = AkeebasubsHelperCparams::getParam('showstatefield' ,  1) ? '' : 'display: none';
$businessFields      = AkeebasubsHelperCparams::getParam('businessfields' , 'auto');
$cparamShowCountries = AkeebasubsHelperCparams::getParam('showcountries'  , '');
$cparamHideCountries = AkeebasubsHelperCparams::getParam('hidecountries'  , '');
$emailasusername     = AkeebasubsHelperCparams::getParam('emailasusername',  0);
?>

<div class="form form-horizontal">

<fieldset>
<?php if(JFactory::getUser()->guest && !$emailasusername):?>
	<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></legend>

	<div class="control-group form-group <?php echo $group_classes['username'] ?>">
		<label for="username" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="username" id="username" value="<?php echo $this->escape($this->cache['username'])?>" />
            </span>
			<span id="username_valid" class="help-inline help-block" <?php if(strpos($group_classes['username'], 'success') == false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_VALID')?>
			</span>
			<span id="username_invalid" class="help-inline help-block" <?php if(strpos($group_classes['username'], 'error') === false):?>style="display:none"<?php endif ?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_INVALID')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['password'] ?>">
		<label for="password" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="password" class="form-control" name="password" id="password" value="<?php echo $this->escape($this->cache['password'])?>" />
            </span>
			<span id="password_invalid" class="help-inline help-block" style="<?php if(strpos($group_classes['password'], 'error') === false): ?>display:none<?php endif; ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['password2'] ?>">
		<label for="password2" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="password" class="form-control" name="password2" id="password2" value="<?php echo $this->escape($this->cache['password2'])?>" />
            </span>
			<span id="password2_invalid" class="help-inline help-block" style="<?php if(strpos($group_classes['password2'], 'error') === false): ?>display:none<?php endif; ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')?>
			</span>
		</div>
	</div>
<?php elseif(JFactory::getUser()->guest && $emailasusername):?>
	<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></legend>
<?php elseif(!JFactory::getUser()->guest): ?>
	<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_EXISTINGACCOUNT')?></legend>

	<div class="control-group form-group">
		<label for="username" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="username" id="username" disabled="disabled" value="<?php echo $this->escape($this->userparams->username)?>" />
           </span>
		</div>
	</div>
<br/>
<?php endif; ?>
</fieldset>

<fieldset>

	<div class="control-group form-group <?php echo $group_classes['name'] ?>">
		<label for="name" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NAME')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="name" id="name" value="<?php echo $this->escape($field_data['name']);?>" />
            </span>
			<span id="name_empty" class="help-inline help-block" <?php if(strpos($group_classes['name'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NAME_INVALID')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['email'] ?>">
		<label for="email" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="email" id="email" value="<?php echo $this->escape($field_data['email']);?>" />
            </span>
			<span id="email_invalid" class="help-inline help-block" <?php if(strpos($group_classes['email'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_EMAIL')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['email2'] ?>">
		<label for="email2" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL2')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="email2" id="email2" value="<?php echo $this->escape($field_data['email2']);?>" />
            </span>
			<span id="email2_invalid" class="help-inline help-block" <?php if(strpos($group_classes['email2'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_EMAIL2')?>
			</span>
		</div>
	</div>

<?php if(JFactory::getUser()->guest && $emailasusername):?>
	<div class="control-group form-group <?php echo $group_classes['password'] ?>">
		<label for="password" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="password" class="form-control" name="password" id="password" value="<?php echo $this->escape($this->cache['password'])?>" />
            </span>
			<span id="password_invalid" class="help-inline help-block" style="<?php if(strpos($group_classes['password'], 'error') === false): ?>display:none<?php endif; ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['password2'] ?>">
		<label for="password2" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="password" class="form-control" name="password2" id="password2" value="<?php echo $this->escape($this->cache['password2'])?>" />
            </span>
			<span id="password2_invalid" class="help-inline help-block" style="<?php if(strpos($group_classes['password2'], 'error') === false): ?>display:none<?php endif; ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')?>
			</span>
		</div>
	</div>
<?php endif; ?>

<?php
// Render per-user custom fields
JLoader::import('joomla.plugin.helper');
JPluginHelper::importPlugin('akeebasubs');
$app = JFactory::getApplication();
$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($this->userparams, array_merge($this->cache,array('subscriptionlevel' => $akeebasubs_subscription_level))));
if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):
if($apply_validation && array_key_exists('isValid', $field)) {
	$customField_class = $field['isValid'] ? (array_key_exists('validLabel', $field) ? 'success has-success' : '') : 'error has-error';
} else {
	$customField_class = '';
}
?>
	<div class="control-group form-group <?php echo $customField_class ?>">
		<label for="<?php echo $field['id']?>" class="control-label col-sm-2">
			<?php echo $field['label']?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <?php echo $field['elementHTML']?>
            </span>
			<?php if(array_key_exists('validLabel', $field)):?>
			<span id="<?php echo $field['id']?>_valid" class="help-inline help-block"
				  style="<?php if(!$field['isValid'] || !$apply_validation):?>display:none<?php endif?>">
					  <?php echo $field['validLabel']?>
			</span>
			<?php endif;?>
			<?php if(array_key_exists('invalidLabel', $field)):?>
			<span id="<?php echo $field['id']?>_invalid" class="help-inline help-block"
				  style="<?php if($field['isValid'] || !$apply_validation):?>display:none<?php endif?>">
					  <?php echo $field['invalidLabel']?>
			</span>
			<?php endif;?>
		</div>
	</div>

<?php endforeach; endforeach;?>

<?php if(AkeebasubsHelperCparams::getParam('personalinfo',1) == -1):?>
	<div class="control-group form-group <?php echo $group_classes['country'] ?>">
		<label for="country" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			<?php
                echo AkeebasubsHelperSelect::countries($field_data['country'], 'country', array(
                        'id'    => 'country',
                        'show'  => $cparamShowCountries,
                        'hide'  => $cparamHideCountries,
                        'class' => 'form-control'
                    ))
            ?>
            </span>
			<span id="country_empty" class="help-inline help-block" <?php if(strpos($group_classes['country'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>
<?php elseif(AkeebasubsHelperCparams::getParam('personalinfo',1) == 1):?>

	<div class="control-group form-group <?php echo $group_classes['address1'] ?>">
		<label for="address1" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="address1" id="address1"
				       value="<?php echo $this->escape($field_data['address1']);?>" />
            </span>
			<span id="address1_empty" class="help-inline help-block" <?php if(strpos($group_classes['address1'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group">
		<label for="address2" class="control-label col-sm-2">
			<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="address2" id="address2"
				       value="<?php echo $this->escape($field_data['address2']);?>" />
            </span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['city'] ?>">
		<label for="city" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_CITY')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="city" id="city" value="<?php echo $this->escape($field_data['city']);?>" />
            </span>
			<span id="city_empty" class="help-inline help-block" <?php if(strpos($group_classes['city'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['city'] ?>" style="<?php echo $styleStateField ?>" id="stateField">
		<label for="state" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_STATE')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <?php echo AkeebasubsHelperSelect::states($field_data['state'], 'state', array('class' => 'form-control')) ?>
            </span>
			<span id="state_empty" class="help-inline help-block" <?php if(strpos($group_classes['city'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['zip'] ?>">
		<label for="zip" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="zip" id="zip" value="<?php echo $this->escape($field_data['zip']);?>" />
            </span>
			<span id="zip_empty" class="help-inline help-block" <?php if(strpos($group_classes['zip'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['country'] ?>">
		<label for="country" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <?php echo AkeebasubsHelperSelect::countries($field_data['country'], 'country', array('show' => $cparamShowCountries, 'hide' => $cparamHideCountries, 'class' => 'form-control')) ?>
            </span>
			<span id="country_empty" class="help-inline help-block" <?php if(strpos($group_classes['country'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

</fieldset>
<fieldset>

<?php if ($businessFields != 'never'): ?>
<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS')?></legend>
<?php endif; ?>

<?php
if ($businessFields == 'never') {
	$isBusiness = 0; $style='display: none';
} elseif ($businessFields == 'always') {
	$isBusiness = 1; $style='display: none';
} else {
	$isBusiness = !empty($this->userparams->isbusiness) ? $this->userparams->isbusiness : (@array_key_exists('isbusiness',$this->cache) ? $this->cache['isbusiness'] : 0);
	$style='';
}
?>

<div class="control-group form-group" style="<?php echo $style ?>">
	<label for="isbusiness" class="control-label col-sm-2">
		* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')?>
	</label>
	<?php if (version_compare(JVERSION, '3.0', 'ge')): ?>
		<?php echo JHTML::_('select.booleanlist', 'isbusiness', array('id'=>'isbusiness'), $isBusiness); ?>
	<?php else: ?>
		<div class="controls">
            <span class="col-sm-3">
			    <?php echo JHTML::_('select.booleanlist', 'isbusiness', array('id'=>'isbusiness'), $isBusiness); ?>
            </span>
		</div>
	<?php endif; ?>
</div>

<?php if($businessFields == 'none'): ?>
<div style="display: none;">
<?php endif; ?>
<div id="businessfields">
	<div class="control-group form-group <?php echo $group_classes['businessname'] ?>">
		<label for="businessname" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="businessname" id="businessname" value="<?php echo $this->escape($field_data['businessname']);?>" />
            </span>
			<span id="businessname_empty" class="help-inline help-block" <?php if(strpos($group_classes['businessname'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['occupation'] ?>">
		<label for="occupation" class="control-label col-sm-2">
			* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_OCCUPATION')?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <input type="text" class="form-control" name="occupation" id="occupation"
    				   value="<?php echo $this->escape($field_data['occupation']);?>" />
            </span>
			<span id="occupation_empty" class="help-inline help-block" <?php if(strpos($group_classes['occupation'], 'error') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?>
			</span>
		</div>
	</div>

	<div class="control-group form-group <?php echo $group_classes['vatnumber'] ?>" id="vatfields">
		<label for="vatnumber" class="control-label col-sm-2" id="vatlabel">
			* <?php echo AkeebasubsHelperCparams::getParam('noneuvat', 0) ? JText::_('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER_ALTLABEL') : JText::_('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER')?>
		</label>
		<div class="controls">
			<span class="input-group input-prepend col-sm-2">
				<span class="input-group-addon add-on" id="vatcountry">EU</span>
				<input type="text" name="vatnumber" id="vatnumber" class="input-small form-control" size="16"
					value="<?php echo $this->escape($field_data['vatnumber']);?>" />
			</span>
			<span id="vat-status-invalid" class="help-inline help-block" <?php if(strpos($group_classes['vatnumber'], 'warning') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_VAT_INVALID')?>
			</span>
			<span id="vat-status-valid" class="help-inline help-block" <?php if(strpos($group_classes['vatnumber'], 'success') === false):?>style="display:none"<?php endif?>>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_VAT_VALID')?>
			</span>
		</div>
	</div>

</div>
<?php if($businessFields == 'none'): ?>
</div>
<?php endif; ?>

<?php endif;?>

<?php
// Render per-subscription fields, only when we have a valid subscription level!
if(!is_null($akeebasubs_subscription_level)):
	JLoader::import('joomla.plugin.helper');
	JPluginHelper::importPlugin('akeebasubs');
	$app = JFactory::getApplication();
	$jResponse = $app->triggerEvent('onSubscriptionFormRenderPerSubFields', array(array_merge($this->cache,array('subscriptionlevel' => $akeebasubs_subscription_level))));
	@ob_start();
	if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
		if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):
			if($apply_validation && array_key_exists('isValid', $field)) {
				$customField_class = $field['isValid'] ? (array_key_exists('validLabel', $field) ? 'success has-success' : '') : 'error has-error';
			} else {
				$customField_class = '';
			}
?>
	<div class="control-group form-group <?php echo $customField_class ?>">
		<label for="<?php echo $field['id']?>" class="control-label col-sm-2">
			<?php echo $field['label']?>
		</label>
		<div class="controls">
            <span class="col-sm-3">
			    <?php echo $field['elementHTML']?>
            </span>
			<?php if(array_key_exists('validLabel', $field)):?>
			<span id="<?php echo $field['id']?>_valid" class="help-inline help-block"
				  style="<?php if(!$field['isValid'] || !$apply_validation):?>display:none<?php endif?>">
					  <?php echo $field['validLabel']?>
			</span>
			<?php endif;?>
			<?php if(array_key_exists('invalidLabel', $field)):?>
			<span id="<?php echo $field['id']?>_invalid" class="help-inline help-block"
				  style="<?php if($field['isValid'] || !$apply_validation):?>display:none<?php endif?>">
					  <?php echo $field['invalidLabel']?>
			</span>
			<?php endif;?>
		</div>
	</div>

<?php
		endforeach;
	endforeach;
	$subfieldsHTML = trim(@ob_get_clean());
	if(!empty($subfieldsHTML)):?>
</fieldset>
<fieldset>

<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_PERSUBFIELDS')?></legend>
<?php echo $subfieldsHTML ?>
<?php
	endif;
endif;
?>

</fieldset>
</div>

<?php
$aks_validate_url = JURI::base().'index.php';
$aks_personal_info = AkeebasubsHelperCparams::getParam('personalinfo',1)?'true':'false';
$aks_noneuvat = AkeebasubsHelperCparams::getParam('noneuvat',0)?'true':'false';
$script = <<< JS

;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
// Akeeba Subscriptions --- START >> >> >>
var akeebasubs_validate_url = "$aks_validate_url";
var akeebasubs_valid_form = false;
var akeebasubs_personalinfo = $aks_personal_info;
var akeebasubs_noneuvat = $aks_noneuvat;
// Akeeba Subscriptions --- END << << <<

JS;
JFactory::getDocument()->addScriptDeclaration($script);
