<?php defined('KOOWA') or die(); ?>

<div id="akeebasubs-steps">
	<div id="akeebasubs-steps-header">
		<?=@text('COM_AKEEBASUBS_SUBSCRIBE_STEPHEADER');?>
	</div>
	<div id="akeebasubs-steps-bar">
		<span id="akeebasubs-steps-subscribe" class="step <?=$step == 'subscribe' ? 'active' : ''?>">
			<span class="numbers">1</span>
			<span class="text"><?=@text('COM_AKEEBASUBS_SUBSCRIBE_STEP_SUBSCRIBE')?></span>
		</span>
		<span id="akeebasubs-steps-payment" class="step <?=$step == 'payment' ? 'active' : ''?>">
			<span class="numbers">2</span>
			<span class="text"><?=@text('COM_AKEEBASUBS_SUBSCRIBE_STEP_PAYMENT')?></span>
		</span>
		<span id="akeebasubs-steps-done" class="step <?=$step == 'done' ? 'active' : ''?>">
			<span class="numbers">3</span>
			<span class="text"><?=@text('COM_AKEEBASUBS_SUBSCRIBE_STEP_DONE')?></span>
		</span>
	</div>
</div>