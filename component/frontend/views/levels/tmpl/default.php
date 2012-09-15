<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('message');

// Take display VAT into account
$vatRate = AkeebasubsHelperCparams::getParam('vatrate', 0);
$vatMultiplier = (100 + (int)$vatRate) / 100;
?>

<div id="akeebasubs" class="levels">

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistheader')?>

<?php if(!empty($this->items)) foreach($this->items as $level):?>
<?php
	$formatedPrice = sprintf('%1.02F', $level->price * $vatMultiplier);
	$dotpos = strpos($formatedPrice, '.');
	$price_integer = substr($formatedPrice,0,$dotpos);
	$price_fractional = substr($formatedPrice,$dotpos+1);
?>
	<div class="level akeebasubs-level-<?php echo $level->akeebasubs_level_id ?>">
		<p class="level-title">
			<span class="level-price">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<span class="level-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
				<?php endif; ?>
				<span class="level-price-integer"><?php echo $price_integer ?></span><?php if((int)$price_fractional > 0): ?><span class="level-price-separator">.</span><span class="level-price-decimal"><?php echo $price_fractional ?></span><?php endif; ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<span class="level-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
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
					<img class="level-image" src="<?php echo JURI::base()?><?php echo trim(AkeebasubsHelperCparams::getParam('imagedir','images/'),'/') ?>/<?php echo $level->image?>" />
					<?php endif;?>
					<?php echo JHTML::_('content.prepare', AkeebasubsHelperMessage::processLanguage($level->description));?>
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

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistfooter')?>
</div>