<?php defined('KOOWA') or die(); ?>

<!--  --
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<!--  -->

<h1 class="componentheading">
	<?= @escape(@text('COM_AKEEBASUBS_MESSAGE_SORRY')) ?>
</h1>

<?=$message->canceltext?>

<div class="akeebasubs-goback">
	<p><a href="<?=JURI::base()?>"><?=@text('back')?></a></p>
</div>