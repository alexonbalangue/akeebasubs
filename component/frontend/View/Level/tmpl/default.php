<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\Select;

/** @var \Akeeba\Subscriptions\Site\View\Level\Html $this */

\JHtml::_('formbehavior.chosen');

$applyValidationBoolean = $this->apply_validation == 'true';

$script = <<<JS


;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
// Akeeba Subscriptions --- START >> >> >>
akeebasubs_level_id = {$this->item->akeebasubs_level_id};
// Akeeba Subscriptions --- END << << <<

JS;
JFactory::getDocument()->addScriptDeclaration($script);

$prepend_class = $this->cparams->currencypos == 'before' ? 'input-prepend' : 'input-append';

$styleDiscount = $this->cparams->showdiscountfield <= 0 ? 'display:none' : '';
$styleTax = $this->cparams->showtaxfield <= 0 ? 'display:none' : '';
$styleRegular = $this->cparams->showregularfield <= 0 ? 'display:none' : '';
$styleCoupon = (($this->cparams->showcouponfield <= 0) && empty($this->cache['coupon'])) ? 'display:none' : '';
$requireCoupon = $this->cparams->reqcoupon;

$paymentMethodsCount = count(Select::paymentmethods('paymentmethod', '', array('id' => 'paymentmethod', 'level_id' => $this->item->akeebasubs_level_id, 'return_raw_list' => 1)));

$hidePaymentMethod = (($paymentMethodsCount <= 1) && $this->cparams->hidelonepaymentoption) || ($this->validation->price->gross < 0.01);
?>

	<div id="akeebasubs">

		<?php if ($this->dnt) : ?>
			<div class="alert alert-block alert-danger" style="text-align: center;font-weight: bold">
				<?php echo JText::_('COM_AKEEBASUBS_DNT_WARNING') ?>
			</div>
		<?php endif; ?>

		<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionsheader') ?>

		<?php if ($this->cparams->stepsbar && ($this->validation->price->net > 0.01)): ?>
			<?php echo $this->loadAnyTemplate('site:com_akeebasubs/Level/steps', array('step' => 'subscribe', 'akeebasubs_subscription_level' => $this->item->akeebasubs_level_id)); ?>
		<?php endif; ?>

		<?php echo $this->loadTemplate('level') ?>

		<noscript>
			<hr/>
			<h1><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER') ?></h1>

			<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY') ?></p>
			<hr/>
		</noscript>

		<?php if (JFactory::getUser()->guest && $this->cparams->allowlogin): ?>
			<?php echo $this->loadTemplate('login') ?>
		<?php endif ?>

		<form
			action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=Subscribe&layout=default&slug=' . $this->input->getString('slug', '')) ?>"
			method="post"
			id="signupForm" class="form form-horizontal">
			<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken(); ?>" value="1"/>

			<?php echo $this->loadTemplate('fields'); ?>

			<?php if ($this->validation->price->net < 0.01): ?>
			<div style="display:none"><?php endif ?>

				<fieldset>
					<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_COUPONANDSUMMARY') ?></legend>

					<noscript>
						<p class="alert alert-warning">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT') ?>
						</p>
					</noscript>

					<div class="control-group form-group" style="<?php echo $styleRegular ?>"
						 id="akeebasubs-sum-net-container">
						<label class="control-label col-sm-2">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_NET') ?>
						</label>

						<div class="controls col-sm-2">
							<div class="input-group <?php echo $prepend_class ?>">
								<?php if ($this->cparams->currencypos == 'before'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
								<input id="akeebasubs-sum-net" type="text" disabled="disabled"
									   class="form-control input-small"
									   value="<?php echo $this->validation->price->net ?>"/>
								<?php if ($this->cparams->currencypos == 'after'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="control-group form-group" style="<?php echo $styleDiscount ?>"
						 id="akeebasubs-sum-discount-container">
						<label class="control-label col-sm-2">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT') ?>
						</label>

						<div class="controls col-sm-2">
							<div class="input-group <?php echo $prepend_class ?>">
								<?php if ($this->cparams->currencypos == 'before'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
								<input id="akeebasubs-sum-discount" type="text" disabled="disabled"
									   class="form-control input-small"
									   value="<?php echo $this->validation->price->discount ?>"/>
								<?php if ($this->cparams->currencypos == 'after'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="control-group form-group" style="<?php echo $styleTax ?>"
						 id="akeebasubs-sum-vat-container">
						<label class="control-label col-sm-2">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_VAT') ?>
							<span id="akeebasubs-sum-vat-percent"><?php echo $this->validation->price->taxrate ?></span>%
						</label>

						<div class="controls col-sm-2">
							<div class="input-group <?php echo $prepend_class ?>">
								<?php if ($this->cparams->currencypos == 'before'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
								<input id="akeebasubs-sum-vat" type="text" disabled="disabled"
									   class="form-control input-small"
									   value="<?php echo $this->validation->price->tax ?>"/>
								<?php if ($this->cparams->currencypos == 'after'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<div class="control-group form-group success">
						<label class="control-label col-sm-2">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_TOTAL') ?>
						</label>

						<div class="controls col-sm-2">
							<div class="input-group <?php echo $prepend_class ?>">
								<?php if ($this->cparams->currencypos == 'before'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
								<input id="akeebasubs-sum-total" type="text" disabled="disabled"
									   class="form-control input-small"
									   value="<?php echo $this->validation->price->gross ?>"/>
								<?php if ($this->cparams->currencypos == 'after'): ?>
									<span
										class="input-group-addon add-on"><?php echo $this->cparams->currencysymbol ?></span>
								<?php endif; ?>
							</div>
						</div>
					</div>

				</fieldset>

				<?php if ($this->validation->price->net < 0.01): ?></div><?php endif ?>

			<fieldset>
				<legend class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUBSCRIBE') ?></legend>

				<?php
				// Render pre-payment custom fields
				$this->getContainer()->platform->importPlugin('akeebasubs');
				$jResponse = $this->getContainer()->platform->runPlugins('onSubscriptionFormPrepaymentRender', array($this->userparams, array_merge($this->cache, array('subscriptionlevel' => $this->item->akeebasubs_level_id))));

				if (is_array($jResponse) && !empty($jResponse))
				{
					foreach ($jResponse as $customFields):
						if (is_array($customFields) && !empty($customFields))
						{
							foreach ($customFields as $field):
								if ($applyValidationBoolean && array_key_exists('isValid', $field))
								{
									$customField_class = $field['isValid'] ? (array_key_exists('validLabel', $field) ? 'success has-success' : '') : 'error has-error';
								}
								else
								{
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
										<?php if (array_key_exists('validLabel', $field)): ?>
											<span id="<?php echo $field['id'] ?>_valid" class="help-inline help-block"
												  style="<?php if (!$field['isValid'] || !$applyValidationBoolean): ?>display:none<?php endif ?>">
				  <?php echo $field['validLabel'] ?>
		</span>
										<?php endif;?>
										<?php if (array_key_exists('invalidLabel', $field)): ?>
											<span id="<?php echo $field['id'] ?>_invalid" class="help-inline help-block"
												  style="<?php if ($field['isValid'] || !$applyValidationBoolean): ?>display:none<?php endif ?>">
				  <?php echo $field['invalidLabel'] ?>
		</span>
										<?php endif;?>
									</div>
								</div>

							<?php endforeach;
						} endforeach;
				} ?>

				<?php if ($requireCoupon || ($this->validation->price->net > 0)): ?>
					<div class="control-group form-group" style="<?php echo $styleCoupon ?>">
						<label for="coupon" class="control-label col-sm-2">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_COUPON') ?>
						</label>

						<div class="controls col-sm-3">
							<input type="text" class="form-control input-medium" name="coupon" id="coupon"
								   value="<?php echo $this->escape($this->cache['coupon']) ?>"/>
						</div>
					</div>
				<?php endif; ?>
			</fieldset>

			<?php if ($hidePaymentMethod): ?>
			<div style="display: none;">
				<?php endif; ?>
				<div id="paymentmethod-container">
					<div class="control-group form-group">
						<label for="paymentmethod" class="control-label col-sm-2">
							<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_METHOD') ?>
						</label>

						<div id="paymentlist-container" class="controls col-sm-3">
							<?php echo Select::paymentmethods('paymentmethod', '', array('id' => 'paymentmethod', 'level_id' => $this->item->akeebasubs_level_id)) ?>
						</div>
					</div>
				</div>
				<?php if ($hidePaymentMethod): ?>
			</div>
		<?php endif; ?>

			<div class="well">
				<button id="subscribenow" class="btn btn-large btn-primary" type="submit"
						style="display:block;margin:auto">
					<?php echo JText::_('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE') ?>
				</button>
				<img class="ui-disable-spinner" src="<?php echo JURI::base() ?>media/com_akeebasubs/images/throbber.gif"
					 style="display: none"/>
			</div>

		</form>

		<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionsfooter') ?>

	</div>

<?php
$aks_personal_info = $this->cparams->personalinfo ? 1 : 0;
$aks_msg_error_overall = JText::_('COM_AKEEBASUBS_LEVEL_ERR_JSVALIDATIONOVERALL', true);
$script = <<<JS

;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
// Akeeba Subscriptions --- START >> >> >>
akeebasubs_fieldprefs = {
	'showregularfield'		: {$this->cparams->showregularfield},
	'showdiscountfield'		: {$this->cparams->showdiscountfield},
	'showtaxfield'			: {$this->cparams->showtaxfield}
};
akeebasubs_apply_validation = {$this->apply_validation};

(function(\$) {
	\$(document).ready(function(){
		// Commented out until we can resolve some strange validation errors for some users
		// \$('#signupForm').submit(onSignupFormSubmit);
		validatePassword();
		validateName();
		validateEmail();
		if($aks_personal_info != 0) {
			validateAddress();
		}
		if($aks_personal_info == 1) {
			validateBusiness();
		}
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