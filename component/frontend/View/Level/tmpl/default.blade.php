<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Select;

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

\JHtml::_('formbehavior.chosen');

$applyValidationBoolean = $this->apply_validation == 'true';

$script = <<<JS

akeebasubs_level_id = {$this->item->akeebasubs_level_id};

JS;
JFactory::getDocument()->addScriptDeclaration($script);

$requireCoupon       = $this->cparams->reqcoupon;
$paymentMethodsCount = count(Select::paymentmethods('paymentmethod', '', ['id'              => 'paymentmethod',
																		  'level_id'        => $this->item->akeebasubs_level_id,
																		  'return_raw_list' => 1]));
$hidePaymentMethod   =
	(($paymentMethodsCount <= 1) && $this->cparams->hidelonepaymentoption) || ($this->validation->price->gross < 0.01);
?>

<div id="akeebasubs">

	@if($this->dnt)
	<div class="alert alert-block alert-danger" style="text-align: center;font-weight: bold">
		@lang('COM_AKEEBASUBS_DNT_WARNING')
	</div>
	@endif

	@modules('akeebasubscriptionsheader')

	@if ($this->cparams->stepsbar && ($this->validation->price->net > 0.01))
	@include('site:com_akeebasubs/Level/steps', [
	'step' => 'subscribe',
	'akeebasubs_subscription_level' => $this->item->akeebasubs_level_id
	])
	@endif

	@include('site:com_akeebasubs/Level/default_level')

	<noscript>
		<hr/>
		<h1>@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')</h1>
		<p>@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')</p>
		<hr/>
	</noscript>

	@if (JFactory::getUser()->guest)
		@include('site:com_akeebasubs/Level/default_login')
	@endif

	<form
		action="@route('index.php?option=com_akeebasubs&view=Subscribe&layout=default&slug=' . $this->input->getString('slug', ''))"
		method="post"
		id="signupForm" class="form form-horizontal">

		<input type="hidden" name="{{{ JFactory::getSession()->getFormToken() }}}" value="1"/>

		@include('site:com_akeebasubs/Level/default_fields')

		@include('site:com_akeebasubs/Level/default_summary')

		<fieldset>
			<legend class="subs">@lang('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')</legend>

			<?php
			// Render pre-payment custom fields
			$this->getContainer()->platform->importPlugin('akeebasubs');
			$jResponse =
				$this->getContainer()->platform->runPlugins('onSubscriptionFormPrepaymentRender', array($this->userparams,
					array_merge($this->cache, array('subscriptionlevel' => $this->item->akeebasubs_level_id))));

			if (is_array($jResponse) && !empty($jResponse))
			{
				foreach ($jResponse as $customFields):
					if (is_array($customFields) && !empty($customFields))
					{
						foreach ($customFields as $field):
							if ($applyValidationBoolean && array_key_exists('isValid', $field))
							{
								$customField_class = $field['isValid'] ?
									(array_key_exists('validLabel', $field) ? 'success has-success' : '') :
									'error has-error';
							}
							else
							{
								$customField_class = '';
							}
							?>
							<div class="control-group form-group {{{$customField_class}}}">
								<label for="{{{$field['id']}}}" class="control-label col-sm-2">
									{{$field['label']}}
								</label>

								<div class="controls">
									<span class="col-sm-3">
										{{$field['elementHTML']}}
									</span>
									<?php if (array_key_exists('validLabel', $field)): ?>
										<span id="{{{$field['id']}}}_valid" class="help-inline help-block"
											  style="<?php if (!$field['isValid'] || !$applyValidationBoolean): ?>display:none<?php endif ?>">{{$field['validLabel']}}</span>
									<?php endif; ?>
									<?php if (array_key_exists('invalidLabel', $field)): ?>
										<span id="{{$field['id']}}_invalid" class="help-inline help-block"
											  style="<?php if ($field['isValid'] || !$applyValidationBoolean): ?>display:none<?php endif ?>">{{$field['invalidLabel']}}</span>
									<?php endif; ?>
								</div>
							</div>

						<?php endforeach;
					} endforeach;
			} ?>

			@if ($requireCoupon || ($this->validation->price->net > 0)):
				<div class="control-group form-group">
					<label for="coupon" class="control-label col-sm-2">
						@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')
					</label>

					<div class="controls col-sm-3">
						<input type="text" class="form-control input-medium" name="coupon" id="coupon"
							   value="{{{$this->cache['coupon']}}}"/>
					</div>
				</div>
			@endif
		</fieldset>

		<div id="paymentmethod-container" class="{{$hidePaymentMethod ? 'hidden' : ''}}">
				<div class="control-group form-group">
					<label for="paymentmethod" class="control-label col-sm-2">
						@lang('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')
					</label>

					<div id="paymentlist-container" class="controls col-sm-3">
						<?php
						$country = !empty($this->userparams->country) && ($this->userparams->country != 'XX') ?
							$this->userparams->country : $this->cache['country'];

						/** @var \Akeeba\Subscriptions\Site\Model\PaymentMethods $paymentMethods */
						$paymentMethods = $this->getContainer()->factory->model('PaymentMethods')->tmpInstance();
						$defaultPayment = $paymentMethods->getLastPaymentPlugin(JFactory::getUser()->id, $country);

						echo Select::paymentmethods(
							'paymentmethod',
							$defaultPayment,
							array(
								'id'       => 'paymentmethod',
								'level_id' => $this->item->akeebasubs_level_id,
								'country'  => $country
							)
						) ?>
					</div>
				</div>
			</div>

		<div class="well">
			<button id="subscribenow" class="btn btn-large btn-primary" type="submit"
					style="display:block;margin:auto">
				@lang('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')
			</button>
			<img class="ui-disable-spinner" src="{{{JUri::base()}}}media/com_akeebasubs/images/throbber.gif"
				 style="display: none"/>
		</div>

	</form>

	@modules('akeebasubscriptionsfooter')

</div>

<?php
$aks_msg_error_overall = JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL', true);
$script                = <<<JS

akeebasubs_apply_validation = {$this->apply_validation};

(function(\$) {
	\$(document).ready(function(){
		// Commented out until we can resolve some strange validation errors for some users
		// \$('#signupForm').submit(onSignupFormSubmit);
		validatePassword();
		validateName();
		validateEmail();
		validateAddress();
		validateBusiness();
		validateForm();
	});
})(akeeba.jQuery);

function onSignupFormSubmit()
{
	if(akeebasubs_valid_form == false) {
		alert('$aks_msg_error_overall');
	}

	return akeebasubs_valid_form;
}
// Akeeba Subscriptions --- END << << <<

JS;
JFactory::getDocument()->addScriptDeclaration($script);