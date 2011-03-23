<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');
?>

<!--
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://lib_koowa/css/koowa.css" />
<style src="media://com_akeebasubs/css/backend.css" />
<script src="media://com_akeebasubs/js/jquery.js" />
<script src="media://com_akeebasubs/js/blockui.js" />
<script src="media://com_akeebasubs/js/backend.js" />
-->

<h1><?=@text('COM_AKEEBASUBS_TOOLS_IMPORT_TITLE');?></h1>

<? foreach($tools as $key => $tool): ?>
<? if($tool->canConvert()): ?>
	<button onclick="doStartConvertSubscriptions('<?=$tool->getName()?>')">
		<?=@text('COM_AKEEBASUBS_TOOLS_IMPORT_FROM_'.$tool->getName());?>
	</button>
<? endif; ?>
<? endforeach; ?>

<div id="refreshMessage" style="display:none">
	<h3><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_TOOLS_IMPORT_RUNNING');?></h3>
	<p><img id="asriSpinner" src="<?=JURI::base()?>../media/com_akeebasubs/images/throbber.gif" align="center" /></p>
	<p><span id="asriPercent">0</span><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_PROGRESS')?></p>
</div>

<script type="text/javascript">
	var akeebasubs_token = "<?php echo JUtility::getToken(); ?>";
</script>