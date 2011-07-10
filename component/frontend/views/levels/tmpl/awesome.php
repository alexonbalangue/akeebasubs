<?php defined('KOOWA') or die(); ?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<div id="akeebasubs" class="levels awesome">

<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionslistheader'))?>

<? $max = count($levels); ?>

<div class="akeebasubs-awesome">
	<div class="columns columns-<?=$max?>">
		<?$i = 0; foreach($levels as $level): $i++?>
		<div class="akeebasubs-awesome-column">
			<div class="column-<?=$i == 1 ? 'first' : ($i == $max ? 'last' : 'middle')?>">
				<div class="akeebasubs-awesome-header">
					<div class="akeebasubs-awesome-level">
						<a href="<?=@route('view=level&layout=default&format=html&slug='.$level->slug)?>" class="akeebasubs-awesome-level-link">
							<?=@escape($level->title)?>
						</a>
					</div>
					<div class="akeebasubs-awesome-price">
						<span class="akeebasubs-awesome-price-currency"><?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->currencysymbol?></span><span class="akeebasubs-awesome-price-integer"><?=floor($level->price)?></span><span class="akeebasubs-awesome-price-separator">.</span><span class="akeebasubs-awesome-price-decimal"><?=sprintf('%02u', 100*($level->price - floor($level->price)))?></span>
					</div>
				</div>
				<div class="akeebasubs-awesome-body">
					<div class="akeebasubs-awesome-image">
						<img src="<?=JURI::base().(version_compare(JVERSION,'1.6.0','ge') ? 'images/' :'images/stories/').@escape($level->image)?>" />
					</div>
					<div class="akeebasubs-awesome-description">
						<?=JHTML::_('content.prepare', $level->description);?>
					</div>
				</div>
				<div class="akeebasubs-awesome-footer">
					<td class="akeebasubs-awesome-subscribe">
						<form action="<?=@route('view=level&layout=default&format=html&slug='.$level->slug)?>" method="get">
							<input class="akeebasubs-awesome-subscribe-button" type="submit" value="<?=@text('COM_AKEEBASUBS_LEVELS_SUBSCRIBE')?>" />
						</form>
					</td>
				</div>
			</div>
		</div>
		<?endforeach?>
		<div class="level-clear"></div>
	</div>
</div>

<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionslistfooter'))?>
</div>