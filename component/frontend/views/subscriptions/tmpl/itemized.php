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

?>

<?php $subs = array(); $expired = array(); ?>
<?php if(count($this->items)) foreach($this->items as $subscription){
	if(array_key_exists($subscription->akeebasubs_level_id, $subs)) continue;
	if($subscription->enabled) {
		$title = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($subscription->akeebasubs_level_id)
			->getItem()
			->title;
		$subs[$subscription->akeebasubs_level_id] = $title;
	} elseif(!$subscription->enabled) {
		$expired[] = $subscription->akeebasubs_level_id;
	}
}?>

<?php if(empty($subs)):?>
<?php echo JText::_('')?>
<?php else:?>
<ul class="akeebasubs-subscriptions-itemized-active">
<?php foreach($subs as $sub):?>
	<li><?php echo $sub ?></li>
<?php endforeach?>
</ul>
<?php endif;?>
<?php if(!empty($expired)): $count = count($expired);?>
<span class="akeebasubs-subscriptions-itemized-expired">
<?php if($count == 1):?>
<?php echo JText::_('COM_AKEEBASUBS_LEVELS_ITEMIZED_ONEEXPIREDSUB')?>
<?php else:?>
<?php echo sprintf(JText::_('COM_AKEEBASUBS_LEVELS_ITEMIZED_MANYEXPIREDSUBS'), $count)?>
<?php endif;?>
</span>
<?php endif?>