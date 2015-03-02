<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>
<div id="cpanel">
	<div style="float:left;">
		<div class="icon">
			<a href="index.php?option=com_akeebasubs&view=reports&task=renewals&layout=renewals">
				<img alt="<?php echo JText::_('COM_AKEEBASUBS_REPORTS_USER_RENEWAL');?>"
				     src="<?php echo F0FTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/renew.png')?>" />
				<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_USER_RENEWAL');?></span>
			</a>
		</div>
	</div>
	<div style="float:left;">
		<div class="icon">
			<a href="index.php?option=com_akeebasubs&view=reports&layout=expirations">
				<img alt="<?php echo JText::_('COM_AKEEBASUBS_REPORTS_EXPIRATIONS');?>"
				     src="<?php echo F0FTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/expires.png')?>" />
				<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_EXPIRATIONS');?></span>
			</a>
		</div>
	</div>
	<div style="float:left;">
		<div class="icon">
			<a href="index.php?option=com_akeebasubs&view=reports&task=invoices">
				<img alt="<?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES');?>"
				     src="<?php echo F0FTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/money.png')?>" />
				<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES');?></span>
			</a>
		</div>
	</div>
	<div style="float:left;">
		<div class="icon">
			<a href="index.php?option=com_akeebasubs&view=reports&task=vies">
				<img alt="<?php echo JText::_('COM_AKEEBASUBS_REPORTS_VIES');?>"
				     src="<?php echo F0FTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/money.png')?>" />
				<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_VIES');?></span>
			</a>
		</div>
	</div>
	<div style="float:left;">
		<div class="icon">
			<a href="index.php?option=com_akeebasubs&view=reports&task=vatmoss">
				<img alt="<?php echo JText::_('COM_AKEEBASUBS_REPORTS_VATMOSS');?>"
				     src="<?php echo F0FTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/money.png')?>" />
				<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_VATMOSS');?></span>
			</a>
		</div>
	</div>
</div>