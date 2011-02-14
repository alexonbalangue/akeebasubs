<?php defined('KOOWA') or die(); ?>

<!--  --
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://lib_koowa/css/koowa.css" />
<style src="media://com_akeebasubs/css/frontend.css" />
<!--  -->

<div id="akeebasubs" class="levels">
<?if(!empty($levels)) foreach($levels as $level):?>
	<div class="level">
		<h3 class="level-title">
			<span class="level-title-text">
				<a href="<?=@route('view=level&id='.$level->id)?>">
					<?=@escape($level->title)?>
				</a>
			</span>
			<div class="level-price">
				<span class="level-price-currency"><?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span>
				<span class="level-price-integer"><?=floor($level->price)?></span><span class="level-price-separator">.</span><span class="level-price-decimal"><?=sprintf('%02u', $level->price - floor($level->price))?></span>
			</div>
		
		</h3>
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
				<form action="<?=@route('view=level&id='.$level->id)?>" method="get">
					<input type="submit" value="<?=@text('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>" />
				</form>
			</div>
		</div>
	</div>
<?endforeach;?>	
</div>