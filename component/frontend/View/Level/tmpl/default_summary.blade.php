<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

?>
{{-- REPEATABLE CONSTRUCT priceStructure --}}
@repeatable('priceStructure', $id, $price)
<div class="input-group input-{{ $this->cparams->currencypos == 'before' ? 'prepend' : 'append' }}">
	@if ($this->cparams->currencypos == 'before')
	<span class="input-group-addon add-on">{{{ $this->cparams->currencysymbol }}}</span>
	@endif
	<input id="akeebasubs-sum-{{{$id}}}" type="text" disabled="disabled"
		   class="form-control input-small"
		   value="{{{ $price }}}"/>
	@if ($this->cparams->currencypos == 'after')
	<span class="input-group-addon add-on">{{{ $this->cparams->currencysymbol }}}</span>
	@endif
</div>
@endRepeatable

{{-- COUPON AND SUMMARY AREA --}}
<fieldset class="{{($this->validation->price->net < 0.01) ? 'hidden' : ''}}">
	<legend>@lang('COM_AKEEBASUBS_LEVEL_COUPONANDSUMMARY')</legend>

	<noscript>
		<p class="alert alert-warning">
			@lang('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')
		</p>
	</noscript>

	<div class="control-group form-group" id="akeebasubs-sum-net-container">
		<label class="control-label col-sm-2">
			@lang('COM_AKEEBASUBS_LEVEL_SUM_NET')
		</label>

		<div class="controls col-sm-2">
			@yieldRepeatable('priceStructure', 'net', $this->validation->price->net)
		</div>
	</div>

	<div class="control-group form-group" id="akeebasubs-sum-discount-container">
		<label class="control-label col-sm-2">
			@lang('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')
		</label>

		<div class="controls col-sm-2">
			@yieldRepeatable('priceStructure', 'discount', $this->validation->price->discount)
		</div>
	</div>

	<div class="control-group form-group" id="akeebasubs-sum-vat-container">
		<label class="control-label col-sm-2">
			@lang('COM_AKEEBASUBS_LEVEL_SUM_VAT')
			<span id="akeebasubs-sum-vat-percent">{{{$this->validation->price->taxrate}}}</span>%
		</label>

		<div class="controls col-sm-2">
			@yieldRepeatable('priceStructure', 'tax', $this->validation->price->tax)
		</div>
	</div>

	<div class="control-group form-group success">
		<label class="control-label col-sm-2">
			@lang('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')
		</label>

		<div class="controls col-sm-2">
			@yieldRepeatable('priceStructure', 'gross', $this->validation->price->gross)
		</div>
	</div>

</fieldset>