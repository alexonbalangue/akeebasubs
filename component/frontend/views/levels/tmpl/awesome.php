<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('message');

?>

<div id="akeebasubs" class="levels awesome">

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistheader')?>

<?php $max = count($this->items); ?>

<div class="akeebasubs-awesome">
	<div class="columns columns-<?php echo $max?>">
		<?$i = 0; foreach($this->items as $level): $i++?>
		<?php
			$formatedPrice = sprintf('%1.02f',$level->price);
			$dotpos = strpos($formatedPrice, '.');
			$price_integer = substr($formatedPrice,0,$dotpos);
			$price_fractional = substr($formatedPrice,$dotpos+1);
		?>
		<div class="akeebasubs-awesome-column">
			<div class="column-<?php echo $i == 1 ? 'first' : ($i == $max ? 'last' : 'middle')?>">
				<div class="akeebasubs-awesome-header">
					<div class="akeebasubs-awesome-level">
						<a href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&format=html&slug='.$level->slug)?>" class="akeebasubs-awesome-level-link">
							<?php echo $this->escape($level->title)?>
						</a>
					</div>
					<div class="akeebasubs-awesome-price">
						<span class="akeebasubs-awesome-price-currency"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span><span class="akeebasubs-awesome-price-integer"><?php echo $price_integer ?></span><span class="akeebasubs-awesome-price-separator">.</span><span class="akeebasubs-awesome-price-decimal"><?php echo $price_fractional ?></span>
					</div>
				</div>
				<div class="akeebasubs-awesome-body">
					<div class="akeebasubs-awesome-image">
						<img src="<?php echo JURI::base()?><?php echo trim(AkeebasubsHelperCparams::getParam('imagedir',version_compare(JVERSION,'1.6.0','ge') ? 'images/' :'images/stories/'),'/') ?>/<?php echo $level->image?>" />
					</div>
					<div class="akeebasubs-awesome-description">
						<?php echo JHTML::_('content.prepare', AkeebasubsHelperMessage::processLanguage($level->description) );?>
					</div>
				</div>
				<div class="akeebasubs-awesome-footer">
					<td class="akeebasubs-awesome-subscribe">
						<button onclick="window.location='<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$level->slug.'&format=html&layout=default')?>'">
							<?php echo JText::_('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>
						</button>
					</td>
				</div>
			</div>
		</div>
		<?endforeach?>
		<div class="level-clear"></div>
	</div>
</div>

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionslistfooter')?>
</div>