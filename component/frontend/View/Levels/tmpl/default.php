<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use \Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use \Akeeba\Subscriptions\Admin\Helper\Image;
use \Akeeba\Subscriptions\Admin\Helper\Message;

/** @var \Akeeba\Subscriptions\Site\View\Levels\Html $this */

// Take display VAT into account
$showVat = ComponentParams::getParam('showvat', 0);
/** @var \Akeeba\Subscriptions\Site\Model\TaxHelper $taxModel */
$taxModel = $this->getContainer()->factory->model('TaxHelper')->savestate(0)->setIgnoreRequest(1);
$taxParams = $taxModel->getTaxDefiningParameters();
// Take the various inclusions into account
$includesignup = ComponentParams::getParam('includesignup', 2);
$includediscount = ComponentParams::getParam('includediscount', 0);
// Only consider discounts if it's a logged in user
$user = JFactory::getUser();
$includediscount = ($includediscount && !$user->guest) ? true : false;
?>

<div id="akeebasubs" class="levels">

<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionslistheader')?>

<?php if(!empty($this->items)) foreach($this->items as $level):?>
<?php
	$signupFee = 0;

	if (!in_array($level->akeebasubs_level_id, $this->subIDs) && ($includesignup != 0))
	{
		$signupFee = (float)$level->signupfee;
	}

	$vatRule = $taxModel->getTaxRule($level->akeebasubs_level_id, $taxParams['country'], $taxParams['state'], $taxParams['city'], $taxParams['vies']);
	$vatMultiplier = (100 + (float)$vatRule->taxrate) / 100;

	if ($includediscount)
	{
		/** @var \Akeeba\Subscriptions\Site\Model\Subscribe $subscribeModel */
		$subscribeModel = $this->getContainer()->factory->model('Subscribe')->savestate(0);
		$subscribeModel->setState('id', $level->akeebasubs_level_id);
		$subValidation = $subscribeModel->validatePrice(true);
		$discount = $subValidation->discount;
		$levelPrice = $level->price - $discount;
	}
	else
	{
		$discount = 0;
		$levelPrice = $level->price;
	}

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
	$price_fractional = substr($formatedPrice,$dotpos+1);

	$formatedPriceSU = sprintf('%1.02F', $signupFee * $vatMultiplier);
	$dotposSU = strpos($formatedPriceSU, '.');
	$price_integerSU = substr($formatedPriceSU,0,$dotposSU);
	$price_fractionalSU = substr($formatedPriceSU,$dotposSU+1);
?>
	<div class="level akeebasubs-level-<?php echo $level->akeebasubs_level_id ?>">
		<p class="level-title">
			<span class="level-price">
				<?php if(ComponentParams::getParam('renderasfree', 0) && ($levelPrice < 0.01)):?>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE') ?>
				<?php else: ?>
				<?php if(ComponentParams::getParam('currencypos','before') == 'before'): ?>
				<span class="level-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span>
				<?php endif; ?>
				<span class="level-price-integer"><?php echo $price_integer ?></span><?php if((int)$price_fractional > 0): ?><span class="level-price-separator">.</span><span class="level-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?>
				<?php if(ComponentParams::getParam('currencypos','before') == 'after'): ?>
				<span class="level-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span>
				<?php endif; ?>
				<?php endif; ?>
				<?php if (((float)$vatRule->taxrate > 0.01) && ($levelPrice > 0.01)): ?>
					<span class="level-price-taxnotice">
						<?php echo JText::sprintf('COM_AKEEBASUBS_LEVELS_INCLUDESVAT', (float)$vatRule->taxrate); ?>
					</span>
				<?php endif; ?>
			</span>
			<span class="level-title-text">
				<a href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>">
					<?php echo $this->escape($level->title)?>
				</a>
			</span>
		</p>
		<div class="level-inner">
			<div class="level-description">
				<div class="level-description-inner">
					<?php if(!empty($level->image)):?>
					<img class="level-image" src="<?php echo Image::getURL($level->image)?>" />
					<?php endif;?>

					<?php if(abs($signupFee) >= 0.01):?>
					<b><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST'); ?></b>
					<?php if(ComponentParams::getParam('currencypos','before') == 'before'): ?>
					<span class="level-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span>
					<?php endif; ?>
					<span class="level-price-integer"><?php echo $price_integerSU ?></span><?php if((int)$price_fractionalSU > 0): ?><span class="level-price-separator">.</span><span class="level-price-decimal"><?php echo $price_fractionalSU ?></span><?php endif; ?>
					<?php if(ComponentParams::getParam('currencypos','before') == 'after'): ?>
					<span class="level-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span>
					<?php endif; ?>
					<br/>
					<?php endif; ?>

					<?php echo JHTML::_('content.prepare', Message::processLanguage($level->description));?>
				</div>
			</div>
			<div class="level-clear"></div>
			<div class="level-subscribe">
				<button onclick="window.location='<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>'">
					<?php echo JText::_('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>
				</button>
			</div>
		</div>
	</div>
<?php endforeach;?>
<div class="level-clear"></div>

<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionslistfooter')?>
</div>