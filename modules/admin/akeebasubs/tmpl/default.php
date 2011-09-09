<? 
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */
 
defined('KOOWA') or die('Restricted access'); ?>

<!--
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<table class="adminlist">
	<thead>
		<tr>
			<th>
				<?= @text( 'COM_AKEEBASUBS_COMMON_ID' ); ?>
			</th>
			<th>
				<?= @text( 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL' ); ?>
			</th>
			<th>
				<?= @text( 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER' ); ?>
			</th>
			<th>
				<?= @text( 'COM_AKEEBASUBS_SUBSCRIPTION_NET_AMOUNT' ); ?>
			</th>
			<th>
				<?= @text( 'COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON' ); ?>
			</th>
        </tr>
	</thead>
	<tbody>
		<?php if(count($subscriptions)): ?>
		<?php $m = 1; $i = 0; ?>
		<?php foreach ($subscriptions as $subscription) : ?>
		<?php
			$m = 1 - $m;
			$rowClass = ($subscription->enabled) ? '' : 'expired';
				
			$users = KFactory::get('com://admin/akeebasubs.model.users')
				->user_id($subscription->user_id)
				->getList();
			if(empty($users)) {
				$user_id = 0;
			} else {
				foreach($users as $user) {
					$user_id = $user->id;
					break;
				}
			}
		?>
        <tr class="row<?=$m?> <?=$rowClass?>">
	        <td>
	      		<a href="<?= JRoute::_('index.php?option=com_akeebasubs&view=subscription&id='.$subscription->id); ?>">
					<?=sprintf('%05u', (int)$subscription->id)?>
				</a>
	        </td>
	        <td>
	          	<a href="<?= JRoute::_('index.php?option=com_akeebasubs&view=level&id='.$subscription->akeebasubs_level_id); ?>">
					<?=@escape($subscription->title)?>
				</a>
	        </td>
	        <td>
				<a href="<?= JRoute::_('index.php?option=com_akeebasubs&view=user&id='.$user_id)?>" class="title">	
					<strong><?=@escape($subscription->username)?></strong> (<?=@escape($subscription->name)?>)
				</a>
	        </td>
	        <td>
				<?= sprintf('%2.2f', (float)$subscription->net_amount) ?> <?=KFactory::get('com://admin/akeebasubs.model.configs')->getConfig()->currencysymbol?>
	        </td>
	        <td>
				<?=@helper('com://admin/akeebasubs.template.helper.date.format', array('date' => $subscription->created_on, 'format' => '%Y-%m-%d %H:%M' )) ?>
	        </td>
		</tr>
		<?php endforeach; ?>
		<?php else: ?>
		<tr>
			<td colspan="5">
				<?= @text('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>