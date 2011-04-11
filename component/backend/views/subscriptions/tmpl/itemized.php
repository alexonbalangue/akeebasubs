<?php defined('KOOWA') or die(); ?>

<?$subs = array(); $expired = array(); ?>
<?if(count($subscriptions)) foreach($subscriptions as $subscription){
	if(array_key_exists($subscription->akeebasubs_level_id, $subs)) continue;
	if($subscription->enabled) {
		$title = KFactory::tmp('admin::com.akeebasubs.model.levels')
			->id($subscription->akeebasubs_level_id)
			->getItem()
			->title;
		$subs[$subscription->akeebasubs_level_id] = $title;
	} elseif(!$subscription->enabled) {
		$expired[] = $subscription->akeebasubs_level_id;
	}
}?>

<?if(empty($subs)):?>
<span class="akeebasubs-subscriptions-itemized-none">
<?=@text('COM_AKEEBASUBS_LEVELS_ITEMIZED_NOACTIVESUBS')?>
</span>
<?else:?>
<span class="akeebasubs-subscriptions-itemized-active">
<?$i = 0; foreach($subs as $sub): $i++?>
	<?=$i == 1 ? '' : ' &bull; '?>
	<?=$sub?>
<?endforeach?>
<?endif;?>
<?if(!empty($expired)): $count = count($expired);?>
<span class="akeebasubs-subscriptions-itemized-expired">
<?if($count == 1):?>
<?=@text('COM_AKEEBASUBS_LEVELS_ITEMIZED_ONEEXPIREDSUB')?>
<?else:?>
<?=sprintf(@text('COM_AKEEBASUBS_LEVELS_ITEMIZED_MANYEXPIREDSUBS'), $count)?>
<?endif;?>
</span>
<?endif?>