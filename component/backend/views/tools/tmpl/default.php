<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('');

AkeebaStrapper::addJSfile('media://com_akeebasubs/js/blockui.js?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');

?>

<div class="row-fluid">
<div class="span12">

<div class="alert alert-danger">
	<button class="close" data-dismiss="alert">×</button>
	<?php echo JText::_('COM_AKEEBASUBS_TOOLS_BIGFATWARNING'); ?>
</div>

<h1><?php echo JText::_('COM_AKEEBASUBS_TOOLS_IMPORT_TITLE');?></h1>

<?php if(!empty($this->items)): ?>
<?php foreach($this->items as $key => $tool): ?>
<p>
	<button onclick="doStartConvertSubscriptions('<?php echo $tool->getName()?>')">
		<?php echo JText::_('COM_AKEEBASUBS_TOOLS_IMPORT_FROM_'.$tool->getName());?>
	</button>
</p>
<?php endforeach; ?>
<div id="refreshMessage" style="display:none">
	<h3><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_TOOLS_IMPORT_RUNNING');?></h3>
	<p><img id="asriSpinner" src="<?php echo JURI::base()?>../media/com_akeebasubs/images/throbber.gif" align="center" /></p>
	<p><span id="asriPercent">0</span><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_PROGRESS')?></p>
</div>
<?php else: ?>
<p>
	<?php echo JText::_('COM_AKEEBASUBS_TOOLS_ERR_NOTOOLS') ?>
</p>
<?php endif; ?>

</div>
</div>
