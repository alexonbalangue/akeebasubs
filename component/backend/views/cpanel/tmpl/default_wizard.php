<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$this->loadHelper('cparams');
$wizardstep = (int)JComponentHelper::getParams('com_akeebasubs')->get('wizardstep', 1);
if ($wizardstep >= 6)
{
	return;
}
?>
<div class="well">
	<?php if ($wizardstep == 1): ?>

	<h2>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_TITLE'); ?>
	</h2>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_TEXT'); ?>
	</p>

	<p>
	<?php if(version_compare(JVERSION, '3.0', 'lt')): ?>
	<a href="index.php?option=com_config&view=component&component=com_akeebasubs&path=&tmpl=component"
		class="modal btn btn-primary"
		rel="{handler: 'iframe', size: {x: 660, y: 500}}">
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_BUTTON'); ?>
	</a>
	<?php else: ?>
	<a href="index.php?option=com_config&view=component&component=com_akeebasubs&path=&return=<?php echo base64_encode(JURI::getInstance()->toString()) ?>"
	   class="btn btn-primary">
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP1_BUTTON'); ?>
	</a>
	<?php endif; ?>
	</p>

	<?php elseif ($wizardstep == 2): ?>

	<h2>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TITLE'); ?>
	</h2>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TEXT'); ?>
	</p>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TEXTA'); ?>
	</p>
	<p>
		<a href="index.php?option=com_users&view=groups" class="btn btn-primary">
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_BUTTONA'); ?>
		</a>
	</p>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_TEXTb'); ?>
	</p>
	<p>
		<a href="index.php?option=com_users&view=levels" class="btn btn-primary">
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP2_BUTTONB'); ?>
		</a>
	</p>

	<?php elseif ($wizardstep == 3): ?>

	<h2>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP3_TITLE'); ?>
	</h2>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP3_TEXT'); ?>
	</p>
	<p>
		<a href="index.php?option=com_plugins&view=plugins&filter_search=&filter_folder=akpayment" class="btn btn-primary">
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP3_BUTTON'); ?>
		</a>
	</p>

	<?php elseif ($wizardstep == 4): ?>

	<h2>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP4_TITLE'); ?>
	</h2>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP4_TEXT'); ?>
	</p>
	<p>
		<a href="index.php?option=com_akeebasubs&view=levels" class="btn btn-primary">
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP4_BUTTON'); ?>
		</a>
	</p>

	<?php elseif ($wizardstep == 5): ?>

	<h2>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP5_TITLE'); ?>
	</h2>
	<p>
		<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP5_TEXT'); ?>
	</p>
	<p>
		<a href="https://www.akeebabackup.com/documentation/akeeba-subscriptions.html" class="btn btn-primary">
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_STEP5_BUTTON'); ?>
		</a>
	</p>

	<?php endif; ?>

	<div class="form-actions">
		<a href="index.php?option=com_akeebasubs&view=cpanel&task=wizardstep&wizardstep=<?php echo $wizardstep + 1 ?>" class="btn btn-success">
			<span class="icon icon-white icon-check"></span>
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_COMMON_COMPLETE'); ?>
		</a>
		<a href="index.php?option=com_akeebasubs&view=cpanel&task=wizardstep&wizardstep=6" class="btn btn-warning">
			<span class="icon icon-remove"></span>
			<?php echo JText::_('COM_AKEEBASUBS_CPANEL_WIZARD_COMMON_HIDE'); ?>
		</a>
	</div>
</div>
