<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');
$this->loadHelper('message');

// Translate message
$message = AkeebasubsHelperMessage::processLanguage($this->item->canceltext);
// Parse merge tags
$message = AkeebasubsHelperMessage::processSubscriptionTags($message, $this->subscription);
// Process content plugins
$message = JHTML::_('content.prepare', $message);
?>

<?php if(AkeebasubsHelperCparams::getParam('stepsbar',1) && ($this->subscription->prediscount_amount > 0.01)):?>
<?php echo $this->loadAnyTemplate('level/steps',array('step'=>'done')); ?>
<?php endif; ?>

<h1 class="componentheading">
	<?php echo $this->escape(JText::_('COM_AKEEBASUBS_MESSAGE_SORRY')) ?>
</h1>

<?php echo JHTML::_('content.prepare', $message)?>

<div class="akeebasubs-goback">
	<p><a href="<?php echo JURI::base()?>"><?php echo JText::_('COM_AKEEBASUBS_MESSAGE_BACK')?></a></p>
</div>

<?php echo $this->pluginHTML; ?>