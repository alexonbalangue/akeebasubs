<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/signup.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/autosubmit.js?'.AKEEBASUBS_VERSIONHASH);

require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/format.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';
$this->loadHelper('modules');
?>

<div id="akeebasubs">

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionsheader')?>

<?php if(AkeebasubsHelperCparams::getParam('stepsbar',1)):?>
<?php echo $this->loadAnyTemplate('level/steps',array('step'=>'subscribe')); ?>
<?php endif; ?>

<?php echo $this->loadTemplate('level') ?>

<noscript>
<hr/>
<h1><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')?></h1>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')?></p>
<hr/>
</noscript>

<?php if(JFactory::getUser()->guest && AkeebasubsHelperCparams::getParam('showloginonsubscribe',1)):?>
	<?php echo $this->loadTemplate('login') ?>
<?php endif?>

<form action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscribe&layout=default&slug='.FOFInput::getString('slug','',$this->input))?>" method="post"
	id="signupForm" >
	<input type="hidden" name="_token" value="<?php echo JUtility::getToken()?>" />
<?php if(JFactory::getUser()->guest):?>
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></h3>
	
	<label for="username" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main" value="<?php echo $this->escape($this->cache['username'])?>" />
	<span id="username_valid" class="valid" <?php if(!$this->validation->validation->username):?>style="display:none"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_VALID')?><span class="akstriangle akstriangle-green"></span></span>
	<span id="username_invalid" class="invalid" <?php if($this->validation->validation->username):?>style="display:none"<?php else: ?>style="display:inline-block"<?php endif ?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="password" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?></label>
	<input type="password" name="password" id="password" class="main" value="<?php echo $this->escape($this->cache['password'])?>" />
	<span id="password_invalid" class="invalid" style="display:none"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="password2" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?></label>
	<input type="password" name="password2" id="password2" class="main" value="<?php echo $this->escape($this->cache['password2'])?>" />
	<span id="password2_invalid" class="invalid" style="display:none"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
<?php else: ?>
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_EXISTINGACCOUNT')?></h3>
	
	<label for="username" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main disabled" disabled="disabled" value="<?php echo $this->escape($this->userparams->username)?>" />
	<br/>
<?php endif; ?>
	
	<label for="name" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NAME')?></label>
	<input type="text" name="name" id="name" value="<?php echo $this->escape(!empty($this->userparams->name) ? $this->userparams->name : $this->cache['name'])?>" class="main" />
	<span id="name_empty" class="invalid" <?php if($this->validation->validation->name):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NAME_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="email" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')?></label>
	<input type="text" name="email" id="email" value="<?php echo $this->escape( !empty($this->userparams->email) ? $this->userparams->email : $this->cache['email'] )?>" class="main" />
	<span id="email_invalid" class="invalid" <?php if($this->validation->validation->email):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_EMAIL')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>

	<?php
	jimport('joomla.plugin.helper');
	JPluginHelper::importPlugin('akeebasubs');
	$app = JFactory::getApplication();
	$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($this->userparams, $this->cache));
	if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
	if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):?>
	<label for="<?php echo $field['id']?>" class="main"><?php echo $field['label']?></label>
	<?php echo $field['elementHTML']?>
	<?php if(array_key_exists('validLabel', $field)):?><span id="<?php echo $field['id']?>_valid" class="valid" style="<?php if(!$field['isValid']):?>display:none<?php else:?>display:inline-block<?php endif?>"><?php echo $field['validLabel']?><span class="akstriangle akstriangle-green"></span></span><?php endif;?>
	<?php if(array_key_exists('invalidLabel', $field)):?><span id="<?php echo $field['id']?>_invalid" class="invalid" style="<?php if($field['isValid']):?>display:none<?php else:?>display:inline-block<?php endif?>"><?php echo $field['invalidLabel']?><span class="akstriangle akstriangle-red"></span></span><?php endif;?>
	<br/>
	<?php endforeach; endforeach;?>
	
	<?php if(AkeebasubsHelperCparams::getParam('personalinfo',1)):?>
	<label for="address1" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')?></label>
	<input type="text" name="address1" id="address1" value="<?php echo $this->escape(!empty($userparams->address1) ? $userparams->address1 : $this->cache['address1'])?>" class="main" />
	<span id="address1_empty" class="invalid" <?php if($this->validation->validation->address1):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="address2" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')?></label>
	<input type="text" name="address2" id="address2" value="<?php echo $this->escape(!empty($userparams->address2) ? $userparams->address2 : $this->cache['address2'])?>" class="main" />
	<br/>
	<label for="country" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?></label>
	<?php echo AkeebasubsHelperSelect::countries(!empty($userparams->country) && ($userparams->country != 'XX') ? $userparams->country : $this->cache['country'], 'country', array('id'=>'country')) ?>
	<span id="country_empty" class="invalid" <?php if($this->validation->validation->country):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<div id="stateField">
		<label for="state" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_STATE')?></label>
		<?php echo AkeebasubsHelperSelect::states(!empty($userparams->state) ? $userparams->state : $this->cache['state'], 'state', array('id'=>'state')) ?>
		<span id="state_empty" class="invalid" <?php if($this->validation->validation->state):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
		<br/>
	</div>
	<label for="city" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_CITY')?></label>
	<input type="text" name="city" id="city" value="<?php echo $this->escape( !empty($userparams->city) ? $userparams->city : $this->cache['city'] )?>" class="main" />
	<span id="city_empty" class="invalid" <?php if($this->validation->validation->city):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="zip" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')?></label>
	<input type="text" name="zip" id="zip" value="<?php echo $this->escape( !empty($userparams->zip) ? $userparams->zip : $this->cache['zip'] )?>" class="main" />
	<span id="zip_empty" class="invalid" <?php if($this->validation->validation->zip):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS')?></h3>
	
	<label for="isbusiness" class="main">* <?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')?></label>

	<?php echo JHTML::_('select.booleanlist', 'isbusiness', array('id'=>'isbusiness'), !empty($userparams->isbusiness) ? $userparams->isbusiness : (@array_key_exists('isbusiness',$this->cache) ? $this->cache['isbusiness'] : 0)); ?>
	<div id="businessfields">
		<label for="businessname" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')?></label>
		<input type="text" name="businessname" id="businessname" value="<?php echo $this->escape( !empty($userparams->businessname) ? $userparams->businessname : $this->cache['businessname'] )?>" class="main" />
		<span id="businessname_empty" class="invalid" <?php if($this->validation->validation->businessname):?>style="display:none"<?php else:?>style="display:inline-block"<?php endif?>><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
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
	<?php endif;?>
	
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')?></h3>
	<label for="coupon" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')?></label>
	<input type="text" name="coupon" id="coupon" value="<?php echo $this->escape($this->cache['coupon'])?>" class="vat" />
	<br/>
	
	<div id="paymentmethod-container">
		<label for="paymentmethod" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')?></label>
		<?php echo AkeebasubsHelperSelect::paymentmethods('paymentmethod', '', array('id'=>'paymentmethod')) ?>
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
	<span class="currency-symbol"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<br/>
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')?></label>
	<span id="akeebasubs-sum-discount" class="currency"><?php echo $this->validation->price->discount?></span>
	<span class="currency-symbol"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<br/>
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_VAT')?></label>
	<span id="akeebasubs-sum-vat" class="currency"><?php echo $this->validation->price->tax?></span>
	<span class="currency-symbol"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<br/>
	<label class="main  total"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')?></label>
	<span id="akeebasubs-sum-total" class="currency total"><?php echo $this->validation->price->gross?></span>
	<span class="currency-symbol total"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	
	
</form>

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionsfooter')?>

</div>

<script type="text/javascript">
var akeebasubs_validate_url = "<?php echo JURI::base().'index.php'?>";
var akeebasubs_level_id = <?php echo $this->item->akeebasubs_level_id?>;
var akeebasubs_valid_form = false;
var akeebasubs_personalinfo = <?php echo AkeebasubsHelperCparams::getParam('personalinfo',1)?'true':'false'?>;

(function($) {
	$(document).ready(function(){
		// Commented out until we can resolve some strange validation errors for some users
		//$('#signupForm').submit(onSignupFormSubmit);
		validatePassword();
		validateName();
		validateEmail();
		<?php if(AkeebasubsHelperCparams::getParam('personalinfo',1)):?>
		validateAddress();
		validateBusiness();
		<?php endif;?>
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