<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$editor = JFactory::getEditor();

$this->loadHelper('cparams');
$this->loadHelper('select');
?>

<div class="akeeba-bootstrap">
<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="taxrule" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_taxrule_id" value="<?php echo $this->item->akeebasubs_taxrule_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

	<div class="well">
		<label for="country" class="main"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_COUNTRY'); ?></label>
		<?php echo AkeebasubsHelperSelect::countries($this->item->country,' country') ?>
		<div class="akeebasubs-clear"></div>

		<label for="state" class="main"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_STATE'); ?></label>
		<?php echo AkeebasubsHelperSelect::states($this->item->state,' state') ?>
		<div class="akeebasubs-clear"></div>

		<label for="city" class="main"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_CITY'); ?></label>
		<input type="text" name="city" id="city" value="<?php echo $this->item->city?>" />
		<div class="akeebasubs-clear"></div>

		<label for="vies" class="main" class="main"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_VIES'); ?></label>
		<?php echo JHTML::_('select.booleanlist', 'vies', null, $this->item->vies); ?>
		<div class="akeebasubs-clear"></div>

		<label for="taxrate" class="main"><?php echo JText::_('COM_AKEEBASUBS_TAXRULES_TAXRATE'); ?></label>
		<input type="text" name="taxrate" id="taxrate" value="<?php echo$this->item->taxrate?>" /> <strong>%</strong>
		<div class="akeebasubs-clear"></div>

		<label for="enabled" class="main" class="main">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	</div>

</form>
	
</div>
</div>
</div>