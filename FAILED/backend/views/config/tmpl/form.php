<? defined('KOOWA') or die('Restricted access'); ?>

<?= @helper('behavior.tooltip'); ?>
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://com_akeebasubs/css/backend.css" />

<form action="<?= @route() ?>" method="post" class="adminform" name="adminForm">
	<?=$formhtml?>
</form>