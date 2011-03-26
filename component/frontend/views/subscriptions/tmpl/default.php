<?php defined('KOOWA') or die(); ?>

<script src="media://lib_koowa/js/koowa.js" />
<style src="media://lib_koowa/css/koowa.css" />
<style src="media://com_akeebasubs/css/frontend.css" />

<div id="akeebasubs" class="subscriptions">
	<h2 class="pageTitle"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE')?></h2>
	<form action="<?= @route() ?>" method="get" class="adminform" name="adminForm">
	<table class="asfelist" width="100%">
		<thead>
			<tr>
				<th width="60px">
					<?=@text('COM_AKEEBASUBS_COMMON_ID')?>
				</th>
				<th>
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')?>
				</th>
				<th width="100px">
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')?>
				</th>
				<th width="120px">
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')?>
				</th>
				<th width="120px">
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')?>
				</th>
				<th width="40px">
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED')?>
				</th>
				<th>
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTIONS')?>
				</th>
			</tr>
		</thead>
		
		<tfoot>
			<tr>
				<td colspan="20">
					<?= @helper('paginator.pagination', array('total' => $total)) ?>
				</td>
			</tr>
		</tfoot>

		<tbody>
			<?php if(count($subscriptions)): ?>
			<?php $m = 1; $i = 0; ?>
			<?php foreach($subscriptions as $subscription):?>
			<?php
				$m = 1 - $m;
				$email = trim($subscription->email);
				$email = strtolower($email);
				$gravatarHash = md5($email);
				$rowClass = ($subscription->enabled) ? '' : 'expired'
			?>
			<tr class="row<?=$m?> <?=$rowClass?>">
				<td align="left">
					<?=sprintf('%05u', (int)$subscription->id)?>
				</td>
				<td>
					<?=@escape($subscription->title)?>
				</td>
				<td>
					<?=@text('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$subscription->state)?>
				</td>
				<td>
					<?if(empty($subscription->publish_up) || ($subscription->publish_up == '0000-00-00 00:00:00')):?>
					&mdash;
					<?else:?>
					<?= @helper('date.format', array('date' => $subscription->publish_up, 'format' => '%Y-%m-%d %H:%M' )) ?>
					<?endif;?>
				</td>
				<td>
					<?if(empty($subscription->publish_up) || ($subscription->publish_down == '0000-00-00 00:00:00')):?>
					&mdash;
					<?else:?>
					<?= @helper('date.format', array('date' => $subscription->publish_down, 'format' => '%Y-%m-%d %H:%M' )) ?>
					<?endif;?>
				</td>
				<td align="center">
					<?if($subscription->enabled):?>
					<img src="media://com_akeebasubs/images/frontend/enabled.png" align="center" />
					<?else:?>
					<img src="media://com_akeebasubs/images/frontend/disabled.png" align="center" />
					<?endif;?>
	            </td>
	            <td>
					<a href="<?=@route('view=subscription&id='.$subscription->id)?>">
						<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_VIEW')?>
					</a>

	            	<?if(($subscription->state == 'C')):?>
	            	&bull;
	            	<a href="<?=@route('view=level&slug='.$subscription->slug)?>">
	            		<?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_RENEW')?>
	            	</a>
	            	<?endif;?>
	            </td>
			</tr>
			<?php endforeach; ?>
			<?php else: ?>
			<tr>
				<td colspan="20">
					<?= @text('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>		
		
	</table>
	</form>	
</div>