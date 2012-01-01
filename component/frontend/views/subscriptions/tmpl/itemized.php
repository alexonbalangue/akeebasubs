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

$subs = array();
$expired = array();
jimport('joomla.utilities.date');
$jNow = new JDate();

if(count($this->items)) foreach($this->items as $subscription){
	if(array_key_exists($subscription->akeebasubs_level_id, $subs)) continue;
	if($subscription->enabled) {
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($subscription->akeebasubs_level_id)
			->getItem();
		if(is_object($level)) {
			if($level->akeebasubs_level_id = $subscription->akeebasubs_level_id) {
				$subs[$subscription->akeebasubs_level_id] = $level->title;
			}
		}
	} else {
		$jUp = new JDate($subscription->publish_up);
		if($jUp->toUnix() > $jNow->toUnix()) continue;
		// Is it expired or just not activated yet?
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