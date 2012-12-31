<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>

<div style="float:left;">
	<div class="icon">
		<a href="index.php?option=com_akeebasubs&view=levels&task=add">
			<img alt="<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_LEVEL');?>"
				 src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/folder_new.png')?>" />
			<span><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_LEVEL');?></span>
		</a>
	</div>
</div>

<div style="float:left;">
	<div class="icon">
		<a href="index.php?option=com_akeebasubs&view=subscriptions&task=add">
			<img alt="<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_SUBSCRIPTION');?>"
				src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/bookmark_add.png')?>" />
			<span><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_SUBSCRIPTION');?></span>
		</a>
	</div>
</div>

<div style="float:left;">
	<div class="icon">
		<a href="index.php?option=com_akeebasubs&view=coupons&task=add">
			<img alt="<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_COUPON');?>"
				src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/money.png')?>" />
			<span><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_COUPON');?></span>
		</a>
	</div>
</div>

<div style="float:left;">
	<div class="icon">
		<a href="index.php?option=com_akeebasubs&view=tools">
			<img alt="<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_TOOLS');?>"
				src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/db_update.png')?>" />
			<span><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_TOOLS');?></span>
		</a>
	</div>
</div>

<?php echo LiveUpdate::getIcon(); ?>