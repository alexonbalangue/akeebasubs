<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Image;
use Akeeba\Subscriptions\Admin\Helper\Message;

use Akeeba\Subscriptions\Admin\Helper\Select;

$requireCoupon       = $this->cparams->reqcoupon;

$paymentMethodsCount = count(Select::paymentmethods('paymentmethod', '', ['id'              => 'paymentmethod',
																		  'level_id'        => $this->item->akeebasubs_level_id,
																		  'return_raw_list' => 1]));
$hidePaymentMethod   =
		(($paymentMethodsCount <= 1) && $this->cparams->hidelonepaymentoption) || ($this->validation->price->gross < 0.01);

?>
{{-- SUBSCRIPTION LEVEL DESCRIPTION --}}
<div>
	@jhtml('content.prepare', Message::processLanguage($this->item->description))
</div>

<hr />

{{-- PRICE INFORMATION SUMMARY AREA --}}
@unless($this->validation->price->net < 0.01)
<div class="col-xs-6 span6">
	@lang('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')

	<span id="akeebasubs-sum-vat-container" style="display:{{ ($this->validation->price->taxrate > 0) ? 'inline' : 'none' }}">
		(@lang('COM_AKEEBASUBS_LEVEL_SUM_VAT') <span id="akeebasubs-sum-vat-percent">{{{$this->validation->price->taxrate}}}</span>%)
		</span>
</div>

<div class="col-xs-6 span6">
	<span class="label label-success">
		@if ($this->cparams->currencypos == 'before')
		<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
		<span class="akeebasubs-level-price" id="akeebasubs-sum-gross">{{{ $this->validation->price->gross }}}</span>
		@if ($this->cparams->currencypos == 'after')
		<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
		@endif
	</span>
</div>
<div class="clearfix"></div>

<noscript>
	<div class="alert alert-warning">
		<h5>
			<span class="glyphicon glyphicon-alert"></span>
			@lang('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')
		</h5>
		<p>
			@lang('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')
		</p>
	</div>
</noscript>

<hr />
@endunless

{{-- COUPON CODE--}}
@if ($requireCoupon || ($this->validation->price->net > 0))
	<h4>
		@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')
	</h4>
	<input type="text" class="form-control input-medium" name="coupon" id="coupon"
		   placeholder="@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')"
		   value="{{{$this->cache['coupon']}}}"/>
@endif

{{-- CUSTOM FIELDS --}}
<div>
	<h3>@lang('COM_AKEEBASUBS_LEVEL_SUBSCRIBE')</h3>

	@include('site:com_akeebasubs/Level/default_prepayment')
</div>

{{-- PAYMENT METHODS --}}
<div id="paymentmethod-container" class="{{$hidePaymentMethod ? 'hidden' : ''}}">
	<div class="form-group">
		<label for="paymentmethod" class="control-label col-sm-4">
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

{{-- SUBSCRIBE BUTTON --}}
<div class="well">
	<button id="subscribenow" class="btn btn-large btn-primary" type="submit"
			style="display:block;margin:auto">
		@lang('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')
	</button>
	<img class="ui-disable-spinner" src="{{{JUri::base()}}}media/com_akeebasubs/images/throbber.gif"
		 style="display: none"/>
</div>