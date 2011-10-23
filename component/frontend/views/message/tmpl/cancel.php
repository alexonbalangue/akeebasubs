<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('mesage');

// Translate message
$message = AkeebasubsHelperMessage::processLanguage($this->item->canceltext);

?>

<?php if(AkeebasubsHelperCparams::getParam('stepsbar',1)):?>
<?php echo $this->loadAnyTemplate('level/steps',array('step'=>'done')); ?>
<?php endif; ?>

<h1 class="componentheading">
	<?= $this->escape(JText::_('COM_AKEEBASUBS_MESSAGE_SORRY')) ?>
</h1>

<?php echo JHTML::_('content.prepare', $message)?>

<div class="akeebasubs-goback">
	<p><a href="<?php echo JURI::base()?>"><?php echo JText::_('COM_AKEEBASUBS_MESSAGE_BACK')?></a></p>
</div>