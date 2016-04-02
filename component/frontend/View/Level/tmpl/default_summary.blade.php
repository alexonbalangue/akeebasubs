<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Image;
use Akeeba\Subscriptions\Admin\Helper\Message;

$requireCoupon       = $this->cparams->reqcoupon;
?>
{{-- REPEATABLE CONSTRUCT priceStructure --}}
@repeatable('priceStructure', $id, $price)
	@if ($this->cparams->currencypos == 'before')
	<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
	@endif
	<span class="akeebasubs-level-price" id="akeebasubs-sum-{{{$id}}}">
		{{{ $price }}}
	</span>
	@if ($this->cparams->currencypos == 'after')
	<span class="akeebasubs-level-price-currency">{{{ $this->cparams->currencysymbol }}}</span>
	@endif
@endRepeatable

<div class="panel panel-default" id="akeebasubs-panel-order">
	<div class="panel-heading">
		<h3 class="panel-title">
			@lang('COM_AKEEBASUBS_LEVEL_LBL_YOURORDER')
			<span class="label label-default label-inverse">{{{$this->item->title}}}</span>
		</h3>
	</div>
	<div class="panel-body">
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
				@yieldRepeatable('priceStructure', 'gross', $this->validation->price->gross)
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

		{{-- Coupon code --}}
		@if ($requireCoupon || ($this->validation->price->net > 0))
		<h4>
			@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')
		</h4>
		<input type="text" class="form-control input-medium" name="coupon" id="coupon"
			   placeholder="@lang('COM_AKEEBASUBS_LEVEL_FIELD_COUPON')"
			   value="{{{$this->cache['coupon']}}}"/>
		@endif

	</div>
</div>

