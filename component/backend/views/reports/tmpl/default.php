<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>
<div id="cpanel">
	<div style="float:left;">
		<div class="icon">
			<a href="index.php?option=com_akeebasubs&view=levels&task=add">
				<img alt="<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_ADD_LEVEL');?>"
				     src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/dashboard/renew.png')?>" />
				<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_USER_RENEWAL');?></span>
			</a>
		</div>
	</div>
</div>