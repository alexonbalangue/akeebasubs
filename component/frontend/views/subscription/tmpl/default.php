<?php defined('KOOWA') or die(); ?>

<!--
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<div id="akeebasubs">

<table class="subscription-table">
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_COMMON_ID')?></td>
		<td class="subscription-info">
			<strong><?=sprintf('%05u', $subscription->id)?></strong>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_USER')?></td>
		<td class="subscription-info">
			<strong><?=KFactory::get('lib.joomla.user', array($subscription->user_id))->username?></strong>
			(<em><?=KFactory::get('lib.joomla.user', array($subscription->user_id))->name?></em>)
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')?></td>
		<td class="subscription-info">
			<?=KFactory::tmp('site::com.akeebasubs.model.levels')->id($subscription->akeebasubs_level_id)->getItem()->title?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')?></td>
		<td class="subscription-info"><?=@helper('date.format', array('date'=>$subscription->publish_up, 'format' => '%Y-%m-%d %H:%M'))?></td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')?></td>
		<td class="subscription-info"><?=@helper('date.format', array('date'=>$subscription->publish_down, 'format' => '%Y-%m-%d %H:%M'))?></td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED')?></td>
		<td class="subscription-info">
			<?if($subscription->enabled):?>
			<img src="media://com_akeebasubs/images/frontend/enabled.png" align="center" />
			<?else:?>
			<img src="media://com_akeebasubs/images/frontend/disabled.png" align="center" />
			<?endif;?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')?></td>
		<td class="subscription-info"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$subscription->state)?></td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_AMOUNT_PAID')?></td>
		<td class="subscription-info">
			<?=sprintf('%2.02f',$subscription->gross_amount)?>
			<?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?=@text('COM_AKEEBASUBS_SUBSCRIPTION_SUBSCRIBED_ON')?></td>
		<td class="subscription-info"><?=@helper('date.format', array('date'=>$subscription->created_on, 'format' => '%Y-%m-%d %H:%M'))?></td>
	</tr>
</table>

</div>