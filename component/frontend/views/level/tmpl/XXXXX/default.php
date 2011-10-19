<?php defined('KOOWA') or die(); ?>
<!--
<script src="media://com_akeebasubs/js/akeebajq.js?<?php echo AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/blockui.js?<?php echo AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/signup.js?<?php echo AKEEBASUBS_VERSIONHASH?>" />
<script src="media://lib_koowa/js/koowa.js?<?php echo AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?php echo AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?php echo AKEEBASUBS_VERSIONHASH?>" />
-->

<div id="akeebasubs">

<?php echo @helper('com://site/akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionsheader'))?>

<?php echo KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->stepsbar ? @template('steps',array('step' => 'subscribe')) : ''?>

<?php echo @template('default_level', array('level' => $level))?>

<noscript>
<hr/>
<h1><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')?></h1>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')?></p>
<hr/>
</noscript>

<?php if(JFactory::getUser()->guest):?>
	<?php echo @template('default_login')?>
<?endif?>

<form action="<?php echo @route('view=subscribe&layout=default&slug='.KRequest::get('get.slug','slug'))?>" method="post"
	id="signupForm" >
	<input type="hidden" name="_token" value="<?php echo JUtility::getToken()?>" />
<?php if(JFactory::getUser()->guest):?>
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></h3>
	
	<label for="username" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main" value="<?php echo $this->escape($this->cache['username'])?>" />
	<span id="username_valid" class="valid" <?php if(!$this->validation->validation->username):?>style="display:none"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_VALID')?><span class="akstriangle akstriangle-green"></span></span>
	<span id="username_invalid" class="invalid" <?php if($this->validation->validation->username):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="password" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?></label>
	<input type="password" name="password" id="password" class="main" value="<?php echo $this->escape($this->cache['password'])?>" />
	<span id="password_invalid" class="invalid" style="display:none"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="password2" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?></label>
	<input type="password" name="password2" id="password2" class="main" value="<?php echo $this->escape($this->cache['password2'])?>" />
	<span id="password2_invalid" class="invalid" style="display:none"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
<?else:?>
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_EXISTINGACCOUNT')?></h3>
	
	<label for="username" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main disabled" disabled="disabled" value="<?php echo $this->escape($userparams->username)?>" />
	<br/>
<?endif?>
	<label for="name" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NAME')?></label>
	<input type="text" name="name" id="name" value="<?php echo $this->escape(!empty($userparams->name) ? $userparams->name : $this->cache['name'])?>" class="main" />
	<span id="name_empty" class="invalid" <?php if($this->validation->validation->name):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NAME_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="email" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')?></label>
	<input type="text" name="email" id="email" value="<?php echo $this->escape( !empty($userparams->email) ? $userparams->email : $this->cache['email'] )?>" class="main" />
	<span id="email_invalid" class="invalid" <?php if($this->validation->validation->email):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_EMAIL')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<?
	jimport('joomla.plugin.helper');
	JPluginHelper::importPlugin('akeebasubs');
	$app = JFactory::getApplication();
	$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($this->userparams, $this->cache));
	if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
	if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):?>
	<label for="<?php echo $field['id']?>" class="main"><?php echo $field['label']?></label>
	<?php echo $field['elementHTML']?>
	<?php if(array_key_exists('validLabel', $field)):?><span id="<?php echo $field['id']?>_valid" class="valid" style="<?php if(!$field['isValid']):?>display:none<?else:?>display:inline-block<?endif?>"><?php echo $field['validLabel']?><span class="akstriangle akstriangle-green"></span></span><?endif;?>
	<?php if(array_key_exists('invalidLabel', $field)):?><span id="<?php echo $field['id']?>_invalid" class="invalid" style="<?php if($field['isValid']):?>display:none<?else:?>display:inline-block<?endif?>"><?php echo $field['invalidLabel']?><span class="akstriangle akstriangle-red"></span></span><?endif;?>
	<br/>
	<?endforeach; endforeach;?>
	
	<?php if(KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->personalinfo):?>
	<label for="address1" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')?></label>
	<input type="text" name="address1" id="address1" value="<?php echo $this->escape(!empty($userparams->address1) ? $userparams->address1 : $this->cache['address1'])?>" class="main" />
	<span id="address1_empty" class="invalid" <?php if($this->validation->validation->address1):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="address2" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')?></label>
	<input type="text" name="address2" id="address2" value="<?php echo $this->escape(!empty($userparams->address2) ? $userparams->address2 : $this->cache['address2'])?>" class="main" />
	<br/>
	<label for="country" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?></label>
	<?php echo @helper('com://admin/akeebasubs.template.helper.listbox.countries', array('name' => 'country', 'selected' => ( !empty($userparams->country) && ($userparams->country != 'XX') ? $userparams->country : $this->cache['country'] ) ))?>
	<span id="country_empty" class="invalid" <?php if($this->validation->validation->country):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<div id="stateField">
		<label for="state" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_STATE')?></label>
		<?php echo @helper('com://admin/akeebasubs.template.helper.listbox.states', array('name' => 'state', 'selected' => ( !empty($userparams->state) ? $userparams->state : $this->cache['state'] ) ))?>
		<span id="state_empty" class="invalid" <?php if($this->validation->validation->state):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
		<br/>
	</div>
	<label for="city" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_CITY')?></label>
	<input type="text" name="city" id="city" value="<?php echo $this->escape( !empty($userparams->city) ? $userparams->city : $this->cache['city'] )?>" class="main" />
	<span id="city_empty" class="invalid" <?php if($this->validation->validation->city):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="zip" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')?></label>
	<input type="text" name="zip" id="zip" value="<?php echo $this->escape( !empty($userparams->zip) ? $userparams->zip : $this->cache['zip'] )?>" class="main" />
	<span id="zip_empty" class="invalid" <?php if($this->validation->validation->zip):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS')?></h3>
	
	<label for="isbusiness" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')?></label>
	<?php echo @helper('select.booleanlist', array('name' => 'isbusiness', 'selected' => ( !empty($userparams->isbusiness) ? $userparams->isbusiness : (@array_key_exists('isbusiness',$this->cache) ? $this->cache['isbusiness'] : 0) ) , 'deselect' => true ))?>
	<div id="businessfields">
		<label for="businessname" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')?></label>
		<input type="text" name="businessname" id="businessname" value="<?php echo $this->escape( !empty($userparams->businessname) ? $userparams->businessname : $this->cache['businessname'] )?>" class="main" />
		<span id="businessname_empty" class="invalid" <?php if($this->validation->validation->businessname):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
		<br/>
		<label for="occupation" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_OCCUPATION')?></label>
		<input type="text" name="occupation" id="occupation" value="<?php echo $this->escape( !empty($userparams->occupation) ? $userparams->occupation : $this->cache['occupation'] )?>" class="main" />
		<br/>
		<div id="vatfields">
			<label for="vatnumber" class="main" id="vatlabel"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER')?></label>
			<span id="vatcountry"></span>
			<input type="text" name="vatnumber" id="vatnumber" value="<?php echo $this->escape( !empty($userparams->vatnumber) ? $userparams->vatnumber : $this->cache['vatnumber'] )?>" class="vat" />
			<span id="vat-status-invalid" class="invalid" style="display:none"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_VAT_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
			<span id="vat-status-valid" class="valid" style="display:none"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_VAT_VALID')?><span class="akstriangle akstriangle-green"></span></span>
		</div>
	</div>
	<br/>
	<?endif;?>
	
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')?></h3>
	<label for="coupon" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')?></label>
	<input type="text" name="coupon" id="coupon" value="<?php echo $this->escape($this->cache['coupon'])?>" class="vat" />
	<br/>
	
	<div id="paymentmethod-container">
		<label for="paymentmethod" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')?></label>
		<?php echo @helper('com://admin/akeebasubs.template.helper.listbox.paymentmethods')?>
		<br/>
	</div>
	<label for="subscribenow" class="main">&nbsp;</label>
	<input id="subscribenow" type="submit" value="<?php echo JText::_('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')?>" />
	<img id="ui-disable-spinner" src="<?php echo JURI::base()?>media/com_akeebasubs/images/throbber.gif" style="display: none" />

	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_COUPONANDSUMMARY')?></h3>
	
	<noscript>
		<p>
			<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')?>
		</p>
	</noscript>
	
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_NET')?></label>
	<span id="akeebasubs-sum-net" class="currency"><?php echo $this->validation->price->net?></span>
	<span class="currency-symbol"><?php echo KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')?></label>
	<span id="akeebasubs-sum-discount" class="currency"><?php echo $this->validation->price->discount?></span>
	<span class="currency-symbol"><?php echo KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_VAT')?></label>
	<span id="akeebasubs-sum-vat" class="currency"><?php echo $this->validation->price->tax?></span>
	<span class="currency-symbol"><?php echo KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main  total"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')?></label>
	<span id="akeebasubs-sum-total" class="currency total"><?php echo $this->validation->price->gross?></span>
	<span class="currency-symbol total"><?php echo KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	
	
</form>

<?php echo @helper('com://site/akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionsfooter'))?>

</div>

<script type="text/javascript">
var akeebasubs_validate_url = "<?php echo JURI::base().'index.php'?>";
var akeebasubs_level_id = <?php echo $level->id?>;
var akeebasubs_valid_form = false;
var akeebasubs_personalinfo = <?php echo KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->personalinfo?'true':'false'?>;

(function($) {
	$(document).ready(function(){
		// Commented out until we can resolve some strange validation errors for some users
		//$('#signupForm').submit(onSignupFormSubmit);
		validatePassword();
		validateName();
		validateEmail();
		<?php if(KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->personalinfo):?>
		validateAddress();
		validateBusiness();
		<?endif;?>
	});
})(akeeba.jQuery);

function onSignupFormSubmit()
{
	if(akeebasubs_valid_form == false) {
		alert('<?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL')?>');
	}
	
	return akeebasubs_valid_form;
}
</script>