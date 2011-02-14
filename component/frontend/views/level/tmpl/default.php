<?php defined('KOOWA') or die(); ?>
<!-- --
<script src="media://lib_koowa/js/koowa.js" />
<script src="media://com_akeebasubs/js/jquery.js" />
<script src="media://com_akeebasubs/js/signup.js" />
<style src="media://lib_koowa/css/koowa.css" />
<style src="media://com_akeebasubs/css/frontend.css" />
<!-- -->

<div id="akeebasubs">

<?if(KFactory::get('lib.joomla.user')->guest):?>
	<?=@template('default_login')?>
<?endif?>

<form action="<?=@route('view=level&id='.KRequest::get('get.id','int',0))?>" method="post" >
<?if(KFactory::get('lib.joomla.user')->guest):?>
	<h3><?=@text('COM_AKEEBASUBS_LEVEL_NEWACCOUNT')?></h3>
	
	<label for="username" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main" value="" />
	<br/>
	<label for="password" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD')?></label>
	<input type="text" name="password" id="password" class="main" value="" />
	<br/>
	<label for="password2" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_PASSWORD2')?></label>
	<input type="text" name="password2" id="password2" class="main" value="" />
	<br/>
<?else:?>
	<h3><?=@text('COM_AKEEBASUBS_LEVEL_EXISTINGACCOUNT')?></h3>
	
	<label for="username" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_USERNAME')?></label>
	<input type="text" name="username" id="username" class="main disabled" disabled="disabled" value="<?=@escape(@$userparams->username)?>" />
	<br/>
<?endif?>
	<label for="name" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_NAME')?></label>
	<input type="text" name="name" id="name" value="<?=@escape($userparams->name)?>" class="main" />
	<br/>
	<label for="email" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_EMAIL')?></label>
	<input type="text" name="email" id="email" value="<?=@escape($userparams->email)?>" class="main" />
	<br/>
	<label for="address1" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS1')?></label>
	<input type="text" name="address1" id="address1" value="<?=@escape($userparams->address1)?>" class="main" />
	<br/>
	<label for="address2" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ADDRESS2')?></label>
	<input type="text" name="address2" id="address2" value="<?=@escape($userparams->address2)?>" class="main" />
	<br/>
	<label for="country" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_COUNTRY')?></label>
	<?=@helper('admin::com.akeebasubs.template.helper.listbox.countries', array('name' => 'country', 'selected' => $userparams->country))?>
	<br/>
	<div id="stateField">
		<label for="state" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_STATE')?></label>
		<?=@helper('admin::com.akeebasubs.template.helper.listbox.states', array('name' => 'state', 'selected' => $userparams->state))?>
		<br/>
	</div>
	<label for="city" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_CITY')?></label>
	<input type="text" name="city" id="city" value="<?=@escape($userparams->city)?>" class="main" />
	<br/>
	<label for="zip" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ZIP')?></label>
	<input type="text" name="zip" id="zip" value="<?=@escape($userparams->zip)?>" class="main" />
	<br/>
	
	<h3><?=@text('COM_AKEEBASUBS_LEVEL_INVOICINGPREFS')?></h3>
	
	<label for="isbusiness" class="main">* <?=@text('COM_AKEEBASUBS_LEVEL_FIELD_ISBUSINESS')?></label>
	<?=@helper('select.booleanlist', array('name' => 'isbusiness', 'selected' => $userparams->isbusiness))?>
	<div id="businessfields">
		<label for="businessname" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_BUSINESSNAME')?></label>
		<input type="text" name="businessname" id="businessname" value="<?=@escape($userparams->businessname)?>" class="main" />
		<br/>
		<label for="occupation" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_OCCUPATION')?></label>
		<input type="text" name="occupation" id="occupation" value="<?=@escape($userparams->occupation)?>" class="main" />
		<br/>
		<div id="vatfields">
			<label for="vatnumber" class="main" id="vatlabel"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_VATNUMBER')?></label>
			<span id="vatcountry">XX</span>
			<input type="text" name="vatnumber" id="vatnumber" value="<?=@escape($userparams->vatnumber)?>" class="vat" />
			<span id="vatstatus"></span>
		</div>
	</div>
	<br/>
	
	<h3><?=@text('COM_AKEEBASUBS_LEVEL_COUPONANDSUMMARY')?></h3>
	<label for="coupon" class="main"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')?></label>
	<input type="text" name="coupon" id="coupon" value="" class="vat" />
	<button id="validateCoupon"><?=@text('COM_AKEEBASUBS_LEVEL_FIELD_COUPON_VALIDATE')?></button>
	<br/>
	<hr />
	
	<noscript>
	<?=@text('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')?>
	</noscript>
	
	<label class="main"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_NET')?></label>
	<span id="akeebasubs-sum-net" class="currency">0.00</span>
	<span class="currency-symbol"><?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')?></label>
	<span id="akeebasubs-sum-discount" class="currency">- 0.00</span>
	<span class="currency-symbol"><?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_VAT')?></label>
	<span id="akeebasubs-sum-vat" class="currency">0.00</span>
	<span class="currency-symbol"><?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	<br/>
	<label class="main  total"><?=@text('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')?></label>
	<span id="akeebasubs-sum-total" class="currency total">0.00</span>
	<span class="currency-symbol total"><?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
	
</form>

</div>