<?php defined('KOOWA') or die(); ?>

<div id="akeebasubs-subscribe-level">
	<h3 class="level-title">
		<span class="level-title-text">
			<?=@escape($level->title)?>
		</span>
	</h3>
	<div class="level-description level-description-short">
		<div class="level-description-inner">
			<?if(!empty($level->image)):?>
			<img class="level-image" src="<?=JURI::base().'images/stories/'.@escape($level->image)?>" />
			<?endif;?>
			<?=$level->description?>
		</div>
	</div>
	<div class="level-clear"></div>	
</div>