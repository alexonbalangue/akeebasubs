<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('message');
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/image.php';

// Take display VAT into account
$vatRate = AkeebasubsHelperCparams::getParam('vatrate', 0);
$vatMultiplier = (100 + (int)$vatRate) / 100;
// Take the various inclusions into account
$includesignup = AkeebasubsHelperCparams::getParam('includesignup', 2);
$includediscount = AkeebasubsHelperCparams::getParam('includediscount', 0);
// Only consider discounts if it's a logged in user
$user = JFactory::getUser();
$includediscount = ($includediscount && !$user->guest) ? true : false;

$discounts = array();
?>

<div id="akeebasubs" class="levels">

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistheader')?>

<?php $max = count($this->items); $width = count($this->items) ? (100/count($this->items)) : '100' ?>

	<table class="table table-striped table-condensed table-bordered">
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-level" width="<?php echo $width?>%">
				<a href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&format=html&slug='.$level->slug)?>" class="akeebasubs-strappy-level-link">
					<?php echo $this->escape($level->title)?>
				</a>
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):
			$signupFee = 0;

			if (!in_array($level->akeebasubs_level_id, $this->subIDs) && ($includesignup != 0))
			{
				$signupFee = (float)$level->signupfee;
			}

			if ($includediscount)
			{
				/** @var AkeebasubsModelSubscribes $subscribesModel */
				$subscribesModel = F0FModel::getTmpInstance('Subscribes', 'AkeebasubsModel')->savestate(false);
				$subscribesModel->setState('id', $level->akeebasubs_level_id);
				$subValidation = $subscribesModel->validatePrice(true);
				$discount = $subValidation->discount;
				$levelPrice = $level->price - $discount;

				$formatedPriceD = sprintf('%1.02F', $level->price);
				$dotposD = strpos($formatedPriceD, '.');
				$price_integerD = substr($formatedPriceD,0,$dotposD);
				$price_fractionalD = substr($formatedPriceD,$dotposD+1);
			}
			else
			{
				$discount = 0;
				$levelPrice = $level->price;
			}

			$discounts[$level->akeebasubs_level_id] = $discount;

			if ($includesignup == 1)
			{
				if (($levelPrice + $signupFee) < 0)
				{
					$levelPrice = -$signupFee;
				}

				$formatedPrice = sprintf('%1.02F', ($levelPrice + $signupFee) * $vatMultiplier);
				$levelPrice += $signupFee;
			}
			else
			{
				if ($levelPrice < 0)
				{
					$levelPrice = 0;
				}

				$formatedPrice = sprintf('%1.02F', ($levelPrice) * $vatMultiplier);
			}

			$dotpos = strpos($formatedPrice, '.');
			$price_integer = substr($formatedPrice,0,$dotpos);
			$price_fractional = substr($formatedPrice,$dotpos+1);?>
			<td class="akeebasubs-strappy-price">
				<?php if(AkeebasubsHelperCparams::getParam('renderasfree', 0) && ($levelPrice < 0.01)):?>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE') ?>
				<?php else: ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $price_integer ?></span><?php if((int)$price_fractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
				<?php endif; ?>
			</td>
		<?php endforeach ?>
		</tr>

		<?php if ($includediscount): ?>
		<tr>
			<?php foreach($this->items as $level):
				$discount = 0;

				if (array_key_exists($level->akeebasubs_level_id, $discounts))
				{
					$discount = (float)$discounts[$level->akeebasubs_level_id];
				}

				$formatedPrice = sprintf('%1.02F', $level->price * $vatMultiplier);
				$dotpos = strpos($formatedPrice, '.');
				$price_integer = substr($formatedPrice,0,$dotpos);
				$price_fractional = substr($formatedPrice,$dotpos+1);
				?>
				<td class="akeebasubs-strappy-prediscount">
					<?php if(abs($discount) >= 0.01): ?>
						<span class="akeebasubs-strappy-prediscount-label">
						<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PREDISCOUNT'); ?>
						</span>
						<s>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $price_integer ?></span><?php if((int)$price_fractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
						</s>
					<?php endif; ?>
				</td>
			<?php endforeach ?>
		</tr>
		<?php endif; ?>

		<?php if ($includesignup == 2): ?>
		<tr>
			<?php foreach($this->items as $level):
				$signupFee = 0;
				if (!in_array($level->akeebasubs_level_id, $this->subIDs))
				{
					$signupFee = (float)$level->signupfee;
				}
				$formatedPrice = sprintf('%1.02F', $signupFee * $vatMultiplier);
				$dotpos = strpos($formatedPrice, '.');
				$price_integer = substr($formatedPrice,0,$dotpos);
				$price_fractional = substr($formatedPrice,$dotpos+1);
			?>
			<td class="akeebasubs-strappy-signupfee">
				<?php if(abs($signupFee) >= 0.01): ?>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST'); ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $price_integer ?></span><?php if((int)$price_fractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
				<?php endif; ?>
			</td>
			<?php endforeach ?>
		</tr>
		<?php endif; ?>

		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-image">
				<img src="<?php echo AkeebasubsHelperImage::getURL($level->image)?>" />
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-description">
				<?php echo JHTML::_('content.prepare', AkeebasubsHelperMessage::processLanguage($level->description) );?>
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-subscribe">
				<button
					class="btn btn-inverse btn-primary"
					onclick="window.location='<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>'">
					<?php echo JText::_('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>
				</button>
			</td>
		<?php endforeach ?>
		</tr>
	</table>

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistfooter')?>
</div>