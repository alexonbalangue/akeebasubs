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
<?=@text('')?>
<?else:?>
<ul class="akeebasubs-subscriptions-itemized-active">
<?foreach($subs as $sub):?>
	<li><?=$sub?></li>
<?endforeach?>
</ul>
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