<?php defined('KOOWA') or die(); ?>
<!--
<script src="media://com_akeebasubs/js/jquery.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/blockui.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/signup.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<div id="akeebasubs">

<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionsheader'))?>

<?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->stepsbar ? @template('steps',array('step' => 'subscribe')) : ''?>

<?=@template('default_level', array('level' => $level))?>

<noscript>
<hr/>
<h1><?=@text('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')?></h1>
<p><?=@text('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')?></p>
<hr/>
</noscript>

<?if(KFactory::get('lib.joomla.user')->guest):?>
	<?=@template('default_login')?>
<?endif?>

<form action="<?=@route('view=subscribe&layout=default&slug='.KRequest::get('get.slug','slug'))?>" method="post"
	id="signupForm" >
	<input type="hidden" name="_token" value="<?=JUtility::getToken()?>" />
<?if(KFactory::get('lib.joomla.user')->guest):?>
	<h3 class="subs"><?=@text('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></h3>
	
	<label for="username" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main" value="<?=@escape($cache['username'])?>" />
	<span id="username_valid" class="valid" <?if(!$validation->validation->username):?>style="display:none"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_VALID')?><span class="akstriangle akstriangle-green"></span></span>
	<span id="username_invalid" class="invalid" <?if($validation->validation->username):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="password" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?></label>
	<input type="password" name="password" id="password" class="main" value="<?=@escape($cache['password'])?>" />
	<span id="password_invalid" class="invalid" style="display:none"><?=@text('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD_EMPTY')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="password2" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?></label>
	<input type="password" name="password2" id="password2" class="main" value="<?=@escape($cache['password2'])?>" />
	<span id="password2_invalid" class="invalid" style="display:none"><?=@text('COM_AKEEBASUBS_LEVEL_ERR_PASSWORD2')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
<?else:?>
	<h3 class="subs"><?=@text('COM_AKEEBASUBS_LEVEL_EXISTINGACCOUNT')?></h3>
	
	<label for="username" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main disabled" disabled="disabled" value="<?=@escape(@$userparams->username)?>" />
	<br/>
<?endif?>
	<label for="name" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_NAME')?></label>
	<input type="text" name="name" id="name" value="<?=@escape(!empty($userparams->name) ? $userparams->name : $cache['name'])?>" class="main" />
	<span id="name_empty" class="invalid" <?if($validation->validation->name):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="email" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')?></label>
	<input type="text" name="email" id="email" value="<?=@escape( !empty($userparams->email) ? $userparams->email : $cache['email'] )?>" class="main" />
	<span id="email_invalid" class="invalid" <?if($validation->validation->email):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_EMAIL')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="address1" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')?></label>
	<input type="text" name="address1" id="address1" value="<?=@escape(!empty($userparams->address1) ? $userparams->address1 : $cache['address1'])?>" class="main" />
	<span id="address1_empty" class="invalid" <?if($validation->validation->address1):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="address2" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')?></label>
	<input type="text" name="address2" id="address2" value="<?=@escape(!empty($userparams->address2) ? $userparams->address2 : $cache['address2'])?>" class="main" />
	<br/>
	<label for="country" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?></label>
	<?=@helper('admin::com.akeebasubs.template.helper.listbox.countries', array('name' => 'country', 'selected' => ( !empty($userparams->country) && ($userparams->country != 'XX') ? $userparams->country : $cache['country'] ) ))?>
	<span id="country_empty" class="invalid" <?if($validation->validation->country):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<div id="stateField">
		<label for="state" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_STATE')?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.states', array('name' => 'state', 'selected' => ( !empty($userparams->state) ? $userparams->state : $cache['state'] ) ))?>
		<span id="state_empty" class="invalid" <?if($validation->validation->state):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
		<br/>
	</div>
	<label for="city" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_CITY')?></label>
	<input type="text" name="city" id="city" value="<?=@escape( !empty($userparams->city) ? $userparams->city : $cache['city'] )?>" class="main" />
	<span id="city_empty" class="invalid" <?if($validation->validation->city):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	<label for="zip" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')?></label>
	<input type="text" name="zip" id="zip" value="<?=@escape( !empty($userparams->zip) ? $userparams->zip : $cache['zip'] )?>" class="main" />
	<span id="zip_empty" class="invalid" <?if($validation->validation->zip):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
	<br/>
	
	<h3 class="subs"><?=@text('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS')?></h3>
	
	<label for="isbusiness" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')?></label>
	<?=@helper('select.booleanlist', array('name' => 'isbusiness', 'selected' => ( !empty($userparams->isbusiness) ? $userparams->isbusiness : $cache['isbusiness'] ) , 'deselect' => true ))?>
	<div id="businessfields">
		<label for="businessname" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')?></label>
		<input type="text" name="businessname" id="businessname" value="<?=@escape( !empty($userparams->businessname) ? $userparams->businessname : $cache['businessname'] )?>" class="main" />
		<span id="businessname_empty" class="invalid" <?if($validation->validation->businessname):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
		<br/>
		<label for="occupation" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_OCCUPATION')?></label>
		<input type="text" name="occupation" id="occupation" value="<?=@escape( !empty($userparams->occupation) ? $userparams->occupation : $cache['occupation'] )?>" class="main" />
		<span id="occupation_empty" class="invalid" <?if($validation->validation->occupation):?>style="display:none"<?else:?>style="display:inline-block"<?endif?>><?=@text('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED')?><span class="akstriangle akstriangle-red"></span></span>
		<br/>
		<div id="vatfields">
			<label for="vatnumber" class="main" id="vatlabel"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER')?></label>
			<span id="vatcountry"></span>
			<input type="text" name="vatnumber" id="vatnumber" value="<?=@escape( !empty($userparams->vatnumber) ? $userparams->vatnumber : $cache['vatnumber'] )?>" class="vat" />
			<span id="vat-status-invalid" class="invalid" style="display:none"><?=@text('COM_AKEEBASUBS_LEVEL_VAT_INVALID')?><span class="akstriangle akstriangle-red"></span></span>
			<span id="vat-status-valid" class="valid" style="display:none"><?=@text('COM_AKEEBASUBS_LEVEL_VAT_VALID')?><span class="akstriangle akstriangle-green"></span></span>
		</div>
	</div>
	<br/>
	
	<h3 class="subs"><?=@text('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')?></h3>
	<label for="coupon" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')?></label>
	<input type="text" name="coupon" id="coupon" value="<?=@escape($cache['coupon'])?>" class="vat" />
	<br/>
	
	<div id="paymentmethod-container">
		<label for="paymentmethod" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.paymentmethods')?>
		<br/>
	</div>
	<label for="subscribenow" class="main">&nbsp;</label>
	<input id="subscribenow" type="submit" value="<?=@text('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')?>" />
	<img id="ui-disable-spinner" src="<?=JURI::base()?>media/com_akeebasubs/images/throbber.gif" style="display: none" />

	<h3 class="subs"><?=@text('COM_AKEEBASUBS_LEVEL_COUPONANDSUMMARY')?></h3>
	
	<noscript>
		<p>
			<?=@text('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')?>
		</p>
	</noscript>
	
	<label class="main"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_NET')?></label>
	<span id="akeebasubs-sum-net" class="currency"><?=$validation->price->net?></span>
	<span class="currency-symbol"><?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')?></label>
	<span id="akeebasubs-sum-discount" class="currency"><?=$validation->price->discount?></span>
	<span class="currency-symbol"><?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_VAT')?></label>
	<span id="akeebasubs-sum-vat" class="currency"><?=$validation->price->tax?></span>
	<span class="currency-symbol"><?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main  total"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')?></label>
	<span id="akeebasubs-sum-total" class="currency total"><?=$validation->price->gross?></span>
	<span class="currency-symbol total"><?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	
	
</form>

<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionsfooter'))?>

</div>

<script type="text/javascript">
var akeebasubs_validate_url = "<?=JURI::base().'index.php'?>";
var akeebasubs_level_id = <?=$level->id?>;
var akeebasubs_valid_form = false;

(function($) {
	$(document).ready(function(){
		$('#signupForm').submit(onSignupFormSubmit);
		validatePassword();
		validateName();
		validateEmail();
		validateAddress();
		validateBusiness();
	});
})(akeeba.jQuery);

function onSignupFormSubmit()
{
	if(akeebasubs_valid_form == false) {
		alert('<?=@text('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL')?>');
	}
	
	return akeebasubs_valid_form;
}
</script>