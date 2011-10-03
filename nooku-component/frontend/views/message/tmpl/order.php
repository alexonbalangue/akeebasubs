<?php defined('KOOWA') or die(); ?>

<!--  --
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<!--  -->

<?=KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->stepsbar ? @template('com://site/akeebasubs.view.level.steps',array('step' => 'done')) : ''?>

<h1 class="componentheading">
	<?= @escape(@text('COM_AKEEBASUBS_MESSAGE_THANKYOU')) ?>
</h1>

<?=JHTML::_('content.prepare', $message->ordertext)?>

<div class="akeebasubs-goback">
	<p><a href="<?=JURI::base()?>"><?=@text('COM_AKEEBASUBS_MESSAGE_BACK')?></a></p>
</div>