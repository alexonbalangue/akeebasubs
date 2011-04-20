<?php defined('KOOWA') or die(); ?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<div id="akeebasubs" class="levels">

<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionslistheader'))?>

<?if(!empty($levels)) foreach($levels as $level):?>
	<div class="level">
		<p class="level-title">
			<span class="level-price">
				<span class="level-price-currency"><?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
				<span class="level-price-integer"><?=floor($level->price)?></span><span class="level-price-separator">.</span><span class="level-price-decimal"><?=sprintf('%02u', 100*($level->price - floor($level->price)))?></span>
			</span>
			<span class="level-title-text">
				<a href="<?=@route('view=level&slug='.$level->slug)?>">
					<?=@escape($level->title)?>
				</a>
			</span>
		</p>
		<div class="level-inner">
			<div class="level-description">
				<div class="level-description-inner">
					<?if(!empty($level->image)):?>
					<img class="level-image" src="<?=JURI::base().'images/stories/'.@escape($level->image)?>" />
					<?endif;?>
					<?=$level->description?>
				</div>
			</div>
			<div class="level-clear"></div>
			<div class="level-subscribe">
				<form action="<?=@route('view=level&slug='.$level->slug)?>" method="get">
					<input type="submit" value="<?=@text('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>" />
				</form>
			</div>
		</div>
	</div>
<?endforeach;?>
<div class="level-clear"></div>	

<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionslistfooter'))?>
</div>