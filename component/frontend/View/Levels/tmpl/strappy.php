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

$discounts = array();
?>

<div id="akeebasubs" class="levels">

<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionslistheader')?>

<?php $max = count($this->items); $width = count($this->items) ? (100/count($this->items)) : '100' ?>

	<table class="table table-striped table-condensed table-bordered">
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-level" width="<?php echo $width?>%">
				<a href="<?php echo \JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&format=html&slug='.$level->slug)?>" class="akeebasubs-strappy-level-link">
					<?php echo $this->escape($level->title)?>
				</a>
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):
			$priceInfo = $this->getLevelPriceInformation($level);
			?>
			<td class="akeebasubs-strappy-price">
				<?php if($this->renderAsFree && ($priceInfo->levelPrice < 0.01)):?>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE') ?>
				<?php else: ?>
				<?php if(ComponentParams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $priceInfo->priceInteger ?></span><?php if((int)$priceInfo->priceFractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $priceInfo->priceFractional ?></span><?php endif; ?><?php if(ComponentParams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span><?php endif; ?>
				<?php endif; ?>
				<?php if (((float)$priceInfo->vatRule->taxrate > 0.01) && ($priceInfo->levelPrice > 0.01)): ?>
					<div class="akeebasubs-strappy-taxnotice">
						<?php echo JText::sprintf('COM_AKEEBASUBS_LEVELS_INCLUDESVAT', (float)$priceInfo->vatRule->taxrate); ?>
					</div>
				<?php endif; ?>
			</td>
		<?php endforeach ?>
		</tr>

		<?php if ($this->includeDiscount): ?>
		<tr>
			<?php foreach($this->items as $level):
				$priceInfo = $this->getLevelPriceInformation($level);
				?>
				<td class="akeebasubs-strappy-prediscount">
					<?php if(abs($priceInfo->discount) >= 0.01): ?>
						<span class="akeebasubs-strappy-prediscount-label">
						<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PREDISCOUNT'); ?>
						</span>
						<s>
						<?php if(ComponentParams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $priceInfo->prediscountInteger ?></span><?php if((int)$priceInfo->prediscountFractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $priceInfo->prediscountFractional ?></span><?php endif; ?><?php if(ComponentParams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span><?php endif; ?>
						</s>
					<?php endif; ?>
				</td>
			<?php endforeach ?>
		</tr>
		<?php endif; ?>

		<?php if ($this->includeSignup == 2): ?>
		<tr>
			<?php foreach($this->items as $level):
				$priceInfo = $this->getLevelPriceInformation($level);
			?>
			<td class="akeebasubs-strappy-signupfee">
				<?php if(abs($priceInfo->signupFee) >= 0.01): ?>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST'); ?>
				<?php if(ComponentParams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $priceInfo->signupInteger ?></span><?php if((int)$priceInfo->signupFractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $priceInfo->signupFractional ?></span><?php endif; ?><?php if(ComponentParams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo ComponentParams::getParam('currencysymbol','€')?></span><?php endif; ?>
				<?php endif; ?>
			</td>
			<?php endforeach ?>
		</tr>
		<?php endif; ?>

		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-image">
				<img src="<?php echo Image::getURL($level->image)?>" />
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-description">
				<?php echo JHTML::_('content.prepare', Message::processLanguage($level->description) );?>
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-subscribe">
				<button
					class="btn btn-inverse btn-primary"
					onclick="window.location='<?php echo \JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>'">
					<?php echo JText::_('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>
				</button>
			</td>
		<?php endforeach ?>
		</tr>
	</table>

<?php echo $this->getContainer()->template->loadPosition('akeebasubscriptionslistfooter')?>
</div>