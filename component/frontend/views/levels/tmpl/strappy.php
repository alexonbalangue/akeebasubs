<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('message');
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/image.php';
if(!class_exists('AkeebasubsHelperImage')) die('SKATA');

// Take display VAT into account
$vatRate = AkeebasubsHelperCparams::getParam('vatrate', 0);
$includesignup = AkeebasubsHelperCparams::getParam('includesignup', 2);
$vatMultiplier = (100 + (int)$vatRate) / 100;
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
			if ($includesignup == 1)
			{
				$formatedPrice = sprintf('%1.02F', ($level->price + $signupFee) * $vatMultiplier);
			}
			else
			{
				$formatedPrice = sprintf('%1.02F', ($level->price) * $vatMultiplier);
			}
			$dotpos = strpos($formatedPrice, '.');
			$price_integer = substr($formatedPrice,0,$dotpos);
			$price_fractional = substr($formatedPrice,$dotpos+1);?>
			<td class="akeebasubs-strappy-price">
				<?php if(AkeebasubsHelperCparams::getParam('renderasfree', 0) && ($level->price < 0.01)):?>
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_LBL_FREE') ?>
				<?php else: ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $price_integer ?></span><?php if((int)$price_fractional > 0): ?><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
				<?php endif; ?>
			</td>
		<?php endforeach ?>
		</tr>
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
					class="btn btn-inverse"
					onclick="window.location='<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>'">
					<?php echo JText::_('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>
				</button>
			</td>
		<?php endforeach ?>
		</tr>
	</table>

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistfooter')?>
</div>