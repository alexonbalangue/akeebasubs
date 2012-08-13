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

?>

<div id="akeebasubs" class="levels akeeba-bootstrap">

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
			$formatedPrice = sprintf('%1.02f',$level->price);
			$dotpos = strpos($formatedPrice, '.');
			$price_integer = substr($formatedPrice,0,$dotpos);
			$price_fractional = substr($formatedPrice,$dotpos+1);?>
			<td class="akeebasubs-strappy-price">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?><span class="akeebasubs-strappy-price-integer"><?php echo $price_integer ?></span><span class="akeebasubs-strappy-price-separator">.</span><span class="akeebasubs-strappy-price-decimal"><?php echo $price_fractional ?></span><?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?><span class="akeebasubs-strappy-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><?php endif; ?>
			</td>
		<?php endforeach ?>
		</tr>
		<tr>
		<?php foreach($this->items as $level):?>
			<td class="akeebasubs-strappy-image">
				<img src="<?php echo JURI::base()?><?php echo trim(AkeebasubsHelperCparams::getParam('imagedir','images/'),'/') ?>/<?php echo $level->image?>" />
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