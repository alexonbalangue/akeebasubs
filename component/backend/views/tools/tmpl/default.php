<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');
?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jquery.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/blockui.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/backend.js?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<h1><?=@text('COM_AKEEBASUBS_TOOLS_IMPORT_TITLE');?></h1>

<? if(!empty($tools)): ?>
<? foreach($tools as $key => $tool): ?>
<? if($tool->canConvert()): ?>
	<button onclick="doStartConvertSubscriptions('<?=$tool->getName()?>')">
		<?=@text('COM_AKEEBASUBS_TOOLS_IMPORT_FROM_'.$tool->getName());?>
	</button>
<? endif; ?>
<? endforeach; ?>
<? else: ?>
	<p>
		<?=@text('COM_AKEEBASUBS_TOOLS_ERR_NOTOOLS')?>
	</p>
<? endif; ?>

<div id="refreshMessage" style="display:none">
	<h3><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_TOOLS_IMPORT_RUNNING');?></h3>
	<p><img id="asriSpinner" src="<?=JURI::base()?>../media/com_akeebasubs/images/throbber.gif" align="center" /></p>
	<p><span id="asriPercent">0</span><?=@text('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_PROGRESS')?></p>
</div>

<script type="text/javascript">
	var akeebasubs_token = "<?php echo JUtility::getToken(); ?>";
</script>