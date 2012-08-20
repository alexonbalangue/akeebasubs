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

$rawActiveLevels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
	->enabled(1)
	->getList();
$activeLevels = array();
$allLevels = array();
if(!empty($rawActiveLevels)) foreach($rawActiveLevels as $l) {
	$activeLevels[] = $l->akeebasubs_level_id;
	$allLevels[$l->akeebasubs_level_id] = $l;
}

$subIDs = array();
if(count($this->items)) foreach($this->items as $sub) {
	$subIDs[] = $sub->akeebasubs_level_id;
}
$subIDs = array_unique($subIDs);
?>

<div id="akeebasubs" class="subscriptions">
	<h2 class="pageTitle"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE')?></h2>
	<form action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscriptions') ?>" method="post" class="adminform" name="adminForm" id="adminForm">
		<?php echo JHtml::_('form.token'); ?>
	<table class="table table-striped" width="100%">
		<thead>
			<tr>
				<th width="60px">
					<?php echo JText::_('COM_AKEEBASUBS_COMMON_ID')?>
				</th>
				<th>
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')?>
				</th>
				<th width="100px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')?>
				</th>
				<th width="120px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')?>
				</th>
				<th width="120px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')?>
				</th>
				<th width="40px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED')?>
				</th>
				<th>
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTIONS')?>
				</th>
			</tr>
		</thead>
		
		<tfoot>
			<tr>
				<td colspan="20">
					<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
				</td>
			</tr>
		</tfoot>

		<tbody>
			<?php if(count($this->items)): ?>
			<?php $m = 1; $i = 0; ?>
			<?php foreach($this->items as $subscription):?>
			<?php
				$m = 1 - $m;
				$email = trim($subscription->email);
				$email = strtolower($email);
				$rowClass = ($subscription->enabled) ? '' : 'expired';
				
				$canRenew = AkeebasubsHelperCparams::getParam('showrenew', 1) ? true : false;
				$level = $allLevels[$subscription->akeebasubs_level_id];
				if($level->only_once) {
					if(in_array($subscription->akeebasubs_level_id,$subIDs)) {
						$canRenew = false;
					}
				}
			?>
			<tr class="row<?php echo $m?> <?php echo $rowClass?>">
				<td align="left">
					<?php echo sprintf('%05u', (int)$subscription->akeebasubs_subscription_id)?>
				</td>
				<td>
					<?php echo $this->escape($subscription->title)?>
				</td>
				<td>
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$subscription->state)?>
				</td>
				<td>
					<?php if(empty($subscription->publish_up) || ($subscription->publish_up == '0000-00-00 00:00:00')):?>
					&mdash;
					<?php else:?>
					<?php echo AkeebasubsHelperFormat::date($subscription->publish_up) ?>
					<?php endif;?>
				</td>
				<td>
					<?php if(empty($subscription->publish_up) || ($subscription->publish_down == '0000-00-00 00:00:00')):?>
					&mdash;
					<?php else:?>
					<?php echo AkeebasubsHelperFormat::date($subscription->publish_down) ?>
					<?php endif;?>
				</td>
				<td align="center">
					<?php if($subscription->enabled):?>
					<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/enabled.png" align="center" />
					<?php else:?>
					<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/disabled.png" align="center" />
					<?php endif;?>
	            </td>
	            <td>
					<a class="btn btn-mini btn-info" href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscription&id='.$subscription->akeebasubs_subscription_id)?>">
						<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_VIEW')?>
					</a>

	            	<?php if(($subscription->state == 'C') && (in_array($subscription->akeebasubs_level_id, $activeLevels))):?>
					<?php if($canRenew): ?>
	            	<?php $slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
						->setId($subscription->akeebasubs_level_id)
						->getItem()
						->slug;?>
	            	<a class="btn btn-mini btn-inverse" href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$slug)?>">
	            		<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_RENEW')?>
	            	</a>
	            	<?php endif;?>
	            	<?php endif;?>
	            </td>
			</tr>
			<?php endforeach; ?>
			<?php else: ?>
			<tr>
				<td colspan="20">
					<?php echo JText::_('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>		
		
	</table>
	</form>	
</div>